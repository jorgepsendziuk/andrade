<?php

class ClienteForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'base';
    private static $activeRecord = 'Cliente';
    private static $primaryKey = 'id';
    private static $formName = 'form_Cliente';

    use Adianti\Base\AdiantiMasterDetailTrait;
    use Adianti\Base\AdiantiFileSaveTrait;

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Cadastrar Cliente");

        $criteria_estado = new TCriteria();
        $criteria_municipio = new TCriteria();
        $criteria_arquivo_cliente_id_tp_arquivo = new TCriteria();
        $criteria_condutor_autorizado_cliente_rg_estado = new TCriteria();
        $criteria_condutor_autorizado_cliente_municipio = new TCriteria();
        $criteria_condutor_autorizado_cliente_estado = new TCriteria();
        $criteria_condutor_autorizado_cliente_condutor = new TCriteria();
        $criteria_id_representante = new TCriteria();

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $cpf = new TEntry('cpf');
        $rg = new TEntry('rg');
        $endereco = new TEntry('endereco');
        $numero = new TEntry('numero');
        $complemento = new TEntry('complemento');
        $bairro = new TEntry('bairro');
        $cep = new TEntry('cep');
        $email = new TEntry('email');
        $estado = new TDBCombo('estado', 'base', 'Estado', 'id', '{estado}','id asc' , $criteria_estado );
        $municipio = new TDBUniqueSearch('municipio', 'base', 'Municipio', 'id', 'municipio','id_estado asc' , $criteria_municipio );
        $arquivo_cliente_id_tp_arquivo = new TDBCombo('arquivo_cliente_id_tp_arquivo', 'base', 'ArquivoTipo', 'id', '{descricao}','id asc' , $criteria_arquivo_cliente_id_tp_arquivo );
        $button_cadastrar_tipo_de_arquivo_arquivo_cliente = new TButton('button_cadastrar_tipo_de_arquivo_arquivo_cliente');
        $arquivo_cliente_arquivo = new TFile('arquivo_cliente_arquivo');
        $condutor_autorizado_cliente_nome = new TEntry('condutor_autorizado_cliente_nome');
        $condutor_autorizado_cliente_cpf = new TEntry('condutor_autorizado_cliente_cpf');
        $condutor_autorizado_cliente_rg = new TEntry('condutor_autorizado_cliente_rg');
        $condutor_autorizado_cliente_rg_estado = new TDBCombo('condutor_autorizado_cliente_rg_estado', 'base', 'Estado', 'id', '{estado}','id asc' , $criteria_condutor_autorizado_cliente_rg_estado );
        $condutor_autorizado_cliente_rg_orgao_emissor = new TEntry('condutor_autorizado_cliente_rg_orgao_emissor');
        $condutor_autorizado_cliente_rg_data_emissao = new TDate('condutor_autorizado_cliente_rg_data_emissao');
        $condutor_autorizado_cliente_email = new TEntry('condutor_autorizado_cliente_email');
        $condutor_autorizado_cliente_cep = new TEntry('condutor_autorizado_cliente_cep');
        $condutor_autorizado_cliente_endereco = new TEntry('condutor_autorizado_cliente_endereco');
        $condutor_autorizado_cliente_numero = new TEntry('condutor_autorizado_cliente_numero');
        $condutor_autorizado_cliente_complemento = new TEntry('condutor_autorizado_cliente_complemento');
        $condutor_autorizado_cliente_bairro = new TEntry('condutor_autorizado_cliente_bairro');
        $condutor_autorizado_cliente_municipio = new TDBUniqueSearch('condutor_autorizado_cliente_municipio', 'base', 'Municipio', 'id', 'municipio','id asc' , $criteria_condutor_autorizado_cliente_municipio );
        $condutor_autorizado_cliente_estado = new TDBCombo('condutor_autorizado_cliente_estado', 'base', 'Estado', 'id', '{estado}','id asc' , $criteria_condutor_autorizado_cliente_estado );
        $condutor_autorizado_cliente_condutor = new TDBCombo('condutor_autorizado_cliente_condutor', 'base', 'Condutor', 'id', '{descricao}','id asc' , $criteria_condutor_autorizado_cliente_condutor );
        $id_representante = new TDBUniqueSearch('id_representante', 'base', 'RepresentanteLegal', 'id', 'nome','nome asc' , $criteria_id_representante );
        $button_cadastrar_repr_legal = new TButton('button_cadastrar_repr_legal');
        $arquivo_cliente_id = new THidden('arquivo_cliente_id');
        $condutor_autorizado_cliente_id = new THidden('condutor_autorizado_cliente_id');


        $id->setEditable(false);
        $arquivo_cliente_arquivo->enableFileHandling();
        $condutor_autorizado_cliente_rg_data_emissao->setDatabaseMask('yyyy-mm-dd');
        $button_cadastrar_repr_legal->setAction(new TAction(['RepresentanteLegalFormList', 'onShow']), "Cadastrar Repr. Legal");
        $button_cadastrar_tipo_de_arquivo_arquivo_cliente->setAction(new TAction(['ArquivoTipoFormList', 'onShow']), "Cadastrar Tipo de Arquivo");

        $button_cadastrar_repr_legal->addStyleClass('btn-success');
        $button_cadastrar_tipo_de_arquivo_arquivo_cliente->addStyleClass('btn-success');

        $button_cadastrar_repr_legal->setImage('fas:plus #ffffff');
        $button_cadastrar_tipo_de_arquivo_arquivo_cliente->setImage('fas:plus #ffffff');

        $municipio->setMinLength(2);
        $id_representante->setMinLength(2);
        $condutor_autorizado_cliente_municipio->setMinLength(1);

        $cep->setMask('99.999-999');
        $cpf->setMask('999.999.999-99');
        $municipio->setMask('{municipio}');
        $id_representante->setMask('{nome} {cpf}');
        $condutor_autorizado_cliente_cep->setMask('99.999-999');
        $condutor_autorizado_cliente_cpf->setMask('999.999.999-99');
        $condutor_autorizado_cliente_rg_data_emissao->setMask('dd/mm/yyyy');
        $condutor_autorizado_cliente_municipio->setMask('{municipio} {estado->estado}');

        $id->setSize(100);
        $rg->setSize('70%');
        $cpf->setSize('70%');
        $cep->setSize('70%');
        $nome->setSize('70%');
        $email->setSize('70%');
        $numero->setSize('70%');
        $bairro->setSize('70%');
        $estado->setSize('70%');
        $endereco->setSize('70%');
        $municipio->setSize('70%');
        $complemento->setSize('70%');
        $id_representante->setSize('70%');
        $arquivo_cliente_arquivo->setSize('70%');
        $arquivo_cliente_id_tp_arquivo->setSize('70%');
        $condutor_autorizado_cliente_rg->setSize('70%');
        $condutor_autorizado_cliente_cpf->setSize('70%');
        $condutor_autorizado_cliente_cep->setSize('70%');
        $condutor_autorizado_cliente_nome->setSize('70%');
        $condutor_autorizado_cliente_email->setSize('70%');
        $condutor_autorizado_cliente_numero->setSize('70%');
        $condutor_autorizado_cliente_bairro->setSize('70%');
        $condutor_autorizado_cliente_estado->setSize('70%');
        $condutor_autorizado_cliente_endereco->setSize('70%');
        $condutor_autorizado_cliente_condutor->setSize('70%');
        $condutor_autorizado_cliente_rg_estado->setSize('70%');
        $condutor_autorizado_cliente_municipio->setSize('70%');
        $condutor_autorizado_cliente_complemento->setSize('70%');
        $condutor_autorizado_cliente_rg_data_emissao->setSize(110);
        $condutor_autorizado_cliente_rg_orgao_emissor->setSize('70%');


        $this->form->appendPage("Dados Pessoais");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null)],[$id],[new TLabel("Nome:", null, '14px', null)],[$nome]);
        $row2 = $this->form->addFields([new TLabel("CPF:", null, '14px', null)],[$cpf],[new TLabel("RG:", null, '14px', null)],[$rg]);
        $row3 = $this->form->addFields([new TLabel("Endereco:", null, '14px', null)],[$endereco],[new TLabel("Numero:", null, '14px', null)],[$numero]);
        $row4 = $this->form->addFields([new TLabel("Complemento:", null, '14px', null)],[$complemento],[new TLabel("Bairro:", null, '14px', null)],[$bairro]);
        $row5 = $this->form->addFields([new TLabel("CEP:", null, '14px', null)],[$cep],[new TLabel("Email:", null, '14px', null)],[$email]);
        $row6 = $this->form->addFields([new TLabel("Estado:", null, '14px', null)],[$estado],[new TLabel("Municipio:", null, '14px', null)],[$municipio]);

        $this->form->appendPage("Arquivos");
        $row7 = $this->form->addFields([new TLabel("Tipo de Arquivo:", '#ff0000', '14px', null)],[$arquivo_cliente_id_tp_arquivo,$button_cadastrar_tipo_de_arquivo_arquivo_cliente]);
        $row8 = $this->form->addFields([new TLabel("Arquivo:", null, '14px', null)],[$arquivo_cliente_arquivo]);
        $row9 = $this->form->addFields([$arquivo_cliente_id]);         
        $add_arquivo_cliente = new TButton('add_arquivo_cliente');

        $action_arquivo_cliente = new TAction(array($this, 'onAddArquivoCliente'));

        $add_arquivo_cliente->setAction($action_arquivo_cliente, "Adicionar");
        $add_arquivo_cliente->setImage('fas:plus #000000');

        $this->form->addFields([$add_arquivo_cliente]);

        $detailDatagrid = new TQuickGrid;
        $this->arquivo_cliente_list = new BootstrapDatagridWrapper($detailDatagrid);
        $this->arquivo_cliente_list->style = 'width:100%';
        $this->arquivo_cliente_list->class .= ' table-bordered';
        $this->arquivo_cliente_list->disableDefaultClick();
        $this->arquivo_cliente_list->addQuickColumn('', 'edit', 'left', 50);
        $this->arquivo_cliente_list->addQuickColumn('', 'delete', 'left', 50);

        $column_arquivo_cliente_id_tp_arquivo = $this->arquivo_cliente_list->addQuickColumn("Tipo", 'arquivo_cliente_id_tp_arquivo', 'left' , '40%');
        $column_arquivo_cliente_arquivo_transformed = $this->arquivo_cliente_list->addQuickColumn("Arquivo", 'arquivo_cliente_arquivo', 'left' , '50%');

        $this->arquivo_cliente_list->createModel();
        $tableResponsiveDiv = new TElement('div');
        $tableResponsiveDiv->class = 'table-responsive';
        $tableResponsiveDiv->add($this->arquivo_cliente_list);
        $this->form->addContent([$tableResponsiveDiv]);

        $column_arquivo_cliente_arquivo_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            $value = explode(',', $value);
            if(count($value) == 0)
            {
                $value = $value[0];
            }

            if(is_array($value))
            {
                $files = $value;
                $divFiles = new TElement('div');
                foreach($files as $file)
                {
                    $fileName = $file;
                    if (strpos($file, '%7B') !== false) 
                    {
                        if (!empty($file)) 
                        {
                            $fileObject = json_decode(urldecode($file));

                            $fileName = $fileObject->fileName;
                        }
                    }

                    $a = new TElement('a');
                    $a->href = "download.php?file={$fileName}";
                    $a->class = 'btn btn-link';
                    $a->add($fileName);
                    $a->target = '_blank';

                    $divFiles->add($a);

                }

                return $divFiles;
            }
            else
            {
                if (strpos($value, '%7B') !== false) 
                {
                    if (!empty($value)) 
                    {
                        $value_object = json_decode(urldecode($value));
                        $value = $value_object->fileName;
                    }
                }

                if($value)
                {
                    $a = new TElement('a');
                    $a->href = "download.php?file={$value}";
                    $a->class = 'btn btn-default';
                    $a->add($value);
                    $a->target = '_blank';

                    return $a;
                }

                return $value;
            }
        });
        $this->form->appendPage("Condutores Autorizados");
        $row10 = $this->form->addFields([new TLabel("Nome:", null, '14px', null)],[$condutor_autorizado_cliente_nome]);
        $row11 = $this->form->addFields([new TLabel("CPF:", null, '14px', null)],[$condutor_autorizado_cliente_cpf]);
        $row12 = $this->form->addFields([new TLabel("RG:", null, '14px', null)],[$condutor_autorizado_cliente_rg],[new TLabel("RG UF:", null, '14px', null)],[$condutor_autorizado_cliente_rg_estado]);
        $row13 = $this->form->addFields([new TLabel("RG Orgão Emissor:", null, '14px', null)],[$condutor_autorizado_cliente_rg_orgao_emissor],[new TLabel("RG Data de Emissão:", null, '14px', null)],[$condutor_autorizado_cliente_rg_data_emissao]);
        $row14 = $this->form->addFields([new TLabel("Email:", null, '14px', null)],[$condutor_autorizado_cliente_email],[new TLabel("CEP:", null, '14px', null)],[$condutor_autorizado_cliente_cep]);
        $row15 = $this->form->addFields([new TLabel("Endereco:", null, '14px', null)],[$condutor_autorizado_cliente_endereco],[new TLabel("Numero:", null, '14px', null)],[$condutor_autorizado_cliente_numero]);
        $row16 = $this->form->addFields([new TLabel("Complemento:", null, '14px', null)],[$condutor_autorizado_cliente_complemento],[new TLabel("Bairro:", null, '14px', null)],[$condutor_autorizado_cliente_bairro]);
        $row17 = $this->form->addFields([new TLabel("Municipio:", null, '14px', null)],[$condutor_autorizado_cliente_municipio],[new TLabel("Estado:", null, '14px', null)],[$condutor_autorizado_cliente_estado]);
        $row18 = $this->form->addFields([new TLabel("Condutor:", null, '14px', null)],[$condutor_autorizado_cliente_condutor]);
        $row19 = $this->form->addFields([$condutor_autorizado_cliente_id]);         
        $add_condutor_autorizado_cliente = new TButton('add_condutor_autorizado_cliente');

        $action_condutor_autorizado_cliente = new TAction(array($this, 'onAddCondutorAutorizadoCliente'));

        $add_condutor_autorizado_cliente->setAction($action_condutor_autorizado_cliente, "Adicionar");
        $add_condutor_autorizado_cliente->setImage('fas:plus #000000');

        $this->form->addFields([$add_condutor_autorizado_cliente]);

        $detailDatagrid = new TQuickGrid;
        $this->condutor_autorizado_cliente_list = new BootstrapDatagridWrapper($detailDatagrid);
        $this->condutor_autorizado_cliente_list->style = 'width:100%';
        $this->condutor_autorizado_cliente_list->class .= ' table-bordered';
        $this->condutor_autorizado_cliente_list->disableDefaultClick();
        $this->condutor_autorizado_cliente_list->addQuickColumn('', 'edit', 'left', 50);
        $this->condutor_autorizado_cliente_list->addQuickColumn('', 'delete', 'left', 50);

        $column_condutor_autorizado_cliente_nome = $this->condutor_autorizado_cliente_list->addQuickColumn("Nome", 'condutor_autorizado_cliente_nome', 'left');
        $column_condutor_autorizado_cliente_condutor = $this->condutor_autorizado_cliente_list->addQuickColumn("Condutor", 'condutor_autorizado_cliente_condutor', 'left');

        $this->condutor_autorizado_cliente_list->createModel();
        $this->form->addContent([$this->condutor_autorizado_cliente_list]);

        $this->form->appendPage("Representante Legal");
        $row20 = $this->form->addFields([new TLabel("Representante Legal:", null, '14px', null)],[$id_representante,$button_cadastrar_repr_legal]);

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'far:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_oncontratos = $this->form->addAction("Ação", new TAction([$this, 'onContratos']), 'far:circle #000000');
        $this->btn_oncontratos = $btn_oncontratos;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Clientes","Cadastrar Cliente"]));
        }
        $container->add($this->form);

        parent::add($container);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            /**
            // Enable Debug logger for SQL operations inside the transaction
            TTransaction::setLogger(new TLoggerSTD); // standard output
            TTransaction::setLogger(new TLoggerTXT('log.txt')); // file
            **/

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Cliente(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $arquivo_cliente_arquivo_dir = '/var/www/html/sistema/files';  

            $object->store(); // save the object 

            $condutor_autorizado_cliente_items = $this->storeItems('CondutorAutorizado', 'id_cliente', $object, 'condutor_autorizado_cliente', function($masterObject, $detailObject){ 

                //code here

            }); 

            $arquivo_cliente_items = $this->storeItems('Arquivo', 'id_cliente', $object, 'arquivo_cliente', function($masterObject, $detailObject){ 

                //code here

            }); 
            if(!empty($arquivo_cliente_items))
            {
                foreach ($arquivo_cliente_items as $item)
                {
                    $dataFile = new stdClass();
                    $dataFile->arquivo = $item->arquivo;
                    $this->saveFile($item, $dataFile, 'arquivo', $arquivo_cliente_arquivo_dir);
                }
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            /**
            // To define an action to be executed on the message close event:
            $messageAction = new TAction(['className', 'methodName']);
            **/

            new TMessage('info', AdiantiCoreTranslator::translate('Record saved'), $messageAction);

        }
        catch (Exception $e) // in case of exception
        {

            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }
    public function onContratos($param = null) 
    {
        try 
        {
            //code here

    $pageParam = ['key' => 53]; // ex.: = ['key' => 10]

    TApplication::loadPage('ContratoFormList', 'onShow', $pageParam);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Cliente($key); // instantiates the Active Record 

                $condutor_autorizado_cliente_items = $this->loadItems('CondutorAutorizado', 'id_cliente', $object, 'condutor_autorizado_cliente', function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }); 

                $arquivo_cliente_items = $this->loadItems('Arquivo', 'id_cliente', $object, 'arquivo_cliente', function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }); 

                $this->form->setData($object); // fill the form 

                    $this->onReload();

                TTransaction::close(); // close the transaction 
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }

    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(true);

        TSession::setValue('arquivo_cliente_items', null);
        TSession::setValue('condutor_autorizado_cliente_items', null);

        $this->onReload();
    }

    public function onAddArquivoCliente( $param )
    {
        try
        {
            $data = $this->form->getData();

            if(!$data->arquivo_cliente_id_tp_arquivo)
            {
                throw new Exception(AdiantiCoreTranslator::translate('The field ^1 is required', "Id tp arquivo"));
            }             

            $arquivo_cliente_items = TSession::getValue('arquivo_cliente_items');
            $key = isset($data->arquivo_cliente_id) && $data->arquivo_cliente_id ? $data->arquivo_cliente_id : 'b'.uniqid();
            $fields = []; 

            $fields['arquivo_cliente_id_tp_arquivo'] = $data->arquivo_cliente_id_tp_arquivo;
            $fields['arquivo_cliente_arquivo'] = $data->arquivo_cliente_arquivo;
            $arquivo_cliente_items[ $key ] = $fields;

            TSession::setValue('arquivo_cliente_items', $arquivo_cliente_items);

            $data->arquivo_cliente_id = '';
            $data->arquivo_cliente_id_tp_arquivo = '';
            $data->arquivo_cliente_arquivo = '';

            $this->form->setData($data);

            $this->onReload( $param );
        }
        catch (Exception $e)
        {
            $this->form->setData( $this->form->getData());

            new TMessage('error', $e->getMessage());
        }
    }

    public function onEditArquivoCliente( $param )
    {
        $data = $this->form->getData();

        // read session items
        $items = TSession::getValue('arquivo_cliente_items');

        // get the session item
        $item = $items[$param['arquivo_cliente_id_row_id']];

        $data->arquivo_cliente_id_tp_arquivo = $item['arquivo_cliente_id_tp_arquivo'];
        $data->arquivo_cliente_arquivo = $item['arquivo_cliente_arquivo'];

        $data->arquivo_cliente_id = $param['arquivo_cliente_id_row_id'];

        // fill product fields
        $this->form->setData( $data );

        $this->onReload( $param );

    }

    public function onDeleteArquivoCliente( $param )
    {
        $data = $this->form->getData();

        $data->arquivo_cliente_id_tp_arquivo = '';
        $data->arquivo_cliente_arquivo = '';

        // clear form data
        $this->form->setData( $data );

        // read session items
        $items = TSession::getValue('arquivo_cliente_items');

        // delete the item from session
        unset($items[$param['arquivo_cliente_id_row_id']]);
        TSession::setValue('arquivo_cliente_items', $items);

        // reload sale items
        $this->onReload( $param );

    }

    public function onReloadArquivoCliente( $param )
    {
        $items = TSession::getValue('arquivo_cliente_items'); 

        $this->arquivo_cliente_list->clear(); 

        if($items) 
        { 
            $cont = 1; 
            foreach ($items as $key => $item) 
            {
                $rowItem = new StdClass;

                $action_del = new TAction(array($this, 'onDeleteArquivoCliente')); 
                $action_del->setParameter('arquivo_cliente_id_row_id', $key);
                $action_del->setParameter('row_data', base64_encode(serialize($item)));
                $action_del->setParameter('key', $key);

                $action_edi = new TAction(array($this, 'onEditArquivoCliente'));  
                $action_edi->setParameter('arquivo_cliente_id_row_id', $key);  
                $action_edi->setParameter('row_data', base64_encode(serialize($item)));
                $action_edi->setParameter('key', $key);

                $button_del = new TButton('delete_arquivo_cliente'.$cont);
                $button_del->setAction($action_del, '');
                $button_del->setFormName($this->form->getName());
                $button_del->setLabel("Excluir");
                $button_del->class = 'btn btn-default btn-sm';
                $button_del->setImage('far:trash-alt #dd5a43');

                $rowItem->delete = $button_del;

                $button_edi = new TButton('edit_arquivo_cliente'.$cont);
                $button_edi->setAction($action_edi, '');
                $button_edi->setFormName($this->form->getName());
                $button_edi->setLabel("Editar");
                $button_edi->class = 'btn btn-default btn-sm';
                $button_edi->setImage('far:edit #478fca');

                $rowItem->edit = $button_edi;

                $rowItem->arquivo_cliente_id_tp_arquivo = '';
                if(isset($item['arquivo_cliente_id_tp_arquivo']) && $item['arquivo_cliente_id_tp_arquivo'])
                {
                    TTransaction::open('base');
                    $arquivo_tipo = ArquivoTipo::find($item['arquivo_cliente_id_tp_arquivo']);
                    if($arquivo_tipo)
                    {
                        $rowItem->arquivo_cliente_id_tp_arquivo = $arquivo_tipo->render('{descricao}');
                    }
                    TTransaction::close();
                }

                $rowItem->arquivo_cliente_arquivo = isset($item['arquivo_cliente_arquivo']) ? $item['arquivo_cliente_arquivo'] : '';

                $row = $this->arquivo_cliente_list->addItem($rowItem);

                $cont++;
            } 
        } 
    } 

    public function onAddCondutorAutorizadoCliente( $param )
    {
        try
        {
            $data = $this->form->getData();

            $condutor_autorizado_cliente_items = TSession::getValue('condutor_autorizado_cliente_items');
            $key = isset($data->condutor_autorizado_cliente_id) && $data->condutor_autorizado_cliente_id ? $data->condutor_autorizado_cliente_id : 'b'.uniqid();
            $fields = []; 

            $fields['condutor_autorizado_cliente_nome'] = $data->condutor_autorizado_cliente_nome;
            $fields['condutor_autorizado_cliente_cpf'] = $data->condutor_autorizado_cliente_cpf;
            $fields['condutor_autorizado_cliente_rg'] = $data->condutor_autorizado_cliente_rg;
            $fields['condutor_autorizado_cliente_rg_estado'] = $data->condutor_autorizado_cliente_rg_estado;
            $fields['condutor_autorizado_cliente_rg_orgao_emissor'] = $data->condutor_autorizado_cliente_rg_orgao_emissor;
            $fields['condutor_autorizado_cliente_rg_data_emissao'] = $data->condutor_autorizado_cliente_rg_data_emissao;
            $fields['condutor_autorizado_cliente_email'] = $data->condutor_autorizado_cliente_email;
            $fields['condutor_autorizado_cliente_cep'] = $data->condutor_autorizado_cliente_cep;
            $fields['condutor_autorizado_cliente_endereco'] = $data->condutor_autorizado_cliente_endereco;
            $fields['condutor_autorizado_cliente_numero'] = $data->condutor_autorizado_cliente_numero;
            $fields['condutor_autorizado_cliente_complemento'] = $data->condutor_autorizado_cliente_complemento;
            $fields['condutor_autorizado_cliente_bairro'] = $data->condutor_autorizado_cliente_bairro;
            $fields['condutor_autorizado_cliente_municipio'] = $data->condutor_autorizado_cliente_municipio;
            $fields['condutor_autorizado_cliente_estado'] = $data->condutor_autorizado_cliente_estado;
            $fields['condutor_autorizado_cliente_condutor'] = $data->condutor_autorizado_cliente_condutor;
            $condutor_autorizado_cliente_items[ $key ] = $fields;

            TSession::setValue('condutor_autorizado_cliente_items', $condutor_autorizado_cliente_items);

            $data->condutor_autorizado_cliente_id = '';
            $data->condutor_autorizado_cliente_nome = '';
            $data->condutor_autorizado_cliente_cpf = '';
            $data->condutor_autorizado_cliente_rg = '';
            $data->condutor_autorizado_cliente_rg_estado = '';
            $data->condutor_autorizado_cliente_rg_orgao_emissor = '';
            $data->condutor_autorizado_cliente_rg_data_emissao = '';
            $data->condutor_autorizado_cliente_email = '';
            $data->condutor_autorizado_cliente_cep = '';
            $data->condutor_autorizado_cliente_endereco = '';
            $data->condutor_autorizado_cliente_numero = '';
            $data->condutor_autorizado_cliente_complemento = '';
            $data->condutor_autorizado_cliente_bairro = '';
            $data->condutor_autorizado_cliente_municipio = '';
            $data->condutor_autorizado_cliente_estado = '';
            $data->condutor_autorizado_cliente_condutor = '';

            $this->form->setData($data);

            $this->onReload( $param );
        }
        catch (Exception $e)
        {
            $this->form->setData( $this->form->getData());

            new TMessage('error', $e->getMessage());
        }
    }

    public function onEditCondutorAutorizadoCliente( $param )
    {
        $data = $this->form->getData();

        // read session items
        $items = TSession::getValue('condutor_autorizado_cliente_items');

        // get the session item
        $item = $items[$param['condutor_autorizado_cliente_id_row_id']];

        $data->condutor_autorizado_cliente_nome = $item['condutor_autorizado_cliente_nome'];
        $data->condutor_autorizado_cliente_cpf = $item['condutor_autorizado_cliente_cpf'];
        $data->condutor_autorizado_cliente_rg = $item['condutor_autorizado_cliente_rg'];
        $data->condutor_autorizado_cliente_rg_estado = $item['condutor_autorizado_cliente_rg_estado'];
        $data->condutor_autorizado_cliente_rg_orgao_emissor = $item['condutor_autorizado_cliente_rg_orgao_emissor'];
        $data->condutor_autorizado_cliente_rg_data_emissao = $item['condutor_autorizado_cliente_rg_data_emissao'];
        $data->condutor_autorizado_cliente_email = $item['condutor_autorizado_cliente_email'];
        $data->condutor_autorizado_cliente_cep = $item['condutor_autorizado_cliente_cep'];
        $data->condutor_autorizado_cliente_endereco = $item['condutor_autorizado_cliente_endereco'];
        $data->condutor_autorizado_cliente_numero = $item['condutor_autorizado_cliente_numero'];
        $data->condutor_autorizado_cliente_complemento = $item['condutor_autorizado_cliente_complemento'];
        $data->condutor_autorizado_cliente_bairro = $item['condutor_autorizado_cliente_bairro'];
        $data->condutor_autorizado_cliente_municipio = $item['condutor_autorizado_cliente_municipio'];
        $data->condutor_autorizado_cliente_estado = $item['condutor_autorizado_cliente_estado'];
        $data->condutor_autorizado_cliente_condutor = $item['condutor_autorizado_cliente_condutor'];

        $data->condutor_autorizado_cliente_id = $param['condutor_autorizado_cliente_id_row_id'];

        // fill product fields
        $this->form->setData( $data );

        $this->onReload( $param );

    }

    public function onDeleteCondutorAutorizadoCliente( $param )
    {
        $data = $this->form->getData();

        $data->condutor_autorizado_cliente_nome = '';
        $data->condutor_autorizado_cliente_cpf = '';
        $data->condutor_autorizado_cliente_rg = '';
        $data->condutor_autorizado_cliente_rg_estado = '';
        $data->condutor_autorizado_cliente_rg_orgao_emissor = '';
        $data->condutor_autorizado_cliente_rg_data_emissao = '';
        $data->condutor_autorizado_cliente_email = '';
        $data->condutor_autorizado_cliente_cep = '';
        $data->condutor_autorizado_cliente_endereco = '';
        $data->condutor_autorizado_cliente_numero = '';
        $data->condutor_autorizado_cliente_complemento = '';
        $data->condutor_autorizado_cliente_bairro = '';
        $data->condutor_autorizado_cliente_municipio = '';
        $data->condutor_autorizado_cliente_estado = '';
        $data->condutor_autorizado_cliente_condutor = '';

        // clear form data
        $this->form->setData( $data );

        // read session items
        $items = TSession::getValue('condutor_autorizado_cliente_items');

        // delete the item from session
        unset($items[$param['condutor_autorizado_cliente_id_row_id']]);
        TSession::setValue('condutor_autorizado_cliente_items', $items);

        // reload sale items
        $this->onReload( $param );

    }

    public function onReloadCondutorAutorizadoCliente( $param )
    {
        $items = TSession::getValue('condutor_autorizado_cliente_items'); 

        $this->condutor_autorizado_cliente_list->clear(); 

        if($items) 
        { 
            $cont = 1; 
            foreach ($items as $key => $item) 
            {
                $rowItem = new StdClass;

                $action_del = new TAction(array($this, 'onDeleteCondutorAutorizadoCliente')); 
                $action_del->setParameter('condutor_autorizado_cliente_id_row_id', $key);
                $action_del->setParameter('row_data', base64_encode(serialize($item)));
                $action_del->setParameter('key', $key);

                $action_edi = new TAction(array($this, 'onEditCondutorAutorizadoCliente'));  
                $action_edi->setParameter('condutor_autorizado_cliente_id_row_id', $key);  
                $action_edi->setParameter('row_data', base64_encode(serialize($item)));
                $action_edi->setParameter('key', $key);

                $button_del = new TButton('delete_condutor_autorizado_cliente'.$cont);
                $button_del->setAction($action_del, '');
                $button_del->setFormName($this->form->getName());
                $button_del->class = 'btn btn-link btn-sm';
                $button_del->title = "Excluir";
                $button_del->setImage('far:trash-alt #dd5a43');

                $rowItem->delete = $button_del;

                $button_edi = new TButton('edit_condutor_autorizado_cliente'.$cont);
                $button_edi->setAction($action_edi, '');
                $button_edi->setFormName($this->form->getName());
                $button_edi->class = 'btn btn-link btn-sm';
                $button_edi->title = "Editar";
                $button_edi->setImage('far:edit #478fca');

                $rowItem->edit = $button_edi;

                $rowItem->condutor_autorizado_cliente_nome = isset($item['condutor_autorizado_cliente_nome']) ? $item['condutor_autorizado_cliente_nome'] : '';
                $rowItem->condutor_autorizado_cliente_cpf = isset($item['condutor_autorizado_cliente_cpf']) ? $item['condutor_autorizado_cliente_cpf'] : '';
                $rowItem->condutor_autorizado_cliente_rg = isset($item['condutor_autorizado_cliente_rg']) ? $item['condutor_autorizado_cliente_rg'] : '';
                $rowItem->condutor_autorizado_cliente_rg_estado = '';
                if(isset($item['condutor_autorizado_cliente_rg_estado']) && $item['condutor_autorizado_cliente_rg_estado'])
                {
                    TTransaction::open('base');
                    $estado = Estado::find($item['condutor_autorizado_cliente_rg_estado']);
                    if($estado)
                    {
                        $rowItem->condutor_autorizado_cliente_rg_estado = $estado->render('{estado}');
                    }
                    TTransaction::close();
                }

                $rowItem->condutor_autorizado_cliente_rg_orgao_emissor = isset($item['condutor_autorizado_cliente_rg_orgao_emissor']) ? $item['condutor_autorizado_cliente_rg_orgao_emissor'] : '';
                $rowItem->condutor_autorizado_cliente_rg_data_emissao = isset($item['condutor_autorizado_cliente_rg_data_emissao']) ? $item['condutor_autorizado_cliente_rg_data_emissao'] : '';
                $rowItem->condutor_autorizado_cliente_email = isset($item['condutor_autorizado_cliente_email']) ? $item['condutor_autorizado_cliente_email'] : '';
                $rowItem->condutor_autorizado_cliente_cep = isset($item['condutor_autorizado_cliente_cep']) ? $item['condutor_autorizado_cliente_cep'] : '';
                $rowItem->condutor_autorizado_cliente_endereco = isset($item['condutor_autorizado_cliente_endereco']) ? $item['condutor_autorizado_cliente_endereco'] : '';
                $rowItem->condutor_autorizado_cliente_numero = isset($item['condutor_autorizado_cliente_numero']) ? $item['condutor_autorizado_cliente_numero'] : '';
                $rowItem->condutor_autorizado_cliente_complemento = isset($item['condutor_autorizado_cliente_complemento']) ? $item['condutor_autorizado_cliente_complemento'] : '';
                $rowItem->condutor_autorizado_cliente_bairro = isset($item['condutor_autorizado_cliente_bairro']) ? $item['condutor_autorizado_cliente_bairro'] : '';
                $rowItem->condutor_autorizado_cliente_municipio = '';
                if(isset($item['condutor_autorizado_cliente_municipio']) && $item['condutor_autorizado_cliente_municipio'])
                {
                    TTransaction::open('base');
                    $municipio = Municipio::find($item['condutor_autorizado_cliente_municipio']);
                    if($municipio)
                    {
                        $rowItem->condutor_autorizado_cliente_municipio = $municipio->render('{municipio} {estado->estado}');
                    }
                    TTransaction::close();
                }

                $rowItem->condutor_autorizado_cliente_estado = '';
                if(isset($item['condutor_autorizado_cliente_estado']) && $item['condutor_autorizado_cliente_estado'])
                {
                    TTransaction::open('base');
                    $estado = Estado::find($item['condutor_autorizado_cliente_estado']);
                    if($estado)
                    {
                        $rowItem->condutor_autorizado_cliente_estado = $estado->render('{estado}');
                    }
                    TTransaction::close();
                }

                $rowItem->condutor_autorizado_cliente_condutor = '';
                if(isset($item['condutor_autorizado_cliente_condutor']) && $item['condutor_autorizado_cliente_condutor'])
                {
                    TTransaction::open('base');
                    $condutor = Condutor::find($item['condutor_autorizado_cliente_condutor']);
                    if($condutor)
                    {
                        $rowItem->condutor_autorizado_cliente_condutor = $condutor->render('{descricao}');
                    }
                    TTransaction::close();
                }

                $row = $this->condutor_autorizado_cliente_list->addItem($rowItem);

                $cont++;
            } 
        } 
    } 

    public function onShow($param = null)
    {

        TSession::setValue('arquivo_cliente_items', null);
        TSession::setValue('condutor_autorizado_cliente_items', null);

        $this->onReload();

    } 

    public function onReload($params = null)
    {
        $this->loaded = TRUE;

        $this->onReloadArquivoCliente($params);
        $this->onReloadCondutorAutorizadoCliente($params);
    }

    public function show() 
    { 
        $param = func_get_arg(0);
        if(!empty($param['current_tab']))
        {
            $this->form->setCurrentPage($param['current_tab']);
        }

        if (!$this->loaded AND (!isset($_GET['method']) OR $_GET['method'] !== 'onReload') ) 
        { 
            $this->onReload( func_get_arg(0) );
        }
        parent::show();
    }

    public static function getFormName()
    {
        return self::$formName;
    }

}

