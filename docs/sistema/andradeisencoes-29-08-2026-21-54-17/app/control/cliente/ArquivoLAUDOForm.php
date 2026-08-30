<?php

class ArquivoLAUDOForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'base';
    private static $activeRecord = 'Arquivo';
    private static $primaryKey = 'id';
    private static $formName = 'form_Arquivo';

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
        $this->form->setFormTitle("Anexar LAUDO MÉDICO:");

        $criteria_id_tp_arquivo = new TCriteria();

// Obter os dados da sessao
$dadosPessoais = TSession::getValue('dados_pessoais');

// Componente que irá armazenar o nome do usuario
$label = new TLabel('Agora, envie o arquivo de LAUDO MÉDICO.<br/><br/>');  
$label->setFontSize(18);

// Adicionar o nome do usuario na tela
$this->form->addContent([$label]);

        $id_tp_arquivo = new TDBCombo('id_tp_arquivo', 'base', 'ArquivoTipo', 'id', '{descricao}','id asc' , $criteria_id_tp_arquivo );
        $arquivo = new TFile('arquivo');

        $id_tp_arquivo->addValidation("Id tp arquivo", new TRequiredValidator()); 

        $id_tp_arquivo->setEditable(false);
        $id_tp_arquivo->setValue('4');
        $arquivo->enableFileHandling();
        $arquivo->setSize('70%');
        $id_tp_arquivo->setSize('66%');

        $row1 = $this->form->addFields([new TLabel("Tipo:", '#ff0000', '14px', null)],[$id_tp_arquivo],[new TLabel("Arquivo:", null, '14px', null)],[$arquivo]);

        // create the form actions
        $btn_onsave = $this->form->addAction("Enviar Comprovante de Residência", new TAction([$this, 'onSave']), 'far:arrow-alt-circle-right #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-success'); 

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Cliente","Anexar LAUDO MÉDICO"]));
        }
        $container->add($this->form);

$tstep = new TStep();

$tstep->addItem('Dados pessoais', false, false);
$tstep->addItem('Anexar CNH', false, false);
$tstep->addItem('Anexar Laudo', true, false);
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

            $object = new Arquivo(); // create an empty object 
            $data->id_cliente = $dadosPessoais->id;

            $data = $this->form->getData(); // get form data as array

            $dadosPessoais = TSession::getValue('dados_pessoais');
            $data->id_cliente = $dadosPessoais->id;

            $object->fromArray( (array) $data); // load the object with data

            $arquivo_dir = '/var/www/html/sistema/arquivos';  

            $object->store(); // save the object 

            $this->saveFile($object, $data, 'arquivo', $arquivo_dir); 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            /**
            // To define an action to be executed on the message close event:
            $messageAction = new TAction(['className', 'methodName']);
            **/

            //new TMessage('info', AdiantiCoreTranslator::translate('Record saved'), $messageAction);
            $pagina='ArquivoCOMPROVANTEForm';
            $metodo='onShow';
            $param=(array) $data;

            //TSession::setValue('dados_pessoais', $data);

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

                $object = new Arquivo($key); // instantiates the Active Record 

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

