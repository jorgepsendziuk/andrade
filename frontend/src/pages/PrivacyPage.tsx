import { Link } from 'react-router-dom';
import { Shield } from 'lucide-react';
import { LegalPageShell } from '../components/legal/LegalPageShell';

const TOC = [
  { id: 'introducao', label: 'Introdução' },
  { id: 'dados-coletados', label: 'Dados coletados' },
  { id: 'finalidade', label: 'Finalidade' },
  { id: 'base-legal', label: 'Base legal' },
  { id: 'compartilhamento', label: 'Compartilhamento' },
  { id: 'seguranca', label: 'Segurança' },
  { id: 'direitos', label: 'Seus direitos' },
  { id: 'retencao', label: 'Retenção' },
  { id: 'encarregado', label: 'Encarregado (DPO)' },
];

export function PrivacyPage() {
  return (
    <LegalPageShell
      title="Política de Privacidade"
      description="Como a Andrade Consultoria e Isenções coleta, usa e protege seus dados pessoais em conformidade com a LGPD."
      path="/privacidade"
      icon={Shield}
      updatedAt="agosto de 2026"
      breadcrumbLabel="Privacidade"
      toc={TOC}
      relatedLinks={[
        { to: '/cookies', label: 'Política de Cookies' },
        { to: '/termos', label: 'Termos de Uso' },
      ]}
      callout={
        <>
          <Shield size={20} className="text-accent shrink-0 mt-0.5" aria-hidden />
          <p>
            Tratamos dados pessoais e sensíveis (incluindo laudos médicos) com acesso restrito, armazenamento privado
            e auditoria de downloads. Você pode exercer seus direitos a qualquer momento pelo e-mail abaixo.
          </p>
        </>
      }
    >
      <section id="introducao">
        <h2>Introdução</h2>
        <p>
          A <strong>Andrade Consultoria e Isenções</strong> trata dados pessoais em conformidade com a{' '}
          <strong>Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong>. Esta política descreve como
          coletamos, utilizamos e protegemos suas informações ao utilizar nosso site, portal do cliente e serviços de
          assessoria para isenções fiscais na compra de veículos PcD.
        </p>
      </section>

      <section id="dados-coletados">
        <h2>1. Dados coletados</h2>
        <p>Podemos tratar as seguintes categorias de dados, conforme a etapa do seu processo:</p>
        <ul>
          <li>
            <strong>Dados de identificação:</strong> nome completo, CPF, RG, endereço, telefone e e-mail
          </li>
          <li>
            <strong>Dados de saúde:</strong> laudos médicos e documentação para comprovação de deficiência (dados
            sensíveis, tratados com proteção reforçada)
          </li>
          <li>
            <strong>Documentos:</strong> CNH, comprovantes de residência, alvará de curatela quando aplicável,
            comprovantes de pagamento
          </li>
          <li>
            <strong>Representante legal:</strong> dados do tutor ou curador, quando houver
          </li>
          <li>
            <strong>Navegação:</strong> cookies analíticos (Google Analytics), somente com seu consentimento — veja a{' '}
            <Link to="/cookies">Política de Cookies</Link>
          </li>
        </ul>
      </section>

      <section id="finalidade">
        <h2>2. Finalidade do tratamento</h2>
        <p>Utilizamos seus dados para:</p>
        <ul>
          <li>Prestar assessoria na obtenção de isenções de IPI, ICMS e IPVA na compra de veículo PcD</li>
          <li>Protocolar e acompanhar processos junto à Receita Federal (SISEN), SEFAZ/MT e DETRAN</li>
          <li>Gerenciar sua conta no portal do cliente (etapas, documentos e comunicações)</li>
          <li>Cumprir obrigações contratuais, legais e regulatórias</li>
          <li>Melhorar nosso site e serviços (dados agregados e anonimizados, quando aplicável)</li>
        </ul>
      </section>

      <section id="base-legal">
        <h2>3. Base legal</h2>
        <p>O tratamento de dados fundamenta-se em:</p>
        <ul>
          <li><strong>Execução de contrato</strong> — prestação dos serviços de assessoria contratados</li>
          <li><strong>Consentimento</strong> — cadastro no portal, envio de documentos e cookies analíticos</li>
          <li><strong>Obrigação legal/regulatória</strong> — exigências de órgãos públicos no curso do processo</li>
          <li><strong>Legítimo interesse</strong> — segurança do sistema e prevenção a fraudes, quando cabível</li>
        </ul>
      </section>

      <section id="compartilhamento">
        <h2>4. Compartilhamento</h2>
        <p>
          Seus dados podem ser compartilhados com órgãos públicos (DETRAN, SEFAZ, Receita Federal), concessionárias
          e parceiros estritamente necessários à condução do processo de isenção. Não vendemos nem alugamos dados
          pessoais. O compartilhamento ocorre sempre na medida do necessário e com medidas de segurança adequadas.
        </p>
      </section>

      <section id="seguranca">
        <h2>5. Armazenamento e segurança</h2>
        <p>
          Documentos sensíveis são armazenados em <strong>ambiente privado</strong> (bucket com acesso restrito no
          Google Cloud), com URLs de download <strong>temporárias e assinadas</strong>. Registramos acessos e
          downloads em log de auditoria. Senhas são armazenadas com hash criptográfico. A comunicação com o site
          utiliza HTTPS.
        </p>
      </section>

      <section id="direitos">
        <h2>6. Seus direitos (titular)</h2>
        <p>Conforme a LGPD, você pode solicitar:</p>
        <ul>
          <li>Confirmação da existência de tratamento e acesso aos dados</li>
          <li>Correção de dados incompletos, inexatos ou desatualizados</li>
          <li>Anonimização, bloqueio ou eliminação de dados desnecessários</li>
          <li>Portabilidade dos dados a outro fornecedor</li>
          <li>Revogação do consentimento, quando esta for a base legal</li>
        </ul>
        <p>
          Para exercer seus direitos, envie e-mail para{' '}
          <a href="mailto:comercial@andradeisencoes.com.br">comercial@andradeisencoes.com.br</a> ou utilize o{' '}
          <Link to="/#contato">formulário de contato</Link> do site.
        </p>
      </section>

      <section id="retencao">
        <h2>7. Retenção</h2>
        <p>
          Os dados são mantidos pelo tempo necessário à prestação do serviço e ao cumprimento de obrigações legais,
          geralmente por até <strong>5 anos</strong> após o encerramento do processo, salvo determinação legal em
          contrário.
        </p>
      </section>

      <section id="encarregado">
        <h2>8. Encarregado (DPO)</h2>
        <p>
          Para questões relacionadas à proteção de dados pessoais, entre em contato com nosso encarregado pelo
          e-mail{' '}
          <a href="mailto:comercial@andradeisencoes.com.br">comercial@andradeisencoes.com.br</a>.
        </p>
      </section>
    </LegalPageShell>
  );
}
