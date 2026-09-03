import { SectionRenderer } from '../components/sections/SectionRenderer';
import { PageLayout } from '../components/layout/PageLayout';
import { SeoHead } from '../components/seo/SeoHead';
import { JsonLd } from '../components/seo/JsonLd';
import { useCms } from '../context/CmsContext';
import {
  DEFAULT_DESCRIPTION,
  buildLocalBusinessSchema,
  buildWebSiteSchema,
} from '../lib/seo';

export function HomePage({ embed }: { embed?: boolean }) {
  const { content } = useCms();

  const title =
    'Carro PCD em Mato Grosso — Isenção IPI, ICMS e IPVA | Andrade Isenções';
  const description =
    content?.site.description ||
    DEFAULT_DESCRIPTION;

  if (embed) {
    return <SectionRenderer />;
  }

  return (
    <PageLayout>
      <SeoHead
        title={title}
        description={description}
        path="/"
        image="/assets/logo/logo-full.png"
      />
      {content && (
        <JsonLd
          data={[
            buildWebSiteSchema(),
            buildLocalBusinessSchema(content.site),
          ]}
        />
      )}
      <SectionRenderer />
    </PageLayout>
  );
}
