import type { Express, Request, Response } from 'express';
import multer from 'multer';
import type { AuthRequest } from './auth.js';
import { authMiddleware } from './auth.js';
import { canManageProcesses } from './auth-login.js';
import {
  createClient,
  findClientById,
  toPublicClient,
} from './clients-store.js';
import {
  createProcess,
  findActiveProcessByClientId,
  findProcessById,
  updateProcess,
} from './processes-store.js';
import {
  createProcessFile,
  listProcessFiles,
} from './process-files-store.js';
import { listAuditLogs, logAudit } from './audit-store.js';
import { recordClientSelfEdit } from './alerts-store.js';
import {
  buildFileObjectName,
  buildStorageContext,
  WIZARD_REQUIRED_FILES,
  FILE_TYPE_LABELS,
} from './process-constants.js';
import {
  savePrivateDoc,
  validateDocMime,
} from './docs-store.js';
import type { FileTypeCode, LegalRepresentative } from './types/process.js';
import { sendPortalWelcomeEmail } from './mail-service.js';
import { applyClientPatch, CLIENT_AUDIT_FIELDS, parseLegalRepresentative } from './client-patch.js';
import { diffRecords, summarizeChanges } from './audit-diff.js';
import { buildClientPasswordResetUrl } from './auth-password-reset.js';
import { getAdminSettings } from './settings-store.js';

const docUpload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: 15 * 1024 * 1024 },
  fileFilter: (_req, file, cb) => {
    if (validateDocMime(file.mimetype)) cb(null, true);
    else cb(new Error('Tipo de arquivo não permitido.'));
  },
});

