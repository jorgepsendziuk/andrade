# Stakeholder — Andrade Isenções

## Concluído

- [x] **Endereço e mapa** — endereço Galeria Itália 1899 + link/iframe Google Maps corretos
- [x] **Picape removida** do simulador (não enquadra PCD)
- [x] **Formulário de contato** — POST `/api/contact` para `comercial@andradeisencoes.com.br` (SMTP) + fallback mailto
- [x] **+3.000 famílias** na trust bar
- [x] **Link avaliações Google** — `https://maps.app.goo.gl/334gwCfDPaFuRTC7A?g_st=iw`
- [x] **Simulador** — somente MT + todas as cidades em select
- [x] **Tenho direito** — cards reordenados + panfleto completo em “Outras condições”
- [x] **Páginas de condição** — hérnia de disco, condromalácia patelar, fibromialgia, artrite reumatóide, túnel do carpo
- [x] **Botão “Quero saber se tenho direito”** — visual melhorado com ícone
- [x] **Como funciona** — ícones separados dos números (sem sobreposição)
- [x] **G1** — imagem maior na seção mídia
- [x] **Sobre** — sem título; citação elegante com missão + fundador

## Pendente / configurar em produção

- [ ] **SMTP no Vercel** — definir `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS` e `CONTACT_EMAIL` para o e-mail sair automaticamente (sem abrir cliente de e-mail)



pelos docs em /docs/sistema da pra ter uma ideia do sistema
analise-os
é uma empresa de processos de isencoes na compra de carros para pcds e etc.., entao imagine os processos, acompanhamento, e crie o sistema basico, vamos ter uploads de documentos, alguns cadastros... 
tem o ssitema legado php para se ter uma ideia do que estavamos desenovlvendo a um tempo atras, pode se basear nele em algumas coisas, mas a fonte de relatorios documentos que devem ser gerados é esses docx novos ai.

vamos criar o sistema ai dentro dessa parte de gestao, com dados no servico de armaenamento que voce arrumou e arquivos tambem nessa estrutura de buckets.

pdoeriamos implantar uma navegacao nesses buckets pra navegar nos arquivos como pastas ou algo assim 

identificar os dados e aplicar lgpd, termos de uso, cookies no site e sistema, mapeamento de dados, politica de privacidade, seguranca, etc.. 



stakeholder claims:
planejar, executar e ir marcando nesse arquivo o andamento, sem muito verboso ou enfeitado.



## SEO /guia/:slug

- [x] 1. HTTP 200 com SSR no HTML inicial (`server/src/public-page-html.ts` + rota `GET /guia/:slug`)
- [x] 2. `<title>` exclusivo por artigo
- [x] 3. Meta description individual
- [x] 4. Canonical individual
- [x] 5. H1 no HTML inicial
- [x] 6. Conteúdo completo no HTML inicial (parágrafos, links, CTA, condições relacionadas; FAQ só se existir no CMS)
- [x] 7. Open Graph individual
- [x] 8. Schema Article (headline, description, datePublished, dateModified, author, publisher, mainEntityOfPage)
- [x] 9. Schema BreadcrumbList
- [x] 10. FAQ Schema (quando o artigo tiver `faq[]`)
- [x] 11. Sitemap com todas as URLs `/guia/:slug` e `lastmod` por `publishedAt`/`updatedAt`
- [x] 12. Robots `index,follow` (não bloqueado no robots.txt)
- [x] 13. Slug inexistente → 404 + noindex
- [ ] 14. 301 de artigos duplicados (`GUIA_REDIRECTS` vazio — aguardando decisão de URL oficial)
- [x] 15. Mesmo HTML para Google e visitante; React hidrata via `window.__GUIA_ARTICLE__`

### Prioridade stakeholder
- [x] 1. SSR/prerender `/guia/:slug`
- [x] 2. title + meta description
- [x] 3. canonical
- [x] 4. H1 + conteúdo no HTML inicial
- [x] 5. sitemap atualizado
- [x] 6. Schema Article + Breadcrumb
- [x] 7. 301 `/detalhe/` (já em produção)
- [ ] 8. consolidação de artigos duplicados (pendente decisão)






