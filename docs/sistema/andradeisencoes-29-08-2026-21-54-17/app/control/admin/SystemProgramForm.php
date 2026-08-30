<?php

use Adianti\Control\TAction;
use Adianti\Database\TTransaction;
use Adianti\Widget\Form\TCheckGroup;
use Adianti\Widget\Form\TEntry;
use Adianti\Widget\Form\TFieldList;
use Adianti\Widget\Form\TForm;

/**
 * SystemProgramForm
 *
 * @version    1.0
 * @package    control
 * @subpackage admin
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class SystemProgramForm extends TStandardForm
{
    protected $form; // form
    
    use BuilderMasterDetailFieldListTrait;

    /**
     * Class constructor
     * Creates the page and the registration form
     */
    function __construct($param)
    {
        parent::__construct();
                
        // creates the form
        
        $this->form = new BootstrapFormBuilder('form_SystemProgram');
        $this->form->setFormTitle(_t('Program'));
        
        // defines the database
        parent::setDatabase('permission');
        
        // defines the active record
        parent::setActiveRecord('SystemProgram');
        
        // create the form fields
        $id            = new TEntry('id');
        $controller    = new TUniqueSearch('controller');
        $name          = new TEntry('name');
        $this->group_list = new TCheckList('groups');
        
        $system_program_method_id = new THidden('system_program_method_id[]');
        $system_program_method___row__id = new THidden('system_program_method___row__id[]');
        $system_program_method___row__data = new THidden('system_program_method___row__data[]');
        $system_program_method_name = new TEntry('system_program_method_name[]');
        $system_program_method_action = new TEntry('system_program_method_action[]');
        $system_program_method_name->setExitAction(new TAction([$this, 'onChangeAction']));
        $system_program_method_action->setExitAction(new TAction([$this, 'onChangeAction']));
        
        $system_program_method_action->setCompletion([]);
        $system_program_method_action->setMinLength(0);

        $this->system_program_method_action = $system_program_method_action;

        $this->fieldList_actions = new TFieldList();

        $this->fieldList_actions->addField(null, $system_program_method_id, []);
        $this->fieldList_actions->addField(null, $system_program_method___row__id, ['uniqid' => true]);
        $this->fieldList_actions->addField(null, $system_program_method___row__data, []);
        $this->fieldList_actions->addField(new TLabel(_t("Name"), null, '14px', null), $system_program_method_name, ['width' => '50%']);
        $this->fieldList_actions->addField(new TLabel(_t("Action"), null, '14px', null), $system_program_method_action, ['width' => '50%']);

        $this->fieldList_actions->width = '100%';
        $this->fieldList_actions->setFieldPrefix('system_program_method');
        $this->fieldList_actions->name = 'fieldList_actions';
        $this->fieldList_actions->class .= ' table-responsive';

        $this->criteria_fieldList_actions = new TCriteria();

        $this->form->addField($system_program_method_id);
        $this->form->addField($system_program_method___row__id);
        $this->form->addField($system_program_method___row__data);
        $this->form->addField($system_program_method_name);
        $this->form->addField($system_program_method_action);

        $this->fieldList_actions->setRemoveFunction("ttable_remove_row(this); setTimeout(function(){__adianti_post_exec('class=SystemProgramForm&method=onChangeAction&static=1', $('#form_SystemProgram').serialize(), null, 1, true) }, 100);");
        
        $this->group_list->setHeight(450);
        $this->group_list->makeScrollable();
        $this->group_list->setIdColumn('id');
        $this->group_list->addColumn('id',    'ID',    'center',  '5%');
        $col_name = $this->group_list->addColumn('name', _t('Name'),    'left',   '40%');
        $col_actions = $this->group_list->addColumn('action', _t('Actions'),    'left',   '55%');

        $col_name->enableSearch();
        $search_program = $col_name->getInputSearch();
        $search_program->placeholder = _t('Search');
        $search_program->style = 'margin-left: 4px; border-radius: 4px';

        $col_actions->setTransformer( function($value, $object, $row) use ($param) {
            $id = $param['key'] ?? null;
            $program = new SystemProgram($id);

            $programMethodsChecks = new TCheckGroup("{$object->id}_actions");
            
            $actionsGroupProgram = [];
            $items = [];
            
            if($program->actions)
            {
                $actions = json_decode($program->actions);
                
                if($actions)
                {
                    $groupProgram = SystemGroupProgram::where('system_group_id', '=', $object->id)->where('system_program_id', '=', $id)->where('actions', 'is not', null)->first();
                    
                    if ($groupProgram && $groupProgram->actions)
                    {
                        $actionsGroupProgram = json_decode($groupProgram->actions);
                    }
                
                    foreach($actions as $action)
                    {
                        $items[$action->action] = "{$action->name} <small>({$action->action})</small>";
                    }
                }
            }
            
            $programMethodsChecks->addItems($items);
            $programMethodsChecks->setValue($actionsGroupProgram);
            
            $div = new TElement('div');
            $div->id = 'actions';
            $div->add($programMethodsChecks);

            return $div;
        });

        $id->setEditable(false);
        $controller->addItems($this->getPrograms( empty($param['id']) ));
        $controller->setMinLength(0);
        $controller->setChangeAction(new TAction([$this, 'onChangeController']));
        
        // add the fields
        $this->form->addFields( [new TLabel('ID', null, null, null, '100%'), $id] );
        $row = $this->form->addFields( [new TLabel(_t('Controller'), null, null, null, '100%'), $controller], [new TLabel(_t('Name'), null, null, null, '100%'), $name] );
        $row->layout = ['col-sm-6','col-sm-6'];

        $row = $this->form->addContent(['']);
        $row->layout = [' col-sm-12'];
        
        $row = $this->form->addContent([new TFormSeparator("Ações")]);
        $row->layout = [' col-sm-12'];

        $row = $this->form->addContent(["<small>"._t('When defining actions, they become mandatory, meaning the user will only be able to execute them if they are associated with a group')."</small>"]);
        $row->layout = [' col-sm-12'];

        $row = $this->form->addContent([$this->fieldList_actions]);
        $row->layout = [' col-sm-12'];
        
        $row = $this->form->addContent([new TFormSeparator("Grupos")]);
        $row->layout = [' col-sm-12'];
        
        $row = $this->form->addFields([$this->group_list] );
        $row->layout = [' col-sm-12' ];

        TTransaction::open('permission');
        $this->group_list->addItems( SystemGroup::get() );
        TTransaction::close();
        
        $id->setSize(100);
        $name->setSize('100%');
        $controller->setSize('100%');
        
        $system_program_method_name->setSize('100%');
        $system_program_method_action->setSize('100%');
        
        // validations
        $name->addValidation(_t('Name'), new TRequiredValidator);
        $controller->addValidation(('Controller'), new TRequiredValidator);

        // add form actions
        $btn = $this->form->addAction(_t('Save'), new TAction(array($this, 'onSave'), ['static'=>1]), 'far:save');
        $btn->class = 'btn btn-sm btn-primary';
        
        $this->form->addActionLink(_t('Clear'), new TAction(array($this, 'onEdit')), 'fa:eraser red');
        $this->form->addActionLink(_t('Back'),new TAction(array('SystemProgramList','onReload')),'far:arrow-alt-circle-left blue');
 
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

        $style = new TStyle('right-panel > .container-part[page-name=SystemProgramForm]');
        $style->width = '70% !important';   
        $style->show(true);
    }
    
    public static function onChangeAction($param)
    {
        try
        {
            TTransaction::open('permission');
            
            $groups = SystemGroup::get();
            
            $items = [];
            $actions = $param['system_program_method_action']??[];
            $names = $param['system_program_method_name']??[];
            
            foreach($actions as $key => $action)
            {
                if (empty($names[$key]) || empty($action))
                {
                    continue;
                }
                
                $items[$action] = "<small>({$names[$key]} {$action})</small>";
            }
            
            $data = new stdClass;
            
            foreach($groups as $group)
            {
                $data->{"{$group->id}_actions"} = $param["{$group->id}_actions"] ?? [];
                TCheckGroup::reload('form_SystemProgram', "{$group->id}_actions", $items, []);
            }
            
            TForm::sendData('form_SystemProgram', $data);
            
            TTransaction::close();
        }
        catch(Exception $e)
        {
            TTransaction::rollback();
        }
    }

    
    
    public static function onGetControllerMethods($param = null) 
    {
        try 
        {
            if(!empty($param['controller']))
            {                
                $actions = self::getActionsFromController($param['controller']);
                
                TFieldList::clearRows('fieldList_actions');
                TEntry::reloadCompletion('"system_program_method_action[]"', $actions, ['minChars'=> 0], 100);
            }
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function getActionsFromController($controller)
    {
        $reflection = new ReflectionClass($controller);
            
        // Obtém todos os métodos da classe atual (ignorando métodos da classe pai)
        $metodos = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE);
        $actions = [];
        
        foreach ($metodos as $metodo) {
            
            if ($metodo->getDeclaringClass()->getName() === $controller && !in_array($metodo->getName(), [ '__construct', 'onShowCurtainFilters', 'onClearFilters', 'onRefresh', 'fireEvents', 'onReload', 'onShow', 'show', 'onSearch', 'getFormName', 'storeMasterDetailItems', 'loadMasterDetailItems'])) {
                $actions[] = $metodo->getName();
            }
        }

        return $actions;
    }

    /**
     * Change controller, generate name
     */
    public static function onChangeController($param)
    {
        if (!empty($param['controller']) AND empty($param['name']))
        {
            $obj = new stdClass;
            $obj->name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $param['controller']);
            TForm::sendData('form_SystemProgram', $obj);
        } 

        if(!empty($param['controller']))
        {
            self::onGetControllerMethods($param);
        }
    }
    
    /**
     * Return all the programs under app/control
     */
    public function getPrograms( $just_new_programs = false )
    {
        try
        {
            TTransaction::open('permission');
            $registered_programs = SystemProgram::getIndexedArray('id', 'controller');
            TTransaction::close();
            
            $entries = array();
            $iterator = new AppendIterator();
            $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app/control'), RecursiveIteratorIterator::CHILD_FIRST));
            $iterator->append(new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app/view'),    RecursiveIteratorIterator::CHILD_FIRST));
            
            foreach ($iterator as $arquivo)
            {
                if (substr($arquivo, -4) == '.php')
                {
                    $name = $arquivo->getFileName();
                    $pieces = explode('.', $name);
                    $class = (string) $pieces[0];
                    
                    if ($just_new_programs)
                    {
                        if (!in_array($class, $registered_programs) AND !in_array($class, array_keys(TApplication::getDefaultPermissions())) AND substr($class,0,6) !== 'System')
                        {
                            $entries[$class] = $class;
                        } 
                    }
                    else
                    {
                        $entries[$class] = $class;
                    }
                }
            }
            
            ksort($entries);
            return $entries;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
    
    /**
     * method onEdit()
     * Executed whenever the user clicks at the edit button da datagrid
     * @param  $param An array containing the GET ($_GET) parameters
     */
    public function onEdit($param)
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];
                
                TTransaction::open($this->database);
                $class = $this->activeRecord;
                $object = new $class($key);
                
                $groups = array();
                
                if( $groups_db = $object->getSystemGroups() )
                {
                    foreach( $groups_db as $group )
                    {
                        $groups[] = $group->id;
                    }
                }
                $object->groups = $groups;

                $this->system_program_method_action->setCompletion(self::getActionsFromController($object->controller));

                $this->fieldList_actions->addHeader();

                if($object->actions)
                {
                    $fieldListActons = json_decode($object->actions);
                    
                    if($fieldListActons)
                    {
                        $prefix = $this->fieldList_actions->getFieldPrefix();
                        foreach($fieldListActons as $fieldListAction)
                        {
                            $detailItem = new stdClass();
                            foreach ($fieldListAction as $attribute => $value) 
                            {
                                $detailItem->{"{$prefix}_{$attribute}"} = $value;
                            }
                            
                            $this->fieldList_actions->addDetail($detailItem);
                        }
                    }
                    else
                    {
                        $this->fieldList_actions->addDetail(new stdClass);
                    }
                }
                else
                {
                    $this->fieldList_actions->addDetail(new stdClass);
                }

                $this->fieldList_actions->addCloneAction();

                $this->form->setData($object);
                
                TTransaction::close();
                
                return $object;
            }
            else
            {
                $this->form->clear();

                $this->fieldList_actions->addHeader();
                $this->fieldList_actions->addDetail( new stdClass );

                $this->fieldList_actions->addCloneAction(null, 'fas:plus #69aa46', "Clonar");
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
    
    /**
     * method onSave()
     * Executed whenever the user clicks at the save button
     */
    public function onSave($param = null)
    {
        try
        {
            TTransaction::open($this->database);
            
            $data = $this->form->getData();
            
            $object = new SystemProgram;
            $object->id = $data->id;
            $object->name = $data->name;
            $object->controller = $data->controller;
            
            $this->form->validate();
            $object->store();
            $data->id = $object->id;
            
            $object->clearParts();
            
            if( !empty($data->groups) )
            {
                foreach( $data->groups as $group_id )
                {
                    $groupProgram = new SystemGroupProgram;
                    $groupProgram->system_program_id = $object->id;
                    $groupProgram->system_group_id = $group_id;
                    $groupProgram->actions = json_encode($param["{$group_id}_actions"]??[]);
                    $groupProgram->store();
                }
            }
            
            $fieldListActions   = $this->fieldList_actions->getPostData();

            if ($fieldListActions)
            {
                $actions = [];

                foreach ($fieldListActions as $row => $objectAction)
                {
                    if(!empty($objectAction->name) && !empty($objectAction->action))
                    {
                        $actions[] = (object) [
                            'name' => $objectAction->name,
                            'action' => $objectAction->action
                        ];
                    }
                }

                if($actions)
                {
                    $object->actions = json_encode($actions);
                }
                else
                {
                    $object->actions = null;
                }
            }
            else
            {
                $object->actions = null;
            }

            $object->store();

            TForm::sendData('form_SystemProgram', (object) ['id'=>$object->id]);

            TTransaction::close();
            
            new TMessage('info', TAdiantiCoreTranslator::translate('Record saved'), new TAction(['SystemProgramList', 'onReload']));
            
            TScript::create("Template.closeRightPanel();");
            
            return $object;
        }
        catch (Exception $e) // in case of exception
        {
            // get the form data
            $object = $this->form->getData($this->activeRecord);
            $this->form->setData($object);
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }
}
