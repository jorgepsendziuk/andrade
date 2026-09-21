export const CLIENT_FIELD_LABELS: Record<string, string> = {
  name: 'Nome',
  email: 'E-mail',
  cpf: 'CPF',
  rg: 'RG',
  rgEstado: 'UF do RG',
  rgOrgaoEmissor: 'Órgão emissor',
  rgDataEmissao: 'Data de emissão do RG',
  phone: 'Telefone',
  endereco: 'Endereço',
  numero: 'Número',
  complemento: 'Complemento',
  bairro: 'Bairro',
  cep: 'CEP',
  cidade: 'Cidade',
  uf: 'UF',
  genero: 'Gênero',
  representante: 'Representante legal',
  active: 'Cadastro ativo',
  vehicle: 'Veículo',
};

export function labelFields(fields: string[]): string {
  return fields.map((field) => CLIENT_FIELD_LABELS[field] || field).join(', ');
}
