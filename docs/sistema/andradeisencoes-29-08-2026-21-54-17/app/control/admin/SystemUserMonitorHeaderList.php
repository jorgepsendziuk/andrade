<?php

class SystemUserMonitorHeaderList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'permission';
    private static $activeRecord = 'SystemUsers';
    private static $primaryKey = 'id';
    private static $formName = 'formList_SystemUsers';
    private $showMethods = ['onReload', 'onSearch'];
    private $limit = 20;

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct($param = null)
    {
        parent::__construct();
        // creates the form

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->limit = 40;

        $id = new TEntry('id');
        $name = new TEntry('name');
        $login = new TEntry('login');
        $email = new TEntry('email');

        $id->exitOnEnter();
        $name->exitOnEnter();
        $login->exitOnEnter();
        $email->exitOnEnter();

        $id->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $name->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $login->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $email->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));

        $id->setSize('100%');
        $name->setSize('100%');
        $login->setSize('100%');
        $email->setSize('100%');

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm(self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_id = new TDataGridColumn('id', "Id", 'center' , '70px');
        $column_name = new TDataGridColumn('name', _t("Name"), 'left');
        $column_login = new TDataGridColumn('login', "Login", 'left');
        $column_email = new TDataGridColumn('email', "Email", 'left');
        $column_active = new TDataGridColumn('active', _t("Active"), 'center');
        $column_status = new TDataGridColumn('status', "Status", 'center');
        $column_last_action = new TDataGridColumn('last_action', _t('Last action executed'), 'center');

        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_name);
        $this->datagrid->addColumn($column_login);
        $this->datagrid->addColumn($column_email);
        $this->datagrid->addColumn($column_last_action);
        $this->datagrid->addColumn($column_active);
        $this->datagrid->addColumn($column_status);
        
        $column_active->setTransformer( function($value, $object, $row) {
            $class = ($value=='N') ? 'danger' : 'success';
            $label = ($value=='N') ? _t('No') : _t('Yes');
            $div = new TElement('span');
            $div->class="label label-{$class}";
            $div->style="text-shadow:none; font-size:12px; font-weight:lighter";
            $div->add($label);
            return $div;
        });

        $firebaseUsers = BuilderFirebaseService::getUsers();

        $column_status->setTransformer( function($value, $object, $row) use ($firebaseUsers) {
            
            if($object->id == TSession::getValue('userid'))
            {
                return '<label style="font-size:12px; font-weight:lighter" class="label label-success">Online </label>';
            }
            else if(!empty($firebaseUsers[$object->id]))
            {
                $user = $firebaseUsers[$object->id];
                if($user['status'] == 'online')
                {
                    return '<label style="font-size:12px; font-weight:lighter" class="label label-success">Online </label>';
                }
            }  

            return '<label style="font-size:12px; font-weight:lighter" class="label label-danger">Offline </label>';
        });

        $column_last_action->setTransformer( function($value, $object, $row) use ($firebaseUsers) {
            
            if(!empty($firebaseUsers[$object->id]))
            {
                $user = $firebaseUsers[$object->id];
                if(!empty($user['last_action']))
                {
                    return str_replace(['engine.php?class='], [''], $user['last_action']);
                }
            }  

            return '';
        });

        $action_onDisconnect = new TDataGridAction(array('SystemUserMonitorHeaderList', 'onDisconnect'));
        $action_onDisconnect->setUseButton(false);
        $action_onDisconnect->setButtonClass('btn btn-default btn-sm');
        $action_onDisconnect->setLabel(_t('Disconnect user'));
        $action_onDisconnect->setImage('fas:plug #FF5722');
        $action_onDisconnect->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onDisconnect);

        // create the datagrid model
        $this->datagrid->createModel();

        $tr = new TElement('tr');
        $this->datagrid->prependRow($tr);

        $tr->add(TElement::tag('td', ''));
        $td_id = TElement::tag('td', $id);
        $tr->add($td_id);
        $td_name = TElement::tag('td', $name);
        $tr->add($td_name);
        $td_login = TElement::tag('td', $login);
        $tr->add($td_login);
        $td_email = TElement::tag('td', $email);
        $tr->add($td_email);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);

        $this->datagrid_form->addField($id);
        $this->datagrid_form->addField($name);
        $this->datagrid_form->addField($login);
        $this->datagrid_form->addField($email);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
        $panel->getHeader()->style = ' display:none !important; ';
        $panel->getBody()->class .= ' table-responsive';

        $panel->addFooter($this->pageNavigation);

        $headerActions = new TElement('div');
        $headerActions->class = ' datagrid-header-actions ';

        $head_left_actions = new TElement('div');
        $head_left_actions->class = ' datagrid-header-actions-left-actions ';

        $head_right_actions = new TElement('div');
        $head_right_actions->class = ' datagrid-header-actions-left-actions ';

        $headerActions->add($head_left_actions);
        $headerActions->add($head_right_actions);

        $this->datagrid_form->add($this->datagrid);
        $panel->add($headerActions);
        $panel->add($this->datagrid_form);

        $button_refresh = new TButton('button_refresh');
        $button_refresh->setAction(new TAction(['SystemGroupList', 'onRefresh']), _t("Refresh"));
        $button_refresh->addStyleClass('btn-default');
        $button_refresh->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_refresh);

        $button_clear_filters = new TButton('button_clear_filters');
        $button_clear_filters->setAction(new TAction(['SystemGroupList', 'onClearFilters']), _t("Clear filters"));
        $button_clear_filters->addStyleClass('btn-default');
        $button_clear_filters->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_clear_filters);

        $head_left_actions->add($button_refresh);
        $head_left_actions->add($button_clear_filters);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($panel);

        parent::add($container);
    }

    public static function onDisconnect($param = null) 
    {
        try 
        {
            new TQuestion(_t('Disconnect user?'), new TAction([__CLASS__, 'onYesDisconnect'], $param), new TAction([__CLASS__, 'onNoDisconnect'], $param));            
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onRefresh($param = null) 
    {
        $this->onReload([]);
    }

    public function onClearFilters($param = null) 
    {
        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    /**
     * Register the filter in the session
     */
    public function onSearch($param = null)
    {
        // get the search form data
        $data = $this->datagrid_form->getData();
        $filters = [];

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->id) AND ( (is_scalar($data->id) AND $data->id !== '') OR (is_array($data->id) AND (!empty($data->id)) )) )
        {
            $filters[] = new TFilter('id', '=', $data->id);// create the filter 
        }

        if (isset($data->name) AND ( (is_scalar($data->name) AND $data->name !== '') OR (is_array($data->name) AND (!empty($data->name)) )) )
        {
            $filters[] = new TFilter('name', 'like', "%{$data->name}%");// create the filter 
        }

        if (isset($data->login) AND ( (is_scalar($data->login) AND $data->login !== '') OR (is_array($data->login) AND (!empty($data->login)) )) )
        {
            $filters[] = new TFilter('login', 'like', "%{$data->login}%");// create the filter 
        }

        if (isset($data->email) AND ( (is_scalar($data->email) AND $data->email !== '') OR (is_array($data->email) AND (!empty($data->email)) )) )
        {
            $filters[] = new TFilter('email', 'like', "%{$data->email}%");// create the filter 
        }

        // fill the form with data again
        $this->datagrid_form->setData($data);

        // keep the search data in the session
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filters', $filters);

        if (isset($param['static']) && ($param['static'] == '1') )
        {
            $class = get_class($this);
            $onReloadParam = ['offset' => 0, 'first_page' => 1, 'target_container' => $param['target_container'] ?? null];
            AdiantiCoreApplication::loadPage($class, 'onReload', $onReloadParam);
            TScript::create('$(".select2").prev().select2("close");');
        }
        else
        {
            $this->onReload(['offset' => 0, 'first_page' => 1]);
        }
    }

    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        try
        {
            // open a transaction with database 'permission'
            TTransaction::open(self::$database);

            // creates a repository for SystemUsers
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'id';    
            }
            if (empty($param['direction']))
            {
                $param['direction'] = 'desc';
            }

            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $this->limit);

            if($filters = TSession::getValue(__CLASS__.'_filters'))
            {
                foreach ($filters as $filter) 
                {
                    $criteria->add($filter);       
                }
            }

            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    $row = $this->datagrid->addItem($object);
                    $row->id = "row_{$object->id}";
                }
            }

            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);

            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($this->limit); // limit

            // close the transaction
            TTransaction::close();
            $this->loaded = true;

            return $objects;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {

    }

    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  $this->showMethods))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }

    public static function onYesDisconnect($param = null) 
    {
        try 
        {
            BuilderFirebaseService::setUserAttribute($param['key'], 'command', 'force_logout');
            self::manageRow($param['key']);
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onNoDisconnect($param = null) 
    {
        try 
        {
            //code here
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function manageRow($id)
    {
        $list = new self([]);

        $openTransaction = TTransaction::getDatabase() != self::$database ? true : false;

        if($openTransaction)
        {
            TTransaction::open(self::$database);    
        }

        $object = new SystemUsers($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}