import { Link } from 'react-router-dom';
import { Cookie, Info } from 'lucide-react';
import { LegalPageShell } from '../components/legal/LegalPageShell';

const TOC = [
  { id: 'o-que-sao', label: 'O que são cookies?' },
  { id: 'cookies-utilizados', label: 'Cookies que utilizamos' },
  { id: 'essenciais', label: 'Cookies essenciais' },
  { id: 'analiticos', label: 'Cookies analíticos' },
  { id: 'gerenciar', label: 'Como gerenciar' },
  { id: 'contato', label: 'Contato' },
];

export function CookiesPage() {
  return (
    <LegalPageShell
      title="Política de Cookies"
      description="Entenda quais cookies utilizamos no site da Andrade Isenções, para que servem e como você pode gerenciar suas preferências."
      path="/cookies"
      icon={Cookie}
      updatedAt="agosto de 2026"
      breadcrumbLabel="Cookies"
      toc={TOC}
      relatedLinks={[
        { to: '/privacidade', label: 'Política de Privacidade' },
        { to: '/termos', label: 'Termos de Uso' },
      ]}
      callout={
        <>
          <Info size={20} className="text-accent shrink-0 mt-0.5" aria-hidden />
          <p>
            Usamos cookies <strong>essenciais</strong> para o funcionamento do site e, apenas com seu consentimento,
            cookies <strong>analíticos</strong> (Google Analytics e medição de campanhas Google Ads) para entender como o site é utilizado e melhorar
            nossos serviços.
          </p>
        </>
      }
    >
      <section id="o-que-sao">
        <h2>O que são cookies?</h2>
        <p>
          Cookies são pequenos arquivos de texto armazenados no seu navegador quando você visita um site. Eles permitem
          que o site lembre preferências, mantenha sua sessão e, com autorização, colete informações estatísticas de
          uso. Para mais detalhes sobre como tratamos dados pessoais, consulte nossa{' '}
          <Link to="/privacidade">Política de Privacidade</Link>.
        </p>
      </section>

      <section id="cookies-utilizados">
        <h2>Cookies que utilizamos</h2>
        <p>Resumo dos cookies e tecnologias semelhantes em uso no site:</p>
        <table>
          <thead>
            <tr>
              <th>Nome</th>
              <th>Tipo</th>
              <th>Finalidade</th>
              <th>Duração</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><code className="text-xs bg-brand-50 px-1.5 py-0.5 rounded">andrade_cookie_consent</code></td>
              <td>Essencial</td>
              <td>Registra se você aceitou ou recusou cookies analíticos</td>
              <td>1 ano</td>
            </tr>
            <tr>
              <td><code className="text-xs bg-brand-50 px-1.5 py-0.5 rounded">_ga</code></td>
              <td>Analítico</td>
              <td>Google Analytics — distingue usuários (somente com consentimento)</td>
              <td>2 anos</td>
            </tr>
            <tr>
              <td><code className="text-xs bg-brand-50 px-1.5 py-0.5 rounded">_ga_*</code></td>
              <td>Analítico</td>
              <td>Google Analytics 4 — mantém estado da sessão (somente com consentimento)</td>
              <td>2 anos</td>
            </tr>
          </tbody>
        </table>
      </section>

      <section id="essenciais">
        <h2>Cookies essenciais</h2>
        <p>
          São necessários para o funcionamento básico do site e do portal do cliente. Incluem a gravação da sua
          preferência de consentimento de cookies. <strong>Não exigem consentimento</strong> prévio, pois são
          indispensáveis para o serviço solicitado.
        </p>
      </section>

      <section id="analiticos">
        <h2>Cookies analíticos</h2>
        <p>
          Utilizamos o <strong>Google Analytics 4</strong> e o <strong>Google Ads</strong> para medir visitas, páginas mais acessadas,
          origem do tráfego e conversões de campanhas (como cliques no WhatsApp e envio do formulário de contato). Esses cookies{' '}
          <strong>só são ativados após você clicar em &quot;Aceitar&quot;</strong> no banner de cookies exibido na primeira visita.
          Se recusar, o Analytics e o acompanhamento de anúncios não serão carregados.
        </p>
        <p>
          Os dados coletados pelo Google são tratados de forma agregada. Para mais informações, consulte a{' '}
          <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer">
            política de privacidade do Google
          </a>
          .
        </p>
      </section>

      <section id="gerenciar">
        <h2>Como gerenciar seus cookies</h2>
        <p>Você pode controlar cookies das seguintes formas:</p>
        <ul>
          <li>
            <strong>Banner do site:</strong> na primeira visita, escolha &quot;Aceitar&quot; ou &quot;Recusar&quot;
            cookies analíticos
          </li>
          <li>
            <strong>Navegador:</strong> limpe ou bloqueie cookies nas configurações do Chrome, Firefox, Safari ou Edge
          </li>
          <li>
            <strong>Extensões:</strong> ferramentas de bloqueio de rastreamento podem impedir cookies de terceiros
          </li>
        </ul>
        <p>
          Ao bloquear cookies essenciais, algumas funcionalidades do portal podem deixar de funcionar corretamente.
        </p>
      </section>

      <section id="contato">
        <h2>Contato</h2>
        <p>
          Dúvidas sobre esta política ou sobre o tratamento de dados relacionados a cookies:{' '}
          <a href="mailto:comercial@andradeisencoes.com.br">comercial@andradeisencoes.com.br</a>
        </p>
      </section>
    </LegalPageShell>
  );
}