export function registerPortalRoutes(app: Express) {
  app.post('/api/portal/register', docUpload.any(), async (req: Request, res: Response) => {
    try {
      const body = req.body as Record<string, string>;
      const {
        email,
        password,
        name,
        cpf,
        rg,
        rgEstado,
        rgOrgaoEmissor,
        rgDataEmissao,
        phone,
        endereco,
        numero,
        complemento,
        bairro,
        cep,
        cidade,
        uf,
        modality,
        lgpdConsent,
        termsConsent,
        hasRepresentante,
        repNome,
        repCpf,
        repRg,
        repRgOrgaoEmissor,
        repRgEstado,
        repTelefone,
      } = body;

      if (!lgpdConsent || !termsConsent) {
        return res.status(400).json({ error: 'É necessário aceitar a política de privacidade e os termos.' });
      }
      if (!email?.trim() || !password || password.length < 8) {
        return res.status(400).json({ error: 'Informe e-mail e senha (mín. 8 caracteres).' });
      }
      if (!name?.trim() || !cpf?.trim()) {
        return res.status(400).json({ error: 'Informe nome e CPF.' });
      }
      const cpfDigits = cpf.replace(/\D/g, '');
      if (cpfDigits.length !== 11) {
        return res.status(400).json({ error: 'CPF inválido. Informe os 11 dígitos.' });
      }

      let representante: LegalRepresentative | null = null;
      if (hasRepresentante === 'true' || hasRepresentante === '1') {
        if (!repNome?.trim() || !repCpf?.trim()) {
          return res.status(400).json({ error: 'Preencha os dados do representante legal.' });
        }
        representante = {
          nome: repNome.trim(),
          cpf: repCpf.replace(/\D/g, ''),
          rg: repRg || '',
          rgOrgaoEmissor: repRgOrgaoEmissor,
          rgEstado: repRgEstado,
          telefone: repTelefone,
        };
      }

      const now = new Date().toISOString();
      const client = await createClient({
        email,
        name,
        password,
        cpf,
        rg,
        rgEstado,
        rgOrgaoEmissor,
        rgDataEmissao,
        phone,
        endereco,
        numero,
        complemento,
        bairro,
        cep,
        cidade,
        uf,
        representante,
        lgpdConsentAt: now,
        termsConsentAt: now,
      });

      const process = await createProcess({
        clientId: client.id,
        modality: modality === 'taxi' ? 'taxi' : 'pcd',
      });

      const storageCtx = buildStorageContext(
        { name: client.name, cpf: client.cpf, storageSlug: client.storageSlug },
        process
      );

      const files = (req.files as Express.Multer.File[]) || [];
      const uploadedTypes = new Set<string>();

      for (const file of files) {
        const fieldName = file.fieldname as FileTypeCode;
        if (!FILE_TYPE_LABELS[fieldName]) continue;

        const objectName = buildFileObjectName(storageCtx, fieldName, file.originalname);
        await savePrivateDoc(file.buffer, file.mimetype, objectName);
        await createProcessFile({
          processId: process.id,
          clientId: client.id,
          fileType: fieldName,
          objectName,
          originalName: file.originalname,
          mimeType: file.mimetype,
          size: file.size,
          uploadedBy: client.id,
          uploadedByRole: 'cliente',
        });
        uploadedTypes.add(fieldName);
      }

      const required = representante
        ? [...WIZARD_REQUIRED_FILES, 'alvara_curatela' as FileTypeCode]
        : WIZARD_REQUIRED_FILES;

      const missing = required.filter((t) => !uploadedTypes.has(t));
      if (missing.length > 0) {
        return res.status(400).json({
          error: `Documentos obrigatórios faltando: ${missing.map((m) => FILE_TYPE_LABELS[m]).join(', ')}`,
        });
      }

      await logAudit({
        action: 'consent',
        resourceType: 'client',
        resourceId: client.id,
        userId: client.id,
        userRole: 'cliente',
        userEmail: client.email,
        ip: req.ip,
      });

      const settings = await getAdminSettings();
      const siteUrl = settings.integrations.siteUrl.replace(/\/$/, '');
      const loginUrl = `${siteUrl}/entrar`;
      const resetUrl = await buildClientPasswordResetUrl(client);
      const welcome = await sendPortalWelcomeEmail({
        name: client.name,
        email: client.email,
        loginUrl,
        resetUrl,
      });
      if (!welcome.sent) {
        console.error('portal welcome email failed', client.email, welcome.error);
      }

      const { authenticateUser } = await import('./auth-login.js');
      const auth = await authenticateUser(email, password);
      res.status(201).json({
        success: true,
        client,
        processId: process.id,
        token: auth?.token,
        user: auth?.user,
      });
    } catch (err) {
      console.error('portal register error', err);
      const message = err instanceof Error ? err.message : 'Falha no cadastro.';
      res.status(400).json({ error: message });
    }
  });

  app.get('/api/portal/me', authMiddleware, async (req: AuthRequest, res: Response) => {
    if (req.user?.role !== 'cliente') {
      return res.status(403).json({ error: 'Acesso restrito ao portal do cliente.' });
    }
    try {
      const client = await findClientById(req.user.id);
      if (!client) return res.status(404).json({ error: 'Cliente não encontrado.' });
      res.json(toPublicClient(client));
    } catch (err) {
      console.error('portal me error', err);
      res.status(500).json({ error: 'Falha ao carregar dados.' });
    }
  });

  app.patch('/api/portal/me', authMiddleware, async (req: AuthRequest, res: Response) => {
    if (req.user?.role !== 'cliente') {
      return res.status(403).json({ error: 'Acesso restrito.' });
    }
    try {
      const before = await findClientById(req.user.id);
      if (!before) return res.status(404).json({ error: 'Cliente não encontrado.' });
      const updated = await applyClientPatch(
        req.user.id,
        { ...req.body, representante: parseLegalRepresentative(req.body?.representante) },
        { allowIdentity: false, allowActive: false }
      );
      if (!updated) return res.status(404).json({ error: 'Cliente não encontrado.' });
      const changes = diffRecords(
        before as unknown as Record<string, unknown>,
        updated as unknown as Record<string, unknown>,
        [...CLIENT_AUDIT_FIELDS]
      );
      if (changes.length) {
        await logAudit({
          action: 'update',
          resourceType: 'client',
          resourceId: before.id,
          userId: req.user.id,
          userRole: req.user.role,
          userEmail: req.user.email,
          ip: req.ip,
          summary: `Cliente atualizou: ${summarizeChanges(changes)}`,
          changes,
        });
        await recordClientSelfEdit({
          type: 'client_self_edit',
          client: updated,
          changes,
        });
      }
      res.json(updated);
    } catch (err) {
      console.error('portal update error', err);
      const message = err instanceof Error ? err.message : 'Falha ao atualizar.';
      res.status(400).json({ error: message });
    }
  });

  app.patch('/api/portal/process', authMiddleware, async (req: AuthRequest, res: Response) => {
    if (req.user?.role !== 'cliente') {
      return res.status(403).json({ error: 'Acesso restrito.' });
    }
    try {
      const process = await findActiveProcessByClientId(req.user.id);
      if (!process) return res.status(404).json({ error: 'Nenhum processo ativo.' });
      const vehicle = req.body?.vehicle as Record<string, string> | undefined;
      if (!vehicle || typeof vehicle !== 'object') {
        return res.status(400).json({ error: 'Informe os dados do veículo.' });
      }
      const cleanVehicle = Object.fromEntries(
        Object.entries(vehicle).filter(([, v]) => String(v ?? '').trim())
      );
      const updated = await updateProcess(process.id, {
        vehicle: Object.keys(cleanVehicle).length ? cleanVehicle : undefined,
      });
      if (!updated) return res.status(404).json({ error: 'Processo não encontrado.' });
      const changes = diffRecords(
        process as unknown as Record<string, unknown>,
        updated as unknown as Record<string, unknown>,
        ['vehicle']
      );
      if (changes.length) {
        await logAudit({
          action: 'update',
          resourceType: 'process',
          resourceId: process.id,
          userId: req.user.id,
          userRole: req.user.role,
          userEmail: req.user.email,
          ip: req.ip,
          summary: `Cliente atualizou veículo: ${summarizeChanges(changes)}`,
          changes,
        });
        const client = await findClientById(req.user.id);
        if (client) {
          await recordClientSelfEdit({
            type: 'process_self_edit',
            client,
            processId: process.id,
            changes,
          });
        }
      }
      res.json(updated);
    } catch (err) {
      console.error('portal process update error', err);
      res.status(500).json({ error: 'Falha ao atualizar processo.' });
    }
  });

  app.get('/api/portal/audit', authMiddleware, async (req: AuthRequest, res: Response) => {
    if (req.user?.role !== 'cliente') {
      return res.status(403).json({ error: 'Acesso restrito.' });
    }
    try {
      const process = await findActiveProcessByClientId(req.user.id);
      const logs = await listAuditLogs({ limit: 400 });
      const own = logs.filter(
        (log) =>
          log.userId === req.user!.id ||
          log.resourceId === req.user!.id ||
          (process && log.resourceId === process.id)
      );
      res.json(own.slice(0, 100));
    } catch (err) {
      res.status(500).json({ error: 'Falha ao carregar auditoria.' });
    }
  });

  app.get('/api/portal/process', authMiddleware, async (req: AuthRequest, res: Response) => {
    if (req.user?.role !== 'cliente') {
      return res.status(403).json({ error: 'Acesso restrito.' });
    }
    try {
      const process = await findActiveProcessByClientId(req.user.id);
      if (!process) return res.status(404).json({ error: 'Nenhum processo ativo.' });
      const files = await listProcessFiles(process.id);
      const clientSteps = process.steps.map((s) => ({
        key: s.key,
        label: s.label,
        status: s.status,
        startedAt: s.startedAt,
        completedAt: s.completedAt,
      }));
      res.json({ process: { ...process, steps: clientSteps }, files });
    } catch (err) {
      console.error('portal process error', err);
      res.status(500).json({ error: 'Falha ao carregar processo.' });
    }
  });

  app.post(
    '/api/portal/files',
    authMiddleware,
    docUpload.single('file'),
    async (req: AuthRequest, res: Response) => {
      if (req.user?.role !== 'cliente') {
        return res.status(403).json({ error: 'Acesso restrito.' });
      }
      if (!req.file) return res.status(400).json({ error: 'Arquivo não enviado.' });

      const { fileType } = req.body as { fileType?: FileTypeCode };
      if (!fileType || !FILE_TYPE_LABELS[fileType]) {
        return res.status(400).json({ error: 'Tipo de arquivo inválido.' });
      }

      try {
        const process = await findActiveProcessByClientId(req.user.id);
        if (!process) return res.status(404).json({ error: 'Nenhum processo ativo.' });

        const client = await findClientById(req.user.id);
        if (!client) return res.status(404).json({ error: 'Cliente não encontrado.' });

        const storageCtx = buildStorageContext(client, process);
        const objectName = buildFileObjectName(storageCtx, fileType, req.file.originalname);
        await savePrivateDoc(req.file.buffer, req.file.mimetype, objectName);
        const record = await createProcessFile({
          processId: process.id,
          clientId: req.user.id,
          fileType,
          objectName,
          originalName: req.file.originalname,
          mimeType: req.file.mimetype,
          size: req.file.size,
          uploadedBy: req.user.id,
          uploadedByRole: 'cliente',
        });

        res.status(201).json(record);
      } catch (err) {
        console.error('portal file upload error', err);
        const message = err instanceof Error ? err.message : 'Falha no upload.';
        res.status(400).json({ error: message });
      }
    }
  );
}

export { canManageProcesses };
