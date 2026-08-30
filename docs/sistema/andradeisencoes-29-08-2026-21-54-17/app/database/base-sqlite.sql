PRAGMA foreign_keys=OFF; 

CREATE TABLE arquivo( 
      id  INTEGER    NOT NULL  , 
      id_cliente int   NOT NULL  , 
      id_tp_arquivo int   NOT NULL  , 
      arquivo text   , 
 PRIMARY KEY (id),
FOREIGN KEY(id_cliente) REFERENCES cliente(id),
FOREIGN KEY(id_tp_arquivo) REFERENCES arquivo_tipo(id)) ; 

CREATE TABLE arquivo_tipo( 
      id  INTEGER    NOT NULL  , 
      descricao varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE carro( 
      id  INTEGER    NOT NULL  , 
      id_modelo int   , 
      placa varchar  (20)   , 
      renavam text   , 
      chassi text   , 
      tipo_isencao int   , 
 PRIMARY KEY (id),
FOREIGN KEY(id_modelo) REFERENCES carro_modelo(id),
FOREIGN KEY(tipo_isencao) REFERENCES isencao_ipva(id)) ; 

CREATE TABLE carro_marca( 
      id  INTEGER    NOT NULL  , 
      descricao varchar  (30)   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE carro_modelo( 
      id  INTEGER    NOT NULL  , 
      modelo varchar  (30)   NOT NULL  , 
      marca int   NOT NULL  , 
 PRIMARY KEY (id),
FOREIGN KEY(marca) REFERENCES carro_marca(id)) ; 

CREATE TABLE cliente( 
      id  INTEGER    NOT NULL  , 
      nome text   , 
      cpf varchar  (14)   , 
      rg text   , 
      rg_estado int   , 
      rg_orgao_emissor varchar  (255)   , 
      rg_data_emissao date   , 
      email varchar  (30)   , 
      endereco text   , 
      numero varchar  (30)   , 
      complemento text   , 
      bairro text   , 
      cep varchar  (10)   , 
      municipio int   , 
      estado int   , 
      id_representante int   , 
      telefone varchar  (13)   , 
 PRIMARY KEY (id),
FOREIGN KEY(municipio) REFERENCES municipio(id),
FOREIGN KEY(estado) REFERENCES estado(id),
FOREIGN KEY(rg_estado) REFERENCES estado(id),
FOREIGN KEY(id_representante) REFERENCES representante_legal(id)) ; 

CREATE TABLE condutor( 
      id  INTEGER    NOT NULL  , 
      descricao varchar  (20)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE condutor_autorizado( 
      id  INTEGER    NOT NULL  , 
      nome text   , 
      cpf varchar  (14)   , 
      rg text   , 
      rg_estado int   , 
      rg_orgao_emissor varchar  (255)   , 
      rg_data_emissao date   , 
      email varchar  (30)   , 
      endereco text   , 
      numero varchar  (30)   , 
      complemento text   , 
      bairro text   , 
      cep varchar  (10)   , 
      municipio int   , 
      estado int   , 
      id_cliente int   , 
      condutor int   , 
 PRIMARY KEY (id),
FOREIGN KEY(estado) REFERENCES estado(id),
FOREIGN KEY(municipio) REFERENCES municipio(id),
FOREIGN KEY(rg_estado) REFERENCES estado(id),
FOREIGN KEY(id_cliente) REFERENCES cliente(id),
FOREIGN KEY(condutor) REFERENCES condutor(id)) ; 

CREATE TABLE contrato( 
      id  INTEGER    NOT NULL  , 
      id_cliente int   , 
      id_carro int   , 
      data_contrato date   , 
      pagamento_tipo int   , 
      pagamento_status int   , 
 PRIMARY KEY (id),
FOREIGN KEY(id_cliente) REFERENCES cliente(id),
FOREIGN KEY(id_carro) REFERENCES carro(id),
FOREIGN KEY(pagamento_tipo) REFERENCES pagamento_tipo(id),
FOREIGN KEY(pagamento_status) REFERENCES pagamento_status(id)) ; 

CREATE TABLE estado( 
      id  INTEGER    NOT NULL  , 
      estado varchar  (30)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE isencao_ipva( 
      id  INTEGER    NOT NULL  , 
      descricao text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE municipio( 
      id  INTEGER    NOT NULL  , 
      municipio varchar  (100)   , 
      id_estado int   , 
 PRIMARY KEY (id),
FOREIGN KEY(id_estado) REFERENCES estado(id)) ; 

CREATE TABLE pagamento_arquivos( 
      id  INTEGER    NOT NULL  , 
      arquivo varchar  (200)   , 
      id_contrato int   , 
 PRIMARY KEY (id),
FOREIGN KEY(id_contrato) REFERENCES contrato(id)) ; 

CREATE TABLE pagamento_status( 
      id  INTEGER    NOT NULL  , 
      descricao varchar  (50)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE pagamento_tipo( 
      id  INTEGER    NOT NULL  , 
      descricao varchar  (50)   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE representante_legal( 
      id  INTEGER    NOT NULL  , 
      nome text   , 
      cpf varchar  (14)   , 
      rg text   , 
      rg_estado int   , 
      rg_orgao_emissor varchar  (255)   , 
      telefone varchar  (13)   , 
 PRIMARY KEY (id),
FOREIGN KEY(rg_estado) REFERENCES estado(id)) ; 

 
 