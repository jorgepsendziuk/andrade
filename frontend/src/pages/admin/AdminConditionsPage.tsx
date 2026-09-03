import { useEffect, useState } from 'react';
import { ExternalLink, Loader2, Plus, Trash2 } from 'lucide-react';
import { Link } from 'react-router-dom';
import { SeoHead } from '../../components/seo/SeoHead';
import {
  AdminCard,
  AdminField,
  AdminInput,
  AdminTextarea,
} from '../../components/admin/AdminForm';
import {
  createAdminCondition,
  deleteAdminCondition,
  fetchConditions,
  saveAdminCondition,
} from '../../lib/api';
import type { ConditionPage } from '../../types/site';

const EMPTY: ConditionPage = {
  slug: '',
  title: '',
  metaDescription: '',
  icon: 'outras',
  whoCan: '',
  benefits: [''],
  documents: [''],
  howItWorks: [''],
  faq: [{ question: '', answer: '' }],
};

function linesToList(text: string): string[] {
  return text.split('\n').map((l) => l.trim()).filter(Boolean);
}

function listToLines(items: string[]): string {
  return items.join('\n');
}

function ConditionEditor({
  initial,
  isNew,
  onSaved,
  onCancel,
}: {
  initial: ConditionPage;
  isNew: boolean;
  onSaved: () => void;
  onCancel: () => void;
}) {
  const [form, setForm] = useState<ConditionPage>(initial);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSave = async () => {
    setSaving(true);
    setError(null);
    try {
      const payload = {
        ...form,
        benefits: form.benefits.filter(Boolean),
        documents: form.documents.filter(Boolean),
        howItWorks: form.howItWorks.filter(Boolean),
        faq: form.faq.filter((f) => f.question.trim() && f.answer.trim()),
        allConditions: form.allConditions?.filter(Boolean),
      };
      if (isNew) {
        await createAdminCondition(payload);
      } else {
        await saveAdminCondition(initial.slug, payload);
      }
      onSaved();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Falha ao salvar');
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="space-y-4">
      <AdminCard title={isNew ? 'Nova condição PCD' : `Editar: ${form.title}`}>
        <div className="grid sm:grid-cols-2 gap-4">
          <AdminField label="Slug (URL)" hint="Ex: autismo → /isencao-pcd/autismo">
            <AdminInput
              value={form.slug}
              onChange={(e) => setForm({ ...form, slug: e.target.value })}
              disabled={!isNew}
            />
          </AdminField>
          <AdminField label="Ícone (id)">
            <AdminInput
              value={form.icon}
              onChange={(e) => setForm({ ...form, icon: e.target.value })}
              placeholder="autismo, avc, outras..."
            />
          </AdminField>
          <AdminField label="Título" className="sm:col-span-2">
            <AdminInput value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} />
          </AdminField>
          <AdminField label="Meta description (SEO)" className="sm:col-span-2">
            <AdminTextarea
              value={form.metaDescription}
              onChange={(e) => setForm({ ...form, metaDescription: e.target.value })}
            />
          </AdminField>
          <AdminField label="Quem pode ter direito?" className="sm:col-span-2">
            <AdminTextarea
              value={form.whoCan}
              onChange={(e) => setForm({ ...form, whoCan: e.target.value })}
            />
          </AdminField>
          <AdminField label="Benefícios (um por linha)" className="sm:col-span-2">
            <AdminTextarea
              value={listToLines(form.benefits)}
              onChange={(e) => setForm({ ...form, benefits: linesToList(e.target.value) })}
              rows={5}
            />
          </AdminField>
          <AdminField label="Documentos (um por linha)" className="sm:col-span-2">
            <AdminTextarea
              value={listToLines(form.documents)}
              onChange={(e) => setForm({ ...form, documents: linesToList(e.target.value) })}
              rows={5}
            />
          </AdminField>
          <AdminField label="Como funciona (um passo por linha)" className="sm:col-span-2">
            <AdminTextarea
              value={listToLines(form.howItWorks)}
              onChange={(e) => setForm({ ...form, howItWorks: linesToList(e.target.value) })}
              rows={6}
            />
          </AdminField>
          <AdminField
            label="Lista de enfermidades (opcional — para página Outras condições)"
            className="sm:col-span-2"
          >
            <AdminTextarea
              value={listToLines(form.allConditions || [])}
              onChange={(e) => setForm({ ...form, allConditions: linesToList(e.target.value) })}
              rows={8}
            />
          </AdminField>
        </div>
      </AdminCard>

      <AdminCard title="Perguntas frequentes">
        <div className="space-y-4">
          {form.faq.map((item, i) => (
            <div key={i} className="p-4 bg-slate-50 rounded-xl space-y-2">
              <AdminField label="Pergunta">
                <AdminInput
                  value={item.question}
                  onChange={(e) => {
                    const faq = [...form.faq];
                    faq[i] = { ...item, question: e.target.value };
                    setForm({ ...form, faq });
                  }}
                />
              </AdminField>
              <AdminField label="Resposta">
                <AdminTextarea
                  value={item.answer}
                  onChange={(e) => {
                    const faq = [...form.faq];
                    faq[i] = { ...item, answer: e.target.value };
                    setForm({ ...form, faq });
                  }}
                />
              </AdminField>
              <button
                type="button"
                onClick={() => setForm({ ...form, faq: form.faq.filter((_, j) => j !== i) })}
                className="text-xs text-red-600 hover:underline"
              >
                Remover pergunta
              </button>
            </div>
          ))}
          <button
            type="button"
            onClick={() => setForm({ ...form, faq: [...form.faq, { question: '', answer: '' }] })}
            className="flex items-center gap-2 text-sm text-brand-600 font-medium"
          >
            <Plus size={16} />
            Adicionar pergunta
          </button>
        </div>
      </AdminCard>

      {error && <div className="text-sm text-red-600 bg-red-50 px-4 py-3 rounded-xl">{error}</div>}

      <div className="flex flex-wrap gap-3">
        <button
          type="button"
          onClick={handleSave}
          disabled={saving || !form.slug || !form.title}
          className="btn-primary disabled:opacity-50"
        >
          {saving ? <Loader2 size={16} className="animate-spin" /> : null}
          Salvar condição
        </button>
        <button type="button" onClick={onCancel} className="btn-secondary">
          Cancelar
        </button>
        {!isNew && (
          <a
            href={`/isencao-pcd/${form.slug}`}
            target="_blank"
            rel="noopener noreferrer"
            className="btn-secondary inline-flex items-center gap-2"
          >
            <ExternalLink size={16} />
            Ver no site
          </a>
        )}
      </div>
    </div>
  );
}

