interface MailErrorInfo {
  status: number;
  message: string;
  code?: string;
}

export function mapMailError(err: unknown): MailErrorInfo {
  const e = err as { code?: string; responseCode?: number; message?: string };

  if (e.code === 'EAUTH' || e.responseCode === 535) {
    return {
      status: 502,
      code: 'smtp_auth',
      message: 'Falha na autenticação SMTP. Verifique SMTP_USER e SMTP_PASS.',
    };
  }

  if (e.code === 'ECONNECTION' || e.code === 'ECONNREFUSED' || e.code === 'ETIMEDOUT') {
    return {
      status: 502,
      code: 'smtp_connection',
      message: 'Não foi possível conectar ao servidor SMTP. Verifique SMTP_HOST e SMTP_PORT.',
    };
  }

  if (e.code === 'ESOCKET') {
    return {
      status: 502,
      code: 'smtp_tls',
      message: 'Erro de conexão segura. Tente SMTP_SECURE=true com porta 465, ou SMTP_SECURE=false com porta 587.',
    };
  }

  if (e.code === 'EENVELOPE' || e.responseCode === 550) {
    return {
      status: 502,
      code: 'smtp_envelope',
      message: 'E-mail rejeitado pelo servidor. Verifique SMTP_FROM e CONTACT_EMAIL.',
    };
  }

  return {
    status: 500,
    code: 'smtp_unknown',
    message: 'Falha ao enviar e-mail. Tente novamente ou use o WhatsApp.',
  };
}

export function validateSmtpConfig(): string | null {
  if (!process.env.SMTP_HOST?.trim()) {
    return 'SMTP_HOST não configurado.';
  }
  if (!process.env.SMTP_USER?.trim()) {
    return 'SMTP_USER não configurado.';
  }
  if (!process.env.SMTP_PASS?.trim()) {
    return 'SMTP_PASS não configurado. No Cloud Run, use o Secret Manager (scripts/gcp-setup-secrets.sh).';
  }
  return null;
}
