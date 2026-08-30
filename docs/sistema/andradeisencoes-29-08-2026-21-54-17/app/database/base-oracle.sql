CREATE TABLE arquivo( 
      id number(10)    NOT NULL , 
      id_cliente number(10)    NOT NULL , 
      id_tp_arquivo number(10)    NOT NULL , 
      arquivo varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE arquivo_tipo( 
      id number(10)    NOT NULL , 
      descricao varchar  (30)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE carro( 
      id number(10)    NOT NULL , 
      id_modelo number(10)   , 
      placa varchar  (20)   , 
      renavam varchar(3000)   , 
      chassi varchar(3000)   , 
      tipo_isencao number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE carro_marca( 
      id number(10)    NOT NULL , 
      descricao varchar  (30)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE carro_modelo( 
      id number(10)    NOT NULL , 
      modelo varchar  (30)    NOT NULL , 
      marca number(10)    NOT NULL , 
 PRIMARY KEY (id)) ; 

CREATE TABLE cliente( 
      id number(10)    NOT NULL , 
      nome varchar(3000)   , 
      cpf varchar  (14)   , 
      rg varchar(3000)   , 
      rg_estado number(10)   , 
      rg_orgao_emissor varchar  (255)   , 
      rg_data_emissao date   , 
      email varchar  (30)   , 
      endereco varchar(3000)   , 
      numero varchar  (30)   , 
      complemento varchar(3000)   , 
      bairro varchar(3000)   , 
      cep varchar  (10)   , 
      municipio number(10)   , 
      estado number(10)   , 
      id_representante number(10)   , 
      telefone varchar  (13)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE condutor( 
      id number(10)    NOT NULL , 
      descricao varchar  (20)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE condutor_autorizado( 
      id number(10)    NOT NULL , 
      nome varchar(3000)   , 
      cpf varchar  (14)   , 
      rg varchar(3000)   , 
      rg_estado number(10)   , 
      rg_orgao_emissor varchar  (255)   , 
      rg_data_emissao date   , 
      email varchar  (30)   , 
      endereco varchar(3000)   , 
      numero varchar  (30)   , 
      complemento varchar(3000)   , 
      bairro varchar(3000)   , 
      cep varchar  (10)   , 
      municipio number(10)   , 
      estado number(10)   , 
      id_cliente number(10)   , 
      condutor number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE contrato( 
      id number(10)    NOT NULL , 
      id_cliente number(10)   , 
      id_carro number(10)   , 
      data_contrato date   , 
      pagamento_tipo number(10)   , 
      pagamento_status number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE estado( 
      id number(10)    NOT NULL , 
      estado varchar  (30)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE isencao_ipva( 
      id number(10)    NOT NULL , 
      descricao varchar(3000)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE municipio( 
      id number(10)    NOT NULL , 
      municipio varchar  (100)   , 
      id_estado number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pagamento_arquivos( 
      id number(10)    NOT NULL , 
      arquivo varchar  (200)   , 
      id_contrato number(10)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pagamento_status( 
      id number(10)    NOT NULL , 
      descricao varchar  (50)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pagamento_tipo( 
      id number(10)    NOT NULL , 
      descricao varchar  (50)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE representante_legal( 
      id number(10)    NOT NULL , 
      nome varchar(3000)   , 
      cpf varchar  (14)   , 
      rg varchar(3000)   , 
      rg_estado number(10)   , 
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
 CREATE SEQUENCE arquivo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER arquivo_id_seq_tr 

BEFORE INSERT ON arquivo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT arquivo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE arquivo_tipo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER arquivo_tipo_id_seq_tr 

BEFORE INSERT ON arquivo_tipo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT arquivo_tipo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE carro_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER carro_id_seq_tr 

BEFORE INSERT ON carro FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT carro_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE carro_marca_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER carro_marca_id_seq_tr 

BEFORE INSERT ON carro_marca FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT carro_marca_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE carro_modelo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER carro_modelo_id_seq_tr 

BEFORE INSERT ON carro_modelo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT carro_modelo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE cliente_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER cliente_id_seq_tr 

BEFORE INSERT ON cliente FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT cliente_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE condutor_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER condutor_id_seq_tr 

BEFORE INSERT ON condutor FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT condutor_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE condutor_autorizado_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER condutor_autorizado_id_seq_tr 

BEFORE INSERT ON condutor_autorizado FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT condutor_autorizado_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE contrato_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER contrato_id_seq_tr 

BEFORE INSERT ON contrato FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT contrato_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE estado_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER estado_id_seq_tr 

BEFORE INSERT ON estado FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT estado_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE isencao_ipva_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER isencao_ipva_id_seq_tr 

BEFORE INSERT ON isencao_ipva FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT isencao_ipva_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE municipio_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER municipio_id_seq_tr 

BEFORE INSERT ON municipio FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT municipio_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE pagamento_arquivos_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER pagamento_arquivos_id_seq_tr 

BEFORE INSERT ON pagamento_arquivos FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT pagamento_arquivos_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE pagamento_status_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER pagamento_status_id_seq_tr 

BEFORE INSERT ON pagamento_status FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT pagamento_status_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE pagamento_tipo_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER pagamento_tipo_id_seq_tr 

BEFORE INSERT ON pagamento_tipo FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT pagamento_tipo_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
CREATE SEQUENCE representante_legal_id_seq START WITH 1 INCREMENT BY 1; 

CREATE OR REPLACE TRIGGER representante_legal_id_seq_tr 

BEFORE INSERT ON representante_legal FOR EACH ROW 

    WHEN 

        (NEW.id IS NULL) 

    BEGIN 

        SELECT representante_legal_id_seq.NEXTVAL INTO :NEW.id FROM DUAL; 

END;
 