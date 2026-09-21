import { Link } from 'react-router-dom';
import { PageLayout } from '../components/layout/PageLayout';
import { SeoHead } from '../components/seo/SeoHead';

export function NotFoundPage() {
  return (
    <PageLayout>
      <SeoHead
        title="Página não encontrada | Andrade Isenções"
        description="Este endereço não existe no site atual da Andrade Isenções."
        path="/"
        noindex
      />
      <div className="py-20 text-center px-4">
        <h1 className="font-display text-2xl md:text-3xl font-bold text-brand-800 mb-4">
          Página não encontrada
        </h1>
        <p className="text-text-secondary text-base mb-8 max-w-lg mx-auto">
          Este endereço não existe no site atual. Se você veio de um link antigo, use o início ou o
          Guia PCD.
        </p>
        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <Link to="/" className="btn-primary">
            Voltar ao início
          </Link>
          <Link to="/guia" className="btn-secondary">
            Guia PCD
          </Link>
        </div>
      </div>
    </PageLayout>
  );
}
