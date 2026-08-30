# Mapeamento de Dados — LGPD

**Responsável:** Andrade Consultoria e Isenções  
**Contato DPO:** comercial@andradeisencoes.com.br  
**Última revisão:** agosto/2026

## Dados tratados

| Categoria | Exemplos | Finalidade | Base legal | Retenção |
|-----------|----------|------------|------------|----------|
| Identificação | Nome, CPF, RG, endereço, telefone, e-mail | Cadastro, contrato, petições | Execução de contrato / consentimento | 5 anos após encerramento |
| Saúde | Laudo médico, CID | Comprovação de elegibilidade PcD | Consentimento explícito | 5 anos após encerramento |
| Documentos | CNH, comprovante residência, alvará | Protocolo em órgãos públicos | Execução de contrato | 5 anos após encerramento |
| Financeiro | Honorários, comprovante pagamento | Cobrança e recibo | Execução de contrato | 5 anos (fiscal) |
| Navegação | IP, cookies GA4 (com consentimento) | Métricas do site | Consentimento | Conforme política de cookies |
| Operacional | Etapas do processo, protocolos | Acompanhamento interno | Legítimo interesse / contrato | 5 anos após encerramento |

## Sistemas e armazenamento

| Sistema | Dados | Localização |
|---------|-------|-------------|
| Firestore | Cadastros, processos, metadados de arquivos, audit logs | GCP (southamerica-east1) |
| GCS `andrade-docs` | Documentos sensíveis (privado) | GCP |
| GCS `andrade-media` | Imagens públicas do site | GCP (público) |
| SMTP | E-mails de contato e boas-vindas | Servidor configurado no painel |

## Compartilhamento

- **DETRAN/MT:** perícia médica, junta especial
- **Receita Federal:** IPI via SISEN/Gov.br
- **SEFAZ/MT e SP:** ICMS, IPVA
- **Concessionárias:** quando necessário à compra do veículo

## Medidas de segurança

- Bucket de documentos **sem acesso público**
- URLs assinadas com TTL de 15 minutos
- Audit log em visualização/download de arquivos sensíveis
- Autenticação JWT com roles (cliente / equipe)
- Senhas com bcrypt

## Direitos do titular

Solicitações de acesso, correção ou exclusão: comercial@andradeisencoes.com.br ou formulário de contato do site.

## Cookies

Ver [Política de Cookies](/cookies). GA4 só carrega após consentimento no banner.
