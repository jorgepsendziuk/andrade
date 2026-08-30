<?php

use Adianti\Database\TTransaction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Wrapper\TDBUniqueSearch;

class SystemNewChatForm extends TWindow
{
    protected $form;
    private static $formName = 'form_SystemNewChatForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(350, null);
        parent::setTitle(_t('New conversation'));
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle(_t('New conversation'));

        $system_user_id = new TDBUniqueSearch('system_user_id', 'permission', 'SystemUsers', 'id', 'name','name asc', SystemChatService::getUsersCriteria() );

        $system_user_id->addValidation(_t('User'), new TRequiredValidator()); 
        $system_user_id->setSize('100%');
        $system_user_id->setMinLength(0);
        $system_user_id->setMask('{name}');

        $row1 = $this->form->addFields([new TLabel(_t('User'), null, '14px', null, '100%'),$system_user_id]);
        $row1->layout = [' col-sm-12'];

        // create the form actions
        $btn_onstart = $this->form->addAction(_t('Start conversation'), new TAction([$this, 'onStart'], ['static' => 1]), 'fas:comment-medical #ffffff');
        $this->btn_onstart = $btn_onstart;
        $btn_onstart->addStyleClass('btn-primary'); 
        $btn_onstart->style = ';width:100%;';

        parent::add($this->form);
    }

    public function onStart($param = null) 
    {
        try
        {
            $this->form->validate();

            if(BuilderFirebaseService::chatExists(TSession::getValue('userid'), $param['system_user_id']))
            {
                throw new Exception('Chat already exists');
            }

            TScript::create("ChatApp.startNewChat({$param['system_user_id']});");

            TWindow::closeWindow();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               

    } 

}

