<?php

class SystemAddUserGroupForm extends TWindow
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
        parent::setSize(350, 250);
        parent::setTitle(_t('Add group participants'));
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle(_t('Add group participants'));

        $group_id = new THidden('group_id');
        $system_user_id = new TDBMultiSearch('system_user_id', 'permission', 'SystemUsers', 'id', 'name','name asc', SystemChatService::getUsersCriteria() );

        $system_user_id->setMinLength(0);
        $system_user_id->setMask('{name}');
        $system_user_id->setSize('100%', 70);

        $group_id->setValue($param['group_id'] ?? null);

        $system_user_id->addValidation(_t('Participants'), new TRequiredValidator()); 

        $row1 = $this->form->addFields([new TLabel(_t('Participants'), null, '14px', null, '100%'),$system_user_id, $group_id]);
        $row1->layout = [' col-sm-12'];

        // create the form actions
        $btn_onAdd = $this->form->addAction(_t('Add'), new TAction([$this, 'onAdd'], ['static'=>1]), 'fas:user-plus #ffffff');
        $btn_onAdd->addStyleClass('btn-primary');
        $btn_onAdd->style = ';width:100%;';

        parent::add($this->form);
    }

    public function onAdd($param = null) 
    {
        try
        {
            $this->form->validate();

            $param['system_user_id'][] = TSession::getValue('userid');

            $system_user_id = json_encode($param['system_user_id']);
            
            TScript::create("ChatApp.addParticipants({$system_user_id}, '{$param['group_id']}')");

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

