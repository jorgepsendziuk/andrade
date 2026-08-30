CREATE TABLE arquivo( 
      id  INT IDENTITY    NOT NULL  , 
      id_cliente int   NOT NULL  , 
      id_tp_arquivo int   NOT NULL  , 
      arquivo nvarchar(max)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE arquivo_tipo( 
      id  INT IDENTITY    NOT NULL  , 
      descricao varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE carro( 
      id  INT IDENTITY    NOT NULL  , 
      id_modelo int   , 
      placa varchar  (20)   , 
      renavam nvarchar(max)   , 
      chassi nvarchar(max)   , 
      tipo_isencao int   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE carro_marca( 
      id  INT IDENTITY    NOT NULL  , 
      descricao varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE carro_modelo( 
      id  INT IDENTITY    NOT NULL  , 
      modelo varchar  (30)   NOT NULL  , 
      marca int   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE cliente( 
      id  INT IDENTITY    NOT NULL  , 
      nome nvarchar(max)   , 
      cpf varchar  (14)   , 
      rg nvarchar(max)   , 
      rg_estado int   , 
      rg_orgao_emissor varchar  (255)   , 
      rg_data_emissao date   , 
      email varchar  (30)   , 
      endereco nvarchar(max)   , 
      numero varchar  (30)   , 
      complemento nvarchar(max)   , 
      bairro nvarchar(max)   , 
      cep varchar  (10)   , 
      municipio int   , 
      estado int   , 
      id_representante int   , 
      telefone varchar  (13)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE condutor( 
      id  INT IDENTITY    NOT NULL  , 
      descricao varchar  (20)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE condutor_autorizado( 
      id  INT IDENTITY    NOT NULL  , 
      nome nvarchar(max)   , 
      cpf varchar  (14)   , 
      rg nvarchar(max)   , 
      rg_estado int   , 
      rg_orgao_emissor varchar  (255)   , 
      rg_data_emissao date   , 
      email varchar  (30)   , 
      endereco nvarchar(max)   , 
      numero varchar  (30)   , 
      complemento nvarchar(max)   , 
      bairro nvarchar(max)   , 
      cep varchar  (10)   , 
      municipio int   , 
      estado int   , 
      id_cliente int   , 
      condutor int   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato( 
      id  INT IDENTITY    NOT NULL  , 
      id_cliente int   , 
      id_carro int   , 
      data_contrato date   , 
      pagamento_tipo int   , 
      pagamento_status int   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE estado( 
      id  INT IDENTITY    NOT NULL  , 
      estado varchar  (30)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE isencao_ipva( 
      id  INT IDENTITY    NOT NULL  , 
      descricao nvarchar(max)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE municipio( 
      id  INT IDENTITY    NOT NULL  , 
      municipio varchar  (100)   , 
      id_estado int   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pagamento_arquivos( 
      id  INT IDENTITY    NOT NULL  , 
      arquivo varchar  (200)   , 
      id_contrato int   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pagamento_status( 
      id  INT IDENTITY    NOT NULL  , 
      descricao varchar  (50)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pagamento_tipo( 
      id  INT IDENTITY    NOT NULL  , 
      descricao varchar  (50)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE representante_legal( 
      id  INT IDENTITY    NOT NULL  , 
      nome nvarchar(max)   , 
      cpf varchar  (14)   , 
      rg nvarchar(max)   , 
      rg_estado int   , 
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
