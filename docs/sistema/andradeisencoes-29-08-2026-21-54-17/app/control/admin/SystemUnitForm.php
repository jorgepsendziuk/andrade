<?php
/**
 * SystemUnitForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class SystemUnitForm extends TStandardForm
{
    protected $form; // form
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();
        
        $ini  = AdiantiApplicationConfig::get();
        
        $this->setDatabase('permission');              // defines the database
        $this->setActiveRecord('SystemUnit');     // defines the active record
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_SystemUnit');
        $this->form->setFormTitle(_t('Unit'));
        
        // create the form fields
        $id = new TEntry('id');
        $name = new TEntry('name');
        
        // add the fields
        $row = $this->form->addFields( [new TLabel('ID', null, null, null, '100%'), $id], [new TLabel(_t('Name'), null, null, null, '100%'), $name] );
        $row->layout = ['col-sm-2','col-sm-10'];
        
        if (!empty($ini['general']['multi_database']) and $ini['general']['multi_database'] == '1')
        {
            $database = new TCombo('connection_name');
            $database->addItems( SystemDatabaseInformationService::getConnections() );
            $row = $this->form->addFields( [new TLabel(_t('Database'), null, null, null, '100%'), $database] );
            $row->layout = ['col-sm-12'];
            $database->setSize('100%');
        }
        
        $id->setEditable(FALSE);
        $id->setSize('100%');
        $name->setSize('100%');
        $name->addValidation( _t('Name'), new TRequiredValidator );
        
        // create the form actions
        $btn = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave')), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';
        $this->form->addActionLink(_t('Clear'),  new TAction(array($this, 'onEdit')), 'fa:eraser red');
        $this->form->addActionLink(_t('Back'),new TAction(array('SystemUnitList','onReload')),'far:arrow-alt-circle-left blue');
        
        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel(_t("Close"));
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);
        
        // add the container to the page
        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=SystemUnitForm]');
        $style->width = '70% !important';   
        $style->show(true);
    }
}