export function AdminConditionsPage() {
  const [items, setItems] = useState<ConditionPage[]>([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState<ConditionPage | 'new' | null>(null);

  const load = () => {
    setLoading(true);
    fetchConditions()
      .then(setItems)
      .catch(console.error)
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const handleDelete = async (slug: string) => {
    if (!confirm(`Excluir a página "${slug}"?`)) return;
    try {
      await deleteAdminCondition(slug);
      load();
      if (editing !== 'new' && editing?.slug === slug) setEditing(null);
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Falha ao excluir');
    }
  };

  if (editing) {
    return (
      <>
        <SeoHead title="Editar condição | Portal Andrade" description="Editor de condições PCD" noindex path="/portal/condicoes" />
        <ConditionEditor
          initial={editing === 'new' ? { ...EMPTY, slug: 'nova-condicao' } : editing}
          isNew={editing === 'new'}
          onSaved={() => { setEditing(null); load(); }}
          onCancel={() => setEditing(null)}
        />
      </>
    );
  }

  return (
    <>
      <SeoHead title="Condições PCD | Portal Andrade" description="Gerenciar páginas de condições" noindex path="/portal/condicoes" />
      <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="font-display text-2xl font-bold text-brand-800">Páginas de condições</h1>
          <p className="text-sm text-slate-500 mt-1">
            Edite as páginas de doenças e condições em /isencao-pcd/
          </p>
        </div>
        <button type="button" onClick={() => setEditing('new')} className="btn-primary">
          <Plus size={16} />
          Nova condição
        </button>
      </div>

      {loading ? (
        <div className="flex justify-center py-12">
          <Loader2 className="animate-spin text-brand-500" size={32} />
        </div>
      ) : (
        <AdminCard title={`${items.length} páginas`}>
          <ul className="divide-y divide-slate-100">
            {items.map((item) => (
              <li key={item.slug} className="py-3 flex flex-wrap items-center gap-3 justify-between">
                <div>
                  <p className="font-medium text-brand-800">{item.title}</p>
                  <p className="text-xs text-slate-500">/isencao-pcd/{item.slug}</p>
                </div>
                <div className="flex items-center gap-2">
                  <Link
                    to={`/isencao-pcd/${item.slug}`}
                    target="_blank"
                    className="text-xs text-brand-600 hover:underline"
                  >
                    Ver
                  </Link>
                  <button
                    type="button"
                    onClick={() => setEditing(item)}
                    className="text-xs font-semibold text-brand-700 hover:bg-brand-50 px-3 py-1.5 rounded-lg"
                  >
                    Editar
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDelete(item.slug)}
                    className="p-1.5 text-red-500 hover:bg-red-50 rounded-lg"
                    aria-label="Excluir"
                  >
                    <Trash2 size={16} />
                  </button>
                </div>
              </li>
            ))}
          </ul>
        </AdminCard>
      )}
    </>
  );
}
