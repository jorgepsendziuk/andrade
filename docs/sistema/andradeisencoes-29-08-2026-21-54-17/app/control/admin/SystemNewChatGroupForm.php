<?php

class SystemNewChatGroupForm extends TWindow
{
    protected $form;
    private static $formName = 'form_SystemNewChatGroupForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(350, null);
        parent::setTitle(_t('New group conversation'));
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle(_t('New group conversation'));

        $group_name = new TEntry('group_name');
        $system_user_id = new TDBMultiSearch('system_user_id', 'permission', 'SystemUsers', 'id', 'name','name asc', SystemChatService::getUsersCriteria() );

        $system_user_id->setMinLength(0);
        $system_user_id->setMask('{name}');
        $group_name->setSize('100%');
        $system_user_id->setSize('100%', 70);

        $system_user_id->addValidation(_t('Participants'), new TRequiredValidator()); 
        $group_name->addValidation(_t('Group name'), new TRequiredValidator()); 

        $row1 = $this->form->addFields([new TLabel(_t('Group name'), null, '14px', null, '100%'),$group_name]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([new TLabel(_t('Participants'), null, '14px', null, '100%'),$system_user_id]);
        $row2->layout = [' col-sm-12'];

        // create the form actions
        $btn_onstart = $this->form->addAction(_t('Start conversation'), new TAction([$this, 'onStart'], ['static'=>1]), 'fas:comment-medical #ffffff');
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

            $param['system_user_id'][] = TSession::getValue('userid');

            $system_user_id = json_encode($param['system_user_id']);
            
            TScript::create("ChatApp.startNewChatGroup({$system_user_id}, '{$param['group_name']}')");

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

