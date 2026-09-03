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
  createAdminGuiaArticle,
  deleteAdminGuiaArticle,
  fetchGuiaArticles,
  saveAdminGuiaArticle,
} from '../../lib/api';
import type { GuiaArticle } from '../../types/site';

const EMPTY: GuiaArticle = {
  slug: '',
  title: '',
  metaDescription: '',
  excerpt: '',
  content: [''],
  publishedAt: new Date().toISOString().split('T')[0],
};

function GuiaEditor({
  initial,
  isNew,
  onSaved,
  onCancel,
}: {
  initial: GuiaArticle;
  isNew: boolean;
  onSaved: () => void;
  onCancel: () => void;
}) {
  const [form, setForm] = useState<GuiaArticle>(initial);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSave = async () => {
    setSaving(true);
    setError(null);
    try {
      const payload = {
        ...form,
        content: form.content.filter((p) => p.trim()),
        relatedConditions: form.relatedConditions?.filter(Boolean),
      };
      if (isNew) {
        await createAdminGuiaArticle(payload);
      } else {
        await saveAdminGuiaArticle(initial.slug, payload);
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
      <AdminCard title={isNew ? 'Novo artigo do Guia' : `Editar: ${form.title}`}>
        <div className="grid sm:grid-cols-2 gap-4">
          <AdminField label="Slug (URL)" hint="Ex: documentos-necessarios → /guia/documentos-necessarios">
            <AdminInput
              value={form.slug}
              onChange={(e) => setForm({ ...form, slug: e.target.value })}
              disabled={!isNew}
            />
          </AdminField>
          <AdminField label="Data de publicação">
            <AdminInput
              type="date"
              value={form.publishedAt}
              onChange={(e) => setForm({ ...form, publishedAt: e.target.value })}
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
          <AdminField label="Resumo (listagem)" className="sm:col-span-2">
            <AdminTextarea
              value={form.excerpt}
              onChange={(e) => setForm({ ...form, excerpt: e.target.value })}
            />
          </AdminField>
          <AdminField
            label="Conteúdo (parágrafos)"
            hint="Cada bloco abaixo vira um parágrafo no artigo"
            className="sm:col-span-2"
          >
            <div className="space-y-3">
              {form.content.map((para, i) => (
                <div key={i} className="flex gap-2">
                  <AdminTextarea
                    value={para}
                    onChange={(e) => {
                      const content = [...form.content];
                      content[i] = e.target.value;
                      setForm({ ...form, content });
                    }}
                    rows={3}
                    className="flex-1"
                  />
                  <button
                    type="button"
                    onClick={() => setForm({ ...form, content: form.content.filter((_, j) => j !== i) })}
                    className="p-2 text-red-500 hover:bg-red-50 rounded-lg self-start"
                  >
                    <Trash2 size={16} />
                  </button>
                </div>
              ))}
              <button
                type="button"
                onClick={() => setForm({ ...form, content: [...form.content, ''] })}
                className="flex items-center gap-2 text-sm text-brand-600 font-medium"
              >
                <Plus size={16} />
                Adicionar parágrafo
              </button>
            </div>
          </AdminField>
          <AdminField
            label="Condições relacionadas (slugs, um por linha)"
            className="sm:col-span-2"
          >
            <AdminTextarea
              value={(form.relatedConditions || []).join('\n')}
              onChange={(e) =>
                setForm({
                  ...form,
                  relatedConditions: e.target.value.split('\n').map((l) => l.trim()).filter(Boolean),
                })
              }
              rows={3}
            />
          </AdminField>
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
          Salvar artigo
        </button>
        <button type="button" onClick={onCancel} className="btn-secondary">
          Cancelar
        </button>
        {!isNew && (
          <a
            href={`/guia/${form.slug}`}
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

export function AdminGuiaPage() {
  const [items, setItems] = useState<GuiaArticle[]>([]);
  const [loading, setLoading] = useState(true);
  const [editing, setEditing] = useState<GuiaArticle | 'new' | null>(null);

  const load = () => {
    setLoading(true);
    fetchGuiaArticles()
      .then(setItems)
      .catch(console.error)
      .finally(() => setLoading(false));
  };

  useEffect(() => { load(); }, []);

  const handleDelete = async (slug: string) => {
    if (!confirm(`Excluir o artigo "${slug}"?`)) return;
    try {
      await deleteAdminGuiaArticle(slug);
      load();
      if (editing !== 'new' && editing?.slug === slug) setEditing(null);
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Falha ao excluir');
    }
  };

  if (editing) {
    return (
      <>
        <SeoHead title="Editar artigo | Portal Andrade" description="Editor do Guia PCD" noindex path="/portal/guia" />
        <GuiaEditor
          initial={editing === 'new' ? { ...EMPTY, slug: 'novo-artigo' } : editing}
          isNew={editing === 'new'}
          onSaved={() => { setEditing(null); load(); }}
          onCancel={() => setEditing(null)}
        />
      </>
    );
  }

  return (
    <>
      <SeoHead title="Guia PCD | Portal Andrade" description="Gerenciar artigos do guia" noindex path="/portal/guia" />
      <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="font-display text-2xl font-bold text-brand-800">Artigos do Guia PCD</h1>
          <p className="text-sm text-slate-500 mt-1">Edite os artigos em /guia/</p>
        </div>
        <button type="button" onClick={() => setEditing('new')} className="btn-primary">
          <Plus size={16} />
          Novo artigo
        </button>
      </div>

      {loading ? (
        <div className="flex justify-center py-12">
          <Loader2 className="animate-spin text-brand-500" size={32} />
        </div>
      ) : (
        <AdminCard title={`${items.length} artigos`}>
          <ul className="divide-y divide-slate-100">
            {items.map((item) => (
              <li key={item.slug} className="py-3 flex flex-wrap items-center gap-3 justify-between">
                <div>
                  <p className="font-medium text-brand-800">{item.title}</p>
                  <p className="text-xs text-slate-500">/guia/{item.slug}</p>
                </div>
                <div className="flex items-center gap-2">
                  <Link to={`/guia/${item.slug}`} target="_blank" className="text-xs text-brand-600 hover:underline">
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
