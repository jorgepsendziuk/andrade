<?php

class ContratoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'base';
    private static $activeRecord = 'Contrato';
    private static $primaryKey = 'id';
    private static $formName = 'form_Contrato';

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
        $this->form->setFormTitle("Contratos - Cadastro");

        $criteria_id_cliente = new TCriteria();
        $criteria_id_carro = new TCriteria();

        $id_cliente = new TDBUniqueSearch('id_cliente', 'base', 'Cliente', 'id', 'nome','nome asc' , $criteria_id_cliente );
        $button_cadastrar_cliente = new TButton('button_cadastrar_cliente');
        $id_carro = new TDBCombo('id_carro', 'base', 'CarroModelo', 'id', '{fk_marca->descricao} {modelo}','marca asc' , $criteria_id_carro );
        $button_cadastrar_carro = new TButton('button_cadastrar_carro');
        $data_contrato = new TDate('data_contrato');


        $id_cliente->setMinLength(0);
        $data_contrato->setDatabaseMask('yyyy-mm-dd');
        $data_contrato->setMask('dd/mm/yyyy');
        $id_cliente->setMask('{nome} - {cpf}');

        $button_cadastrar_carro->setAction(new TAction(['ModeloFormList', 'onShow']), "Cadastrar Carro");
        $button_cadastrar_cliente->setAction(new TAction(['ClienteForm', 'onShow']), "Cadastrar Cliente");

        $button_cadastrar_carro->addStyleClass('btn-success');
        $button_cadastrar_cliente->addStyleClass('btn-success');

        $button_cadastrar_carro->setImage('fas:plus #ffffff');
        $button_cadastrar_cliente->setImage('fas:plus #ffffff');

        $id_carro->setSize('70%');
        $id_cliente->setSize('70%');
        $data_contrato->setSize(110);

        $this->form->appendPage("Contrato");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Cliente:", null, '14px', null)],[$id_cliente,$button_cadastrar_cliente]);
        $row2 = $this->form->addFields([new TLabel("Carro:", null, '14px', null)],[$id_carro,$button_cadastrar_carro]);
        $row3 = $this->form->addFields([new TLabel("Data contrato:", null, '14px', null)],[$data_contrato]);

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'far:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Cadastros Auxiliares","Contratos - Cadastro"]));
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

            $object = new Contrato(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 

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

    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Contrato($key); // instantiates the Active Record 

                $this->form->setData($object); // fill the form 

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

    }

    public function onShow($param = null)
    {

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

