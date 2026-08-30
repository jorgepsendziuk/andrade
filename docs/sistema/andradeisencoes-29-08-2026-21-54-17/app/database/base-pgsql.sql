CREATE TABLE arquivo( 
      id  SERIAL    NOT NULL  , 
      id_cliente integer   NOT NULL  , 
      id_tp_arquivo integer   NOT NULL  , 
      arquivo text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE arquivo_tipo( 
      id  SERIAL    NOT NULL  , 
      descricao varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE carro( 
      id  SERIAL    NOT NULL  , 
      id_modelo integer   , 
      placa varchar  (20)   , 
      renavam text   , 
      chassi text   , 
      tipo_isencao integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE carro_marca( 
      id  SERIAL    NOT NULL  , 
      descricao varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE carro_modelo( 
      id  SERIAL    NOT NULL  , 
      modelo varchar  (30)   NOT NULL  , 
      marca integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE cliente( 
      id  SERIAL    NOT NULL  , 
      nome text   , 
      cpf varchar  (14)   , 
      rg text   , 
      rg_estado integer   , 
      rg_orgao_emissor varchar  (255)   , 
      rg_data_emissao date   , 
      email varchar  (30)   , 
      endereco text   , 
      numero varchar  (30)   , 
      complemento text   , 
      bairro text   , 
      cep varchar  (10)   , 
      municipio integer   , 
      estado integer   , 
      id_representante integer   , 
      telefone varchar  (13)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE condutor( 
      id  SERIAL    NOT NULL  , 
      descricao varchar  (20)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE condutor_autorizado( 
      id  SERIAL    NOT NULL  , 
      nome text   , 
      cpf varchar  (14)   , 
      rg text   , 
      rg_estado integer   , 
      rg_orgao_emissor varchar  (255)   , 
      rg_data_emissao date   , 
      email varchar  (30)   , 
      endereco text   , 
      numero varchar  (30)   , 
      complemento text   , 
      bairro text   , 
      cep varchar  (10)   , 
      municipio integer   , 
      estado integer   , 
      id_cliente integer   , 
      condutor integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato( 
      id  SERIAL    NOT NULL  , 
      id_cliente integer   , 
      id_carro integer   , 
      data_contrato date   , 
      pagamento_tipo integer   , 
      pagamento_status integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE estado( 
      id  SERIAL    NOT NULL  , 
      estado varchar  (30)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE isencao_ipva( 
      id  SERIAL    NOT NULL  , 
      descricao text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE municipio( 
      id  SERIAL    NOT NULL  , 
      municipio varchar  (100)   , 
      id_estado integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pagamento_arquivos( 
      id  SERIAL    NOT NULL  , 
      arquivo varchar  (200)   , 
      id_contrato integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pagamento_status( 
      id  SERIAL    NOT NULL  , 
      descricao varchar  (50)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pagamento_tipo( 
      id  SERIAL    NOT NULL  , 
      descricao varchar  (50)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE representante_legal( 
      id  SERIAL    NOT NULL  , 
      nome text   , 
      cpf varchar  (14)   , 
      rg text   , 
      rg_estado integer   , 
      rg_orgao_emissor varchar  (255)   , 
      telefone varchar  (13)   , 
 PRIMARY KEY (id)) ; 

 
  
 ALTER TABLE arquivo ADD CONSTRAINT fk_arquivo_1 FOREIGN KEY (id_cliente) references cliente(id); 
ALTER TABLE arquivo ADD CONSTRAINT fk_arquivo_2 FOREIGN KEY (id_tp_arquivo) references arquivo_tipo(id); 
ALTER TABLE carro ADD CONSTRAINT fk_carro_1 FOREIGN KEY (id_modelo) references carro_modelo(id); 
ALTER TABLE carro ADD CONSTRAINT fk_carro_2 FOREIGN KEY (tipo_isencao) references isencao_ipva(id); 
ALTER TABLE carro_modelo ADD CONSTRAINT fk_carro_modelo_1 FOREIGN KEY (marca) references carro_marca(id); 
ALTER TABLE cliente ADD CONSTRAINT fk_cliente_1 FOREIGN KEY (municipio) references municipio(id); 
ALTER TABLE cliente ADD CONSTRAINT fk_cliente_2 FOREIGN KEY (estado) references estado(id); 
ALTER TABLE cliente ADD CONSTRAINT fk_cliente_3 FOREIGN KEY (rg_estado) references estado(id); 
ALTER TABLE cliente ADD CONSTRAINT fk_cliente_4 FOREIGN KEY (id_representante) references representante_legal(id); 
ALTER TABLE condutor_autorizado ADD CONSTRAINT fk_condutor_autorizado_1 FOREIGN KEY (estado) references estado(id); 
ALTER TABLE condutor_autorizado ADD CONSTRAINT fk_condutor_autorizado_2 FOREIGN KEY (municipio) references municipio(id); 
ALTER TABLE condutor_autorizado ADD CONSTRAINT fk_condutor_autorizado_3 FOREIGN KEY (rg_estado) references estado(id); 
ALTER TABLE condutor_autorizado ADD CONSTRAINT fk_condutor_autorizado_4 FOREIGN KEY (id_cliente) references cliente(id); 
ALTER TABLE condutor_autorizado ADD CONSTRAINT fk_condutor_autorizado_5 FOREIGN KEY (condutor) references condutor(id); 
ALTER TABLE contrato ADD CONSTRAINT fk_contrato_1 FOREIGN KEY (id_cliente) references cliente(id); 
ALTER TABLE contrato ADD CONSTRAINT fk_contrato_2 FOREIGN KEY (id_carro) references carro(id); 
ALTER TABLE contrato ADD CONSTRAINT fk_contrato_3 FOREIGN KEY (pagamento_tipo) references pagamento_tipo(id); 
ALTER TABLE contrato ADD CONSTRAINT fk_contrato_4 FOREIGN KEY (pagamento_status) references pagamento_status(id); 
ALTER TABLE municipio ADD CONSTRAINT fk_municipio_1 FOREIGN KEY (id_estado) references estado(id); 
ALTER TABLE pagamento_arquivos ADD CONSTRAINT fk_pagamento_arquivos_1 FOREIGN KEY (id_contrato) references contrato(id); 
ALTER TABLE representante_legal ADD CONSTRAINT fk_representante_legal_1 FOREIGN KEY (rg_estado) references estado(id); 
 
 CREATE index idx_arquivo_id_cliente on arquivo(id_cliente); 
CREATE index idx_arquivo_id_tp_arquivo on arquivo(id_tp_arquivo); 
CREATE index idx_carro_id_modelo on carro(id_modelo); 
CREATE index idx_carro_tipo_isencao on carro(tipo_isencao); 
CREATE index idx_carro_modelo_marca on carro_modelo(marca); 
CREATE index idx_cliente_municipio on cliente(municipio); 
CREATE index idx_cliente_estado on cliente(estado); 
CREATE index idx_cliente_rg_estado on cliente(rg_estado); 
CREATE index idx_cliente_id_representante on cliente(id_representante); 
CREATE index idx_condutor_autorizado_estado on condutor_autorizado(estado); 
CREATE index idx_condutor_autorizado_municipio on condutor_autorizado(municipio); 
CREATE index idx_condutor_autorizado_rg_estado on condutor_autorizado(rg_estado); 
CREATE index idx_condutor_autorizado_id_cliente on condutor_autorizado(id_cliente); 
CREATE index idx_condutor_autorizado_condutor on condutor_autorizado(condutor); 
CREATE index idx_contrato_id_cliente on contrato(id_cliente); 
CREATE index idx_contrato_id_carro on contrato(id_carro); 
CREATE index idx_contrato_pagamento_tipo on contrato(pagamento_tipo); 
CREATE index idx_contrato_pagamento_status on contrato(pagamento_status); 
CREATE index idx_municipio_id_estado on municipio(id_estado); 
CREATE index idx_pagamento_arquivos_id_contrato on pagamento_arquivos(id_contrato); 
CREATE index idx_representante_legal_rg_estado on representante_legal(rg_estado); 
