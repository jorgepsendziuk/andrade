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