import { Link } from 'react-router-dom';
import { FileText, Scale } from 'lucide-react';
import { LegalPageShell } from '../components/legal/LegalPageShell';

const TOC = [
  { id: 'aceitacao', label: 'Aceitação' },
  { id: 'servicos', label: 'Serviços' },
  { id: 'portal', label: 'Portal do cliente' },
  { id: 'documentacao', label: 'Documentação' },
  { id: 'honorarios', label: 'Honorários' },
  { id: 'propriedade', label: 'Propriedade intelectual' },
  { id: 'responsabilidade', label: 'Limitação de responsabilidade' },
  { id: 'foro', label: 'Foro' },
];

export function TermsPage() {
  return (
    <LegalPageShell
      title="Termos de Uso"
      description="Condições para utilização do site institucional, portal do cliente e serviços de assessoria da Andrade Consultoria e Isenções."
      path="/termos"
      icon={FileText}
      updatedAt="agosto de 2026"
      breadcrumbLabel="Termos de Uso"
      toc={TOC}
      relatedLinks={[
        { to: '/privacidade', label: 'Política de Privacidade' },
        { to: '/cookies', label: 'Política de Cookies' },
      ]}
      callout={
        <>
          <Scale size={20} className="text-accent shrink-0 mt-0.5" aria-hidden />
          <p>
            Ao utilizar o site ou iniciar um processo de isenção PCD, você declara ter lido e concordado com estes
            termos e com nossa <Link to="/privacidade" className="underline font-semibold">Política de Privacidade</Link>.
          </p>
        </>
      }
    >
      <section id="aceitacao">
        <h2>1. Aceitação</h2>
        <p>
          Ao acessar o site <strong>andradeisencoes.com.br</strong>, utilizar o portal do cliente ou contratar nossos
          serviços, você concorda com estes Termos de Uso. Se não concordar, não utilize nossos canais digitais.
        </p>
      </section>

      <section id="servicos">
        <h2>2. Serviços</h2>
        <p>
          A Andrade Consultoria e Isenções presta <strong>assessoria administrativa</strong> para obtenção de isenções
          fiscais (IPI, ICMS, IPVA) na compra de veículos para pessoas com deficiência (PcD). Nosso trabalho consiste
          em orientação, organização documental e protocolo junto aos órgãos competentes.
        </p>
        <p>
          <strong>Não garantimos deferimento</strong> pelos órgãos públicos. Cada caso depende de análise documental,
          perícia médica e decisão administrativa dos entes responsáveis.
        </p>
      </section>

      <section id="portal">
        <h2>3. Portal do cliente</h2>
        <p>
          O acesso ao portal em <strong>/portal/meu-processo</strong> é pessoal e intransferível. Você é responsável por manter sua
          senha em sigilo e por todas as atividades realizadas em sua conta. Notifique-nos imediatamente em caso de
          uso não autorizado.
        </p>
      </section>

      <section id="documentacao">
        <h2>4. Documentação</h2>
        <p>
          É de sua responsabilidade fornecer documentos <strong>verdadeiros, completos e atualizados</strong>. A
          apresentação de documentos falsos ou adulterados pode configurar crime previsto em lei e resultará no
          encerramento imediato do contrato.
        </p>
      </section>

      <section id="honorarios">
        <h2>5. Honorários</h2>
        <p>
          Valores, forma de pagamento e condições são definidos em <strong>contrato específico</strong>, celebrado após
          análise de elegibilidade do beneficiário. Informações no site têm caráter informativo e não vinculam valores
          finais sem proposta formal.
        </p>
      </section>

      <section id="propriedade">
        <h2>6. Propriedade intelectual</h2>
        <p>
          Todo o conteúdo do site — textos, imagens, logotipos, marca <strong>Andrade Isenções®</strong>, materiais
          educativos e templates — é protegido por direitos autorais e não pode ser reproduzido sem autorização prévia
          por escrito.
        </p>
      </section>

      <section id="responsabilidade">
        <h2>7. Limitação de responsabilidade</h2>
        <p>Não nos responsabilizamos por:</p>
        <ul>
          <li>Decisões de órgãos públicos (deferimento, indeferimento ou exigências)</li>
          <li>Prazos governamentais fora de nosso controle</li>
          <li>Indisponibilidade de sistemas externos (Gov.br, SISEN, SEFAZ, DETRAN)</li>
          <li>Informações desatualizadas em sites de terceiros vinculados</li>
        </ul>
      </section>

      <section id="foro">
        <h2>8. Foro</h2>
        <p>
          Fica eleito o foro da comarca de <strong>Cuiabá/MT</strong> para dirimir quaisquer controvérsias decorrentes
          destes termos, com renúncia a qualquer outro, por mais privilegiado que seja.
        </p>
      </section>
    </LegalPageShell>
  );
}
