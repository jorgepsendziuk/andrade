<?php

/**
 * SystemGroupForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class SystemGroupForm extends TPage
{
    protected $form; // form
    protected $program_list;
    protected $user_list;
    
    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct()
    {
        parent::__construct();
        
        // creates the form
        $this->form = new BootstrapFormBuilder('form_System_group');
        $this->form->setFormTitle( _t('Group') );

        // create the form fields
        $id   = new TEntry('id');
        $name = new TEntry('name');
        
        // define the sizes
        $id->setSize('100%');
        $name->setSize('100%');

        $name->addValidation('name', new TRequiredValidator);
        
        $id->setEditable(false);
        
        $row = $this->form->addFields( [new TLabel('ID', null, null, null, '100%'), $id], [new TLabel(_t('Name'), null, null, null, '100%'), $name] );
        $row->layout = ['col-sm-2','col-sm-10'];
        
        $this->program_list = new TCheckList('program_list');
        $this->program_list->setIdColumn('id');
        $this->program_list->addColumn('id',    'ID',    'center',  '10%');
        $col_name    = $this->program_list->addColumn('name', _t('Name'),    'left',   '90%');

        $this->program_list->setHeight(450);
        $this->program_list->makeScrollable();
        
        $col_name->enableSearch();
        $search_program = $col_name->getInputSearch();
        $search_program->placeholder = _t('Search');
        $search_program->style = 'margin-left: 4px; border-radius: 4px';

        $col_name->setTransformer( function($value, $object, $row) {
            
            if($object->actions)
            {
                $actions = json_decode($object->actions);
                if($actions)
                {
                    foreach($actions as $action)
                    {
                        $items[$action->action] = "{$action->name} <small>({$action->action})</small>";
                    }
                }

                $programMethodsChecks = new TCheckGroup("{$object->id}_actions");
                $programMethodsChecks->addItems($items);
                $programMethodsChecks->setLayout('horizontal');

                $div = new TElement('div');
                $div->add("{$object->name} <small>({$object->controller})</small>");
                
                $container = new BContainer('checks');
                $container->setTagName('div');
                $container->setTitle('Ações', '', '13px');
                $container->addContent([$programMethodsChecks]);
                $div->add($container);

                return $div;

            }

            return "{$object->name} <small>({$object->controller})</small>";

        });
        
        $this->user_list = new TCheckList('user_list');
        $this->user_list->setIdColumn('id');
        $this->user_list->addColumn('id',    'ID',    'center',  '10%');
        $col_user = $this->user_list->addColumn('name', _t('Name'),    'left',   '90%');
        $this->user_list->setHeight(400);
        $this->user_list->makeScrollable();
        
        $col_user->enableSearch();
        $search_user = $col_user->getInputSearch();
        $search_user->placeholder = _t('Search');
        $search_user->style = 'margin-left: 4px; border-radius: 4px';
        
        $subform = new BootstrapFormBuilder;
        $subform->setProperty('style', 'border:none; box-shadow:none');
        
        $subform->appendPage( _t('Programs') );
        $subform->addFields( [$this->program_list] );
        
        $subform->appendPage( _t('Users') );
        $subform->addFields( [$this->user_list] );
        
        $this->form->addContent( [$subform] );
        
        TTransaction::open('permission');
        $this->program_list->addItems( SystemProgram::get() );
        $this->user_list->addItems( SystemUsers::get() );
        TTransaction::close();
        
        $btn = $this->form->addAction( _t('Save'), new TAction(array($this, 'onSave'), ['static' => 1]), 'far:save' );
        $btn->class = 'btn btn-sm btn-primary';
        
        $this->form->addActionLink( _t('Clear'), new TAction(array($this, 'onEdit')),  'fa:eraser red' );
        $this->form->addActionLink( _t('Back'), new TAction(array('SystemGroupList','onReload')),  'far:arrow-alt-circle-left blue' );
        
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

        $style = new TStyle('right-panel > .container-part[page-name=SystemGroupForm]');
        $style->width = '70% !important';   
        $style->show(true);

        
        $style = new TStyle('bContainer-fieldset .panel-body');
        $style->padding = '3px 0px 0px 0px !important';   
        $style->show(true);

        $style = new TStyle('card-body [widget="bootstrapformbuilder"]:not(.bContainer-fieldset) .card-body .tab-pane');
        $style->padding = '10px 10px 0px 10px !important';   
        $style->show(true);


        $style = new TStyle('bContainer-fieldset');
        $style->position = 'relative';   
        $style->show(true);
    }
    
    /**
     * method onSave()
     * Executed whenever the user clicks at the save button
     */
    public function onSave($param)
    {
        try
        {
            // open a transaction with database 'permission'
            TTransaction::open('permission');
            
            $data = $this->form->getData();
            $this->form->setData($data);
            
            // get the form data into an active record System_group
            $object = new SystemGroup;
            $object->fromArray( (array) $data );
            $object->store();
            $object->clearParts();

            if (!empty($data->program_list))
            {
                foreach ($data->program_list as $program_id)
                {
                    $groupProgram = new SystemGroupProgram;
                    $groupProgram->system_program_id = $program_id;
                    $groupProgram->system_group_id = $object->id;
                    $groupProgram->actions = json_encode($param["{$program_id}_actions"]??[]);
                    $groupProgram->store();
                }
            }
            
            if (!empty($data->user_list))
            {
                foreach ($data->user_list as $user_id)
                {
                    $object->addSystemUser( new SystemUsers( $user_id ) );
                }
            }
            
            $data = new stdClass;
            $data->id = $object->id;
            TForm::sendData('form_System_group', $data);
            
            TTransaction::close(); // close the transaction
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'), new TAction(['SystemGroupList', 'onReload']));
            
            TScript::create("Template.closeRightPanel();");

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * method onEdit()
     * Executed whenever the user clicks at the edit button da datagrid
     */
    function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                // get the parameter $key
                $key=$param['key'];
                
                // open a transaction with database 'permission'
                TTransaction::open('permission');
                
                // instantiates object System_group
                $object = new SystemGroup($key);
                
                $data = new stdClass;
                
                $program_ids = array();
                $system_group_programs = SystemGroupProgram::where('system_group_id', '=', $key)->load();
                foreach ($system_group_programs as $program)
                {
                    $program_ids[] = $program->system_program_id;
                    if($program->actions)
                    {
                        $data->{"{$program->system_program_id}_actions"} = json_decode($program->actions);
                    }
                }
                
                $object->program_list = $program_ids;
                
                
                $user_ids = array();
                foreach ($object->getSystemUsers() as $user)
                {
                    $user_ids[] = $user->id;
                }
                
                $object->user_list = $user_ids;
                
                TForm::sendData('form_System_group', $data);
                // fill the form with the active record data
                $this->form->setData($object);
                
                // close the transaction
                TTransaction::close();
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
