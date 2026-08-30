import type { Express, Response } from 'express';
import multer from 'multer';
import path from 'path';
import type { AuthRequest } from './auth.js';
import { authMiddleware, requireRole } from './auth.js';
import { canManageProcesses } from './auth-login.js';
import { listClients, findClientById, toPublicClient } from './clients-store.js';
import {
  listProcesses,
  findProcessById,
  updateProcess,
  updateProcessStep,
  advanceProcessStep,
  createProcess,
  findActiveProcessByClientId,
} from './processes-store.js';
import {
  listProcessFiles,
  createProcessFile,
  findProcessFileById,
  deleteProcessFile,
  updateProcessFile,
} from './process-files-store.js';
import { listConductors, upsertConductors } from './conductors-store.js';
import { logAudit } from './audit-store.js';
import { buildFileObjectName, FILE_TYPE_LABELS } from './process-constants.js';
import { resolveStorageContext, getClientStoragePrefix } from './storage-context.js';
import { clientOwnsObjectName, formatHumanStoragePath, resolveClientStorageSlug } from './storage-slugs.js';
import {
  savePrivateDoc,
  validateDocMime,
  listDocsPrefix,
  deletePrivateDoc,
  getLocalDocPath,
  pipePrivateDoc,
  privateDocExists,
  isSafeStoragePath,
} from './docs-store.js';
import { renderDocument, DOCUMENT_TEMPLATES } from './pdf-templates.js';
import { derivePagamentoMeta } from './pagamentos-utils.js';
import type {
  FileTypeCode,
  ProcessStepKey,
  DocumentTemplateCode,
  ProcessModality,
  ProcessStepStatus,
  VehicleInfo,
  ProcessStatus,
  PagamentoHonorario,
} from './types/process.js';
import { createClient } from './clients-store.js';

const docUpload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 15 * 1024 * 1024 },
  fileFilter: (_req, file, cb) => {
    if (validateDocMime(file.mimetype)) cb(null, true);
    else cb(new Error('Tipo não permitido.'));
  },
});

function staffOnly(req: AuthRequest, res: Response, next: () => void) {
  if (!req.user || !canManageProcesses(req.user.role)) {
    return res.status(403).json({ error: 'Sem permissão.' });
  }
  next();
}

