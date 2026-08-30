import { useEffect } from 'react';
import { ExternalLink, Loader2, Pencil, Save } from 'lucide-react';
import { useCms } from '../../context/CmsContext';
import { SeoHead } from '../../components/seo/SeoHead';
import { CmsToolbar } from '../../components/cms/CmsToolbar';
import { HomePage } from '../HomePage';

export function AdminSitePage() {
  const { content, loading, setIsEditing, save, isSaving, hasChanges } = useCms();

  useEffect(() => {
    setIsEditing(true);
    return () => setIsEditing(false);
  }, [setIsEditing]);

  if (loading || !content) {
    return (
      <div className="flex justify-center py-20">
        <Loader2 className="animate-spin text-brand-500" size={32} />
      </div>
    );
  }

  return (
    <>
      <SeoHead title="Editar site | Portal Andrade" description="Painel administrativo de edição do site." noindex />
      <div className="space-y-4 -m-4 md:-m-6">
        <div className="bg-white border-b border-slate-200 px-4 md:px-6 py-4 flex flex-wrap items-center justify-between gap-3 sticky top-[57px] z-20">
          <div>
            <h1 className="text-lg font-bold text-brand-800">Editar site</h1>
            <p className="text-slate-500 text-xs mt-0.5">Clique nos textos para editar · arraste seções no painel flutuante</p>
          </div>
          <div className="flex flex-wrap gap-2">
            <a
              href="/"
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border text-sm hover:bg-slate-50"
            >
              <ExternalLink size={16} />
              Ver ao vivo
            </a>
            <button
              type="button"
              onClick={() => void save()}
              disabled={!hasChanges || isSaving}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white text-sm font-medium hover:bg-brand-600 disabled:opacity-50"
            >
              {isSaving ? <Loader2 size={16} className="animate-spin" /> : <Save size={16} />}
              Salvar
            </button>
          </div>
        </div>
        {hasChanges && (
          <div className="mx-4 md:mx-6 bg-amber-50 border border-amber-200 text-amber-800 px-4 py-2 rounded-lg text-sm flex items-center gap-2">
            <Pencil size={16} />
            Alterações não salvas — clique em Salvar antes de sair.
          </div>
        )}
        <div className="cms-editing bg-slate-50">
          <HomePage embed />
        </div>
      </div>
      <CmsToolbar />
    </>
  );
}
