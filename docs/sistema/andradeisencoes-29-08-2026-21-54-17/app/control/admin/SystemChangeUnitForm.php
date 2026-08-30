<?php

class SystemChangeUnitForm extends TWindow
{
    protected $form;
    private static $formName = 'form_SystemChangeUnitForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(400, null);
        parent::setTitle(_t("Change Unit"));
        parent::setProperty('class', 'window_modal');

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle(_t("Change Unit"));
        
        $criteria_system_unit_id = new TCriteria();

        $filterVar = TSession::getValue("userunitids");
        $criteria_system_unit_id->add(new TFilter('id', 'in', $filterVar));

        $current_unit = new TLabel(TSession::getValue('userunitname'), null, '14px', null);
        $system_unit_id = new TDBCombo('system_unit_id', 'permission', 'SystemUnit', 'id', '{name}','name asc' , $criteria_system_unit_id );

        $system_unit_id->setSize('100%');
        $system_unit_id->setDefaultOption(false);
        $system_unit_id->setValue(TSession::getValue('userunitid'));

        $current_unit->class = 'badge badge-success';

        $row1 = $this->form->addFields([new TLabel(_t("Current unit").":", null, '14px', null),$current_unit]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([new TLabel(_t("Choose the unit").":", null, '14px', null, '100%'),$system_unit_id]);
        $row2->layout = [' col-sm-12'];

        // create the form actions
        $btn_changeunit = $this->form->addAction(_t("Change Unit"), new TAction([$this, 'changeUnit']), 'fas:exchange-alt #ffffff');
        $btn_changeunit->style = 'width: 100%';
        $btn_changeunit->addStyleClass('btn-primary'); 

        parent::add($this->form);

    }

    public static function changeUnit($param = null) 
    {
        try
        {
            if(!$param['system_unit_id'])
            {
                throw new Exception(_t('Unit is required'));
            }

            ApplicationAuthenticationService::setUnit($param['system_unit_id']);
            
            new TMessage('info', _t('Unit changed!'));

            TWindow::closeAll();

            TApplication::gotoPage('EmptyPage'); // reload
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