export function registerAdminProcessRoutes(app: Express) {
  app.get('/api/admin/clients', authMiddleware, staffOnly, async (_req, res) => {
    try {
      res.json(await listClients());
    } catch (err) {
      console.error('list clients error', err);
      res.status(500).json({ error: 'Falha ao carregar clientes.' });
    }
  });

  app.get('/api/admin/clients/:id', authMiddleware, staffOnly, async (req, res) => {
    try {
      const client = await findClientById(String(req.params.id));
      if (!client) return res.status(404).json({ error: 'Cliente não encontrado.' });
      res.json(toPublicClient(client));
    } catch (err) {
      res.status(500).json({ error: 'Falha ao carregar cliente.' });
    }
  });

  app.get('/api/admin/processes', authMiddleware, staffOnly, async (_req, res) => {
    try {
      const processes = await listProcesses();
      const clients = await listClients();
      const clientMap = Object.fromEntries(clients.map((c) => [c.id, c]));
      const enriched = processes.map((p) => {
        const completedSteps = p.steps.filter(
          (s) => s.status === 'concluida' && s.key !== 'concluido' && s.key !== 'cancelado'
        ).length;
        const totalSteps = p.steps.filter((s) => s.key !== 'concluido' && s.key !== 'cancelado').length;
        return {
          ...p,
          clientName: clientMap[p.clientId]?.name ?? '—',
          clientCpf: clientMap[p.clientId]?.cpf ?? '',
          clientEmail: clientMap[p.clientId]?.email ?? '',
          clientPhone: clientMap[p.clientId]?.phone ?? '',
          clientStorageSlug: clientMap[p.clientId]?.storageSlug,
          progressPercent: totalSteps > 0 ? Math.round((completedSteps / totalSteps) * 100) : 0,
        };
      });
      res.json(enriched);
    } catch (err) {
      console.error('list processes error', err);
      res.status(500).json({ error: 'Falha ao carregar processos.' });
    }
  });

  app.post('/api/admin/processes', authMiddleware, staffOnly, async (req, res) => {
    const {
      clientId,
      modality,
      newClient,
      force,
    } = req.body as {
      clientId?: string;
      modality?: ProcessModality;
      force?: boolean;
      newClient?: {
        name: string;
        email: string;
        cpf: string;
        phone?: string;
        password?: string;
      };
    };

    try {
      let resolvedClientId = clientId?.trim();
      let generatedPassword: string | undefined;

      if (!resolvedClientId && newClient) {
        const tempPassword =
          newClient.password?.trim() ||
          `Andrade${Math.random().toString(36).slice(2, 8)}!`;
        if (!newClient.password?.trim()) generatedPassword = tempPassword;
        const client = await createClient({
          email: newClient.email,
          name: newClient.name,
          cpf: newClient.cpf,
          phone: newClient.phone,
          password: tempPassword,
          lgpdConsentAt: new Date().toISOString(),
          termsConsentAt: new Date().toISOString(),
        });
        resolvedClientId = client.id;
      }

      if (!resolvedClientId) {
        return res.status(400).json({ error: 'Informe o cliente ou os dados do novo cadastro.' });
      }

      const client = await findClientById(resolvedClientId);
      if (!client) return res.status(404).json({ error: 'Cliente não encontrado.' });

      const active = await findActiveProcessByClientId(resolvedClientId);
      if (active && !force) {
        return res.status(409).json({
          error: 'Este cliente já possui um processo ativo.',
          activeProcessId: active.id,
        });
      }

      const process = await createProcess({
        clientId: resolvedClientId,
        modality: modality ?? 'pcd',
      });

      const enriched = {
        ...process,
        clientName: client.name,
        clientCpf: client.cpf,
        clientEmail: client.email,
        clientPhone: client.phone ?? '',
        progressPercent: 0,
      };

      res.status(201).json({
        process: enriched,
        ...(generatedPassword ? { tempPassword: generatedPassword } : {}),
      });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Falha ao criar processo.';
      res.status(400).json({ error: message });
    }
  });

  app.get('/api/admin/processes/:id', authMiddleware, staffOnly, async (req, res) => {
    try {
      const process = await findProcessById(String(req.params.id));
      if (!process) return res.status(404).json({ error: 'Processo não encontrado.' });
      const [client, files, conductors] = await Promise.all([
        findClientById(process.clientId),
        listProcessFiles(process.id),
        listConductors(process.id),
      ]);
      const publicClient = client ? toPublicClient(client) : null;
      res.json({
        process,
        client: publicClient,
        storagePrefix: client ? getClientStoragePrefix(client) : undefined,
        files: files.map((f) => ({
          ...f,
          humanPath: formatHumanStoragePath(f.objectName),
        })),
        conductors,
        documentTemplates: DOCUMENT_TEMPLATES,
      });
    } catch (err) {
      console.error('get process error', err);
      res.status(500).json({ error: 'Falha ao carregar processo.' });
    }
  });

  app.patch('/api/admin/processes/:id', authMiddleware, staffOnly, async (req: AuthRequest, res) => {
    try {
      const { vehicle, honorarios, pagamentoTipo, pagamentoStatus, pagamentos, modality, status } = req.body as {
        vehicle?: VehicleInfo;
        honorarios?: number;
        pagamentoTipo?: string;
        pagamentoStatus?: string;
        pagamentos?: PagamentoHonorario[];
        modality?: ProcessModality;
        status?: ProcessStatus;
      };

      const patch: Parameters<typeof updateProcess>[1] = {
        vehicle,
        honorarios,
        modality,
        status,
      };

      if (pagamentos !== undefined) {
        patch.pagamentos = pagamentos;
        const derived = derivePagamentoMeta({ honorarios, pagamentos });
        patch.pagamentoTipo = derived.pagamentoTipo;
        patch.pagamentoStatus = derived.pagamentoStatus;
      } else {
        if (pagamentoTipo !== undefined) patch.pagamentoTipo = pagamentoTipo;
        if (pagamentoStatus !== undefined) patch.pagamentoStatus = pagamentoStatus;
      }

      const updated = await updateProcess(String(req.params.id), patch);
      if (!updated) return res.status(404).json({ error: 'Processo não encontrado.' });
      res.json(updated);
    } catch (err) {
      res.status(500).json({ error: 'Falha ao atualizar processo.' });
    }
  });

  app.patch('/api/admin/processes/:id/step', authMiddleware, staffOnly, async (req: AuthRequest, res) => {
    const { stepKey, status, protocol, internalNote } = req.body as {
      stepKey?: ProcessStepKey;
      status?: string;
      protocol?: string;
      internalNote?: string;
    };
    if (!stepKey) return res.status(400).json({ error: 'Informe a etapa.' });
    try {
      const updated = await updateProcessStep(String(req.params.id), stepKey, {
        status: status as ProcessStepStatus,
        protocol,
        internalNote,
      });
      if (!updated) return res.status(404).json({ error: 'Processo não encontrado.' });
      res.json(updated);
    } catch (err) {
      res.status(500).json({ error: 'Falha ao atualizar etapa.' });
    }
  });

  app.post('/api/admin/processes/:id/advance', authMiddleware, staffOnly, async (req, res) => {
    try {
      const updated = await advanceProcessStep(String(req.params.id));
      if (!updated) return res.status(404).json({ error: 'Processo não encontrado.' });
      res.json(updated);
    } catch (err) {
      res.status(500).json({ error: 'Falha ao avançar etapa.' });
    }
  });

  app.put('/api/admin/processes/:id/conductors', authMiddleware, staffOnly, async (req, res) => {
    try {
      const process = await findProcessById(String(req.params.id));
      if (!process) return res.status(404).json({ error: 'Processo não encontrado.' });
      const { conductors } = req.body as { conductors: { nome: string; cpf: string; rg?: string; endereco?: string; telefone?: string }[] };
      const saved = await upsertConductors(process.id, process.clientId, conductors || []);
      res.json(saved);
    } catch (err) {
      res.status(500).json({ error: 'Falha ao salvar condutores.' });
    }
  });

  app.post(
    '/api/admin/processes/:id/files',
    authMiddleware,
    staffOnly,
    docUpload.single('file'),
    async (req: AuthRequest, res) => {
      if (!req.file) return res.status(400).json({ error: 'Arquivo não enviado.' });
      const { fileType } = req.body as { fileType?: FileTypeCode };
      if (!fileType || !FILE_TYPE_LABELS[fileType]) {
        return res.status(400).json({ error: 'Tipo inválido.' });
      }
      try {
        const process = await findProcessById(String(req.params.id));
        if (!process) return res.status(404).json({ error: 'Processo não encontrado.' });

        const storageCtx = await resolveStorageContext(process.clientId, process.id);
        const objectName = buildFileObjectName(storageCtx, fileType, req.file.originalname);
        await savePrivateDoc(req.file.buffer, req.file.mimetype, objectName);
        const record = await createProcessFile({
          processId: process.id,
          clientId: process.clientId,
          fileType,
          objectName,
          originalName: req.file.originalname,
          mimeType: req.file.mimetype,
          size: req.file.size,
          uploadedBy: req.user!.id,
          uploadedByRole: req.user!.role === 'admin' ? 'admin' : 'comercial',
        });
        res.status(201).json(record);
      } catch (err) {
        const message = err instanceof Error ? err.message : 'Falha no upload.';
        res.status(400).json({ error: message });
      }
    }
  );

  app.get('/api/admin/files/:id/url', authMiddleware, staffOnly, async (req: AuthRequest, res) => {
    try {
      const file = await findProcessFileById(String(req.params.id));
      if (!file) return res.status(404).json({ error: 'Arquivo não encontrado.' });

      const exists = await privateDocExists(file.objectName);
      if (!exists) {
        return res.status(404).json({ error: 'Arquivo não encontrado no storage.' });
      }

      await logAudit({
        action: 'download',
        resourceType: 'process_file',
        resourceId: file.id,
        objectName: file.objectName,
        userId: req.user!.id,
        userRole: req.user!.role,
        userEmail: req.user!.email,
        ip: req.ip,
      });

      res.json({
        url: `/api/admin/files/${file.id}/view`,
        mimeType: file.mimeType,
        fileName: file.originalName,
      });
    } catch (err) {
      console.error('admin file url error', err);
      const message = err instanceof Error ? err.message : 'Falha ao gerar URL.';
      res.status(500).json({ error: message });
    }
  });

  app.get('/api/admin/files/:id/view', authMiddleware, staffOnly, async (req: AuthRequest, res) => {
    try {
      const file = await findProcessFileById(String(req.params.id));
      if (!file) return res.status(404).json({ error: 'Arquivo não encontrado.' });
      await pipePrivateDoc(file.objectName, res, {
        fileName: file.originalName,
        mimeType: file.mimeType,
        inline: true,
      });
    } catch (err) {
      console.error('admin file view error', err);
      if (!res.headersSent) {
        const message = err instanceof Error ? err.message : 'Falha ao abrir arquivo.';
        res.status(500).json({ error: message });
      }
    }
  });

  app.get('/api/admin/files/:id/download', authMiddleware, staffOnly, async (req: AuthRequest, res) => {
    try {
      const file = await findProcessFileById(String(req.params.id));
      if (!file) return res.status(404).json({ error: 'Arquivo não encontrado.' });

      await logAudit({
        action: 'download',
        resourceType: 'process_file',
        resourceId: file.id,
        objectName: file.objectName,
        userId: req.user!.id,
        userRole: req.user!.role,
        userEmail: req.user!.email,
        ip: req.ip,
      });

      await pipePrivateDoc(file.objectName, res, {
        fileName: file.originalName,
        mimeType: file.mimeType,
        inline: false,
      });
    } catch (err) {
      console.error('admin file download error', err);
      if (!res.headersSent) {
        res.status(500).json({ error: 'Falha ao baixar arquivo.' });
      }
    }
  });

  app.patch('/api/admin/files/:id', authMiddleware, staffOnly, async (req: AuthRequest, res) => {
    const { fileType, originalName } = req.body as {
      fileType?: FileTypeCode;
      originalName?: string;
    };
    try {
      const file = await findProcessFileById(String(req.params.id));
      if (!file) return res.status(404).json({ error: 'Arquivo não encontrado.' });

      const patch: Parameters<typeof updateProcessFile>[1] = {};
      if (fileType && FILE_TYPE_LABELS[fileType]) patch.fileType = fileType;
      if (originalName?.trim()) patch.originalName = originalName.trim();
      if (!Object.keys(patch).length) {
        return res.status(400).json({ error: 'Nada para atualizar.' });
      }

      const updated = await updateProcessFile(file.id, patch);
      res.json(updated);
    } catch (err) {
      res.status(500).json({ error: 'Falha ao atualizar arquivo.' });
    }
  });

  app.post(
    '/api/admin/files/:id/replace',
    authMiddleware,
    staffOnly,
    docUpload.single('file'),
    async (req: AuthRequest, res) => {
      if (!req.file) return res.status(400).json({ error: 'Arquivo não enviado.' });
      try {
        const file = await findProcessFileById(String(req.params.id));
        if (!file) return res.status(404).json({ error: 'Arquivo não encontrado.' });

        const process = await findProcessById(file.processId);
        if (!process) return res.status(404).json({ error: 'Processo não encontrado.' });

        const storageCtx = await resolveStorageContext(process.clientId, process.id);
        const objectName = buildFileObjectName(storageCtx, file.fileType, req.file.originalname);
        await savePrivateDoc(req.file.buffer, req.file.mimetype, objectName);
        await deletePrivateDoc(file.objectName);

        const updated = await updateProcessFile(file.id, {
          objectName,
          originalName: req.file.originalname,
          mimeType: req.file.mimetype,
          size: req.file.size,
        });

        res.json(updated);
      } catch (err) {
        const message = err instanceof Error ? err.message : 'Falha ao substituir arquivo.';
        res.status(400).json({ error: message });
      }
    }
  );

  app.delete('/api/admin/files/:id', authMiddleware, staffOnly, async (req: AuthRequest, res) => {
    try {
      const file = await deleteProcessFile(String(req.params.id));
      if (!file) return res.status(404).json({ error: 'Arquivo não encontrado.' });
      await deletePrivateDoc(file.objectName);
      await logAudit({
        action: 'delete',
        resourceType: 'process_file',
        resourceId: file.id,
        objectName: file.objectName,
        userId: req.user!.id,
        userRole: req.user!.role,
        userEmail: req.user!.email,
        ip: req.ip,
      });
      res.json({ success: true });
    } catch (err) {
      res.status(500).json({ error: 'Falha ao excluir.' });
    }
  });

  app.get('/api/admin/docs/browse', authMiddleware, staffOnly, async (req, res) => {
    try {
      const prefix =
        typeof req.query.prefix === 'string' ? req.query.prefix : '';
      const result = await listDocsPrefix(prefix);
      res.json(result);
    } catch (err) {
      console.error('docs browse error', err);
      res.status(500).json({ error: 'Falha ao listar arquivos.' });
    }
  });

  app.post(
    '/api/admin/docs/upload',
    authMiddleware,
    staffOnly,
    docUpload.single('file'),
    async (req: AuthRequest, res) => {
      if (!req.file) return res.status(400).json({ error: 'Arquivo não enviado.' });
      const { prefix } = req.body as { prefix?: string };
      if (!prefix || !isSafeStoragePath(prefix.replace(/\/$/, ''))) {
        return res.status(400).json({ error: 'Prefixo inválido.' });
      }
      try {
        const safeName = `${Date.now()}-${req.file.originalname.replace(/[^a-zA-Z0-9._-]/g, '_')}`;
        const objectName = `${prefix.endsWith('/') ? prefix : `${prefix}/`}${safeName}`;
        await savePrivateDoc(req.file.buffer, req.file.mimetype, objectName);
        res.status(201).json({ objectName });
      } catch (err) {
        const message = err instanceof Error ? err.message : 'Falha no upload.';
        res.status(400).json({ error: message });
      }
    }
  );

  app.get('/api/admin/processes/:id/print/:template', authMiddleware, staffOnly, async (req, res) => {
    try {
      const process = await findProcessById(String(req.params.id));
      if (!process) return res.status(404).json({ error: 'Processo não encontrado.' });
      const client = await findClientById(process.clientId);
      if (!client) return res.status(404).json({ error: 'Cliente não encontrado.' });
      const conductors = await listConductors(process.id);
      const template = String(req.params.template) as DocumentTemplateCode;
      const pagamentoId = typeof req.query.pagamentoId === 'string' ? req.query.pagamentoId : undefined;
      const html = renderDocument(template, toPublicClient(client), process, conductors, { pagamentoId });
      res.set('Content-Type', 'text/html; charset=utf-8');
      res.send(html);
    } catch (err) {
      res.status(500).json({ error: 'Falha ao gerar documento.' });
    }
  });

  app.post('/api/admin/contacts/:id/to-process', authMiddleware, staffOnly, async (req, res) => {
    const { listContacts } = await import('./contacts-store.js');
    try {
      const contacts = await listContacts(500);
      const contact = contacts.find((c) => c.id === String(req.params.id));
      if (!contact) return res.status(404).json({ error: 'Contato não encontrado.' });

      const tempPassword = `Andrade${Math.random().toString(36).slice(2, 8)}!`;
      const client = await createClient({
        email: contact.email,
        name: contact.name,
        password: tempPassword,
        cpf: '00000000000',
        phone: contact.phone,
        lgpdConsentAt: new Date().toISOString(),
        termsConsentAt: new Date().toISOString(),
      });
      const process = await createProcess({ clientId: client.id, contactId: contact.id });
      res.status(201).json({ client, process, tempPassword });
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Falha ao criar processo.';
      res.status(400).json({ error: message });
    }
  });

  app.get('/api/docs/local', authMiddleware, async (req: AuthRequest, res) => {
    const objectName = decodeURIComponent(String(req.query.path || ''));
    if (!objectName) return res.status(400).json({ error: 'Informe o caminho do arquivo.' });
    const localPath = getLocalDocPath(objectName);
    if (!localPath) return res.status(404).json({ error: 'Arquivo não encontrado.' });

    if (req.user?.role === 'cliente') {
      const client = await findClientById(req.user.id);
      const slug = client ? resolveClientStorageSlug(client) : req.user.id;
      if (!clientOwnsObjectName(req.user.id, slug, objectName)) {
        return res.status(403).json({ error: 'Sem permissão.' });
      }
    } else if (!canManageProcesses(req.user?.role ?? 'editor')) {
      return res.status(403).json({ error: 'Sem permissão.' });
    }

    res.sendFile(path.resolve(localPath));
  });

  app.get('/api/portal/files/:id/url', authMiddleware, async (req: AuthRequest, res) => {
    if (req.user?.role !== 'cliente') return res.status(403).json({ error: 'Acesso restrito.' });
    try {
      const file = await findProcessFileById(String(req.params.id));
      if (!file || file.clientId !== req.user.id) {
        return res.status(404).json({ error: 'Arquivo não encontrado.' });
      }
      const exists = await privateDocExists(file.objectName);
      if (!exists) return res.status(404).json({ error: 'Arquivo não encontrado no storage.' });
      res.json({ url: `/api/portal/files/${file.id}/view`, mimeType: file.mimeType });
    } catch (err) {
      console.error('portal file url error', err);
      res.status(500).json({ error: 'Falha ao gerar URL.' });
    }
  });

  app.get('/api/portal/files/:id/view', authMiddleware, async (req: AuthRequest, res) => {
    if (req.user?.role !== 'cliente') return res.status(403).json({ error: 'Acesso restrito.' });
    try {
      const file = await findProcessFileById(String(req.params.id));
      if (!file || file.clientId !== req.user.id) {
        return res.status(404).json({ error: 'Arquivo não encontrado.' });
      }
      await pipePrivateDoc(file.objectName, res, {
        fileName: file.originalName,
        mimeType: file.mimeType,
        inline: true,
      });
    } catch (err) {
      console.error('portal file view error', err);
      if (!res.headersSent) res.status(500).json({ error: 'Falha ao abrir arquivo.' });
    }
  });
}
