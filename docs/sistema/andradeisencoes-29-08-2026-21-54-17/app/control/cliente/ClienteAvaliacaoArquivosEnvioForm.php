<?php

class ClienteAvaliacaoArquivosEnvioForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'base';
    private static $activeRecord = 'Cliente';
    private static $primaryKey = 'id';
    private static $formName = 'form_Cliente';

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
        $this->form->setFormTitle("ENVIO DE ARQUIVOS PARA AVALIAÇÃO MÉDICA");

        $criteria_estado = new TCriteria();
        $criteria_municipio = new TCriteria();

        $nome = new TEntry('nome');
        $cpf = new TEntry('cpf');
        $email = new TEntry('email');
        $endereco = new TEntry('endereco');
        $numero = new TEntry('numero');
        $complemento = new TEntry('complemento');
        $bairro = new TEntry('bairro');
        $estado = new TDBCombo('estado', 'base', 'Estado', 'id', '{estado}','id asc' , $criteria_estado );
        $municipio = new TDBUniqueSearch('municipio', 'base', 'Municipio', 'id', 'municipio','id_estado asc' , $criteria_municipio );
        $cep = new TEntry('cep');


        $municipio->setMinLength(2);
        $cep->setMask('99.999-999');
        $cpf->setMask('999.999.999-99');
        $municipio->setMask('{municipio}');

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

        $this->form->appendPage("Dados Pessoais");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Nome:", null, '14px', null)],[$nome]);
        $row2 = $this->form->addFields([new TLabel("CPF:", null, '14px', null)],[$cpf],[new TLabel("Email:", null, '14px', null)],[$email]);

        $this->form->appendPage("Endereço (opcional)");
        $row3 = $this->form->addFields([new TLabel("Endereco:", null, '14px', null)],[$endereco],[new TLabel("Numero:", null, '14px', null)],[$numero]);
        $row4 = $this->form->addFields([new TLabel("Complemento:", null, '14px', null)],[$complemento],[new TLabel("Bairro:", null, '14px', null)],[$bairro]);
        $row5 = $this->form->addFields([new TLabel("Estado:", null, '14px', null)],[$estado],[new TLabel("Municipio:", null, '14px', null)],[$municipio]);
        $row6 = $this->form->addFields([new TLabel("CEP:", null, '14px', null)],[$cep],[],[]);

        // create the form actions
        $btn_onsave = $this->form->addAction("Enviar Documentos", new TAction([$this, 'onSave']), 'far:arrow-alt-circle-right #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-success'); 

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Cliente","ENVIO DE ARQUIVOS PARA AVALIAÇÃO MÉDICA"]));
        }
        $container->add($this->form);

$tstep = new TStep();

$tstep->addItem('Dados pessoais', true, false);
$tstep->addItem('Anexar CNH', false, false);
$tstep->addItem('Anexar Laudo', false, false);
$tstep->addItem('Anexar Comprovante de Endereço', false, false);

parent::add($tstep);

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

            $object->store(); // save the object 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            /**
            // To define an action to be executed on the message close event:
            $messageAction = new TAction(['className', 'methodName']);
            **/

            //new TMessage('info', AdiantiCoreTranslator::translate('Record saved'), $messageAction);
            $pagina='ArquivoCNHForm';
            $metodo='onShow';
            $param=(array) $data;

            TSession::setValue('dados_pessoais', $data);

            AdiantiCoreApplication::loadPage($pagina,$metodo,$param);

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

                $object = new Cliente($key); // instantiates the Active Record 

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

