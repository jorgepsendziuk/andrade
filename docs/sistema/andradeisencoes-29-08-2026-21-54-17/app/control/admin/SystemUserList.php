<?php

use Adianti\Base\AdiantiStandardListExportTrait;

class SystemUserList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private $database = 'permission';
    private static $activeRecord = 'SystemUsers';
    private static $primaryKey = 'id';
    private static $formName = 'formList_SystemUsers';
    private $showMethods = ['onReload', 'onSearch'];
    private $limit = 20;

    use AdiantiStandardListExportTrait;

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct($param = null)
    {
        parent::__construct();
        // creates the form

        $this->limit = 20;

        $id = new TEntry('id');
        $name = new TEntry('name');
        $login = new TEntry('login');
        $email = new TEntry('email');
        $active = new TCombo('active');

        $id->exitOnEnter();
        $name->exitOnEnter();
        $login->exitOnEnter();
        $email->exitOnEnter();

        $id->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $name->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $login->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $email->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $active->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $active->addItems( [ 'Y' => _t('Yes'), 'N' => _t('No') ] );
        $id->setSize('100%');
        $name->setSize('100%');
        $login->setSize('100%');
        $email->setSize('100%');
        $active->setSize('100%');

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->disableHtmlConversion();

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
        $column_active = new TDataGridColumn('active', "Active", 'left');
        $column_two_factor_enabled = new TDataGridColumn('two_factor_enabled', "2FA", 'center');
        $column_term_policy = new TDataGridColumn('accepted_term_policy', _t('Terms of use and privacy policy'), 'center');

        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_name);
        $this->datagrid->addColumn($column_login);
        $this->datagrid->addColumn($column_email);
        $this->datagrid->addColumn($column_two_factor_enabled);
        $this->datagrid->addColumn($column_active);

        if (!empty($ini['general']['require_terms']) && $ini['general']['require_terms'] == '1')
        {
            $this->datagrid->addColumn($column_term_policy);
        }

        $column_active->setTransformer( function($value, $object, $row) {
            $class = ($value=='N') ? 'danger' : 'success';
            $label = ($value=='N') ? _t('No') : _t('Yes');
            $div = new TElement('span');
            $div->class="label label-{$class}";
            $div->style="text-shadow:none; font-size:12px; font-weight:lighter";
            $div->add($label);
            return $div;
        });

        $column_term_policy->setTransformer( function($value, $object, $row) {
            $class = (empty($value) || $value=='N') ? 'danger' : 'success';
            $label = (empty($value) || $value=='N') ? _t('No') : _t('Yes');
            $div = new TElement('span');
            $div->class="label label-{$class}";
            $div->style="text-shadow:none; font-size:12px; font-weight:lighter";

            if ($value == 'Y')
            {
                $div->title = TDateTime::convertToMask($object->accepted_term_policy_at, 'yyyy-mm-dd hh:ii:ss', 'dd/mm/yyyy hh:ii');
            }
            
            $div->add($label);
            return $div;
        });

        $column_two_factor_enabled->setTransformer( function($value, $object, $row) {
            $class = (empty($value) || $value=='N') ? 'danger' : 'success';
            $label = (empty($value) || $value=='N') ? _t('No') : _t('Yes');
            $div = new TElement('span');
            $div->class="label label-{$class}";
            $div->style="text-shadow:none; font-size:12px; font-weight:lighter";
            
            $div->add($label);
            return $div;
        });

        // creates the datagrid column actions
        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);
        
        $order_name = new TAction(array($this, 'onReload'));
        $order_name->setParameter('order', 'name');
        $column_name->setAction($order_name);
        
        $order_login = new TAction(array($this, 'onReload'));
        $order_login->setParameter('order', 'login');
        $column_login->setAction($order_login);
        
        $order_email = new TAction(array($this, 'onReload'));
        $order_email->setParameter('order', 'email');
        $column_email->setAction($order_email);
        
        // create EDIT action
        $action_edit = new TDataGridAction(array('SystemUserForm', 'onEdit'));
        $action_edit->setButtonClass('btn btn-default');
        $action_edit->setLabel(_t('Edit'));
        $action_edit->setImage('far:edit #478fca');
        $action_edit->setField('id');
        $this->datagrid->addAction($action_edit);
        
        // create DELETE action
        $action_del = new TDataGridAction(array($this, 'onDelete'));
        $action_del->setButtonClass('btn btn-default');
        $action_del->setLabel(_t('Delete'));
        $action_del->setImage('far:trash-alt #dd5a43');
        $action_del->setField('id');
        $this->datagrid->addAction($action_del);
        
        // create CLONE action
        $action_clone = new TDataGridAction(array($this, 'onClone'));
        $action_clone->setButtonClass('btn btn-default');
        $action_clone->setLabel(_t('Clone'));
        $action_clone->setImage('far:clone green');
        $action_clone->setField('id');
        $this->datagrid->addAction($action_clone);
        
        // create ONOFF action
        $action_onoff = new TDataGridAction(array($this, 'onTurnOnOff'));
        $action_onoff->setButtonClass('btn btn-default');
        $action_onoff->setLabel(_t('Activate/Deactivate'));
        $action_onoff->setImage('fa:power-off orange');
        $action_onoff->setField('id');
        $this->datagrid->addAction($action_onoff);
        
        // create ONOFF action
        $action_person = new TDataGridAction(array($this, 'onImpersonation'));
        $action_person->setButtonClass('btn btn-default');
        $action_person->setLabel(_t('Impersonation'));
        $action_person->setImage('far:user-circle gray');
        $action_person->setFields(['id','login']);
        $this->datagrid->addAction($action_person);

        // create the datagrid model
        $this->datagrid->createModel();

        $tr = new TElement('tr');
        $this->datagrid->prependRow($tr);

        $tr->add(TElement::tag('td', ''));
        $tr->add(TElement::tag('td', ''));
        $tr->add(TElement::tag('td', ''));
        $tr->add(TElement::tag('td', ''));
        $tr->add(TElement::tag('td', ''));
        $td_id = TElement::tag('td', $id);
        $tr->add($td_id);
        $td_name = TElement::tag('td', $name);
        $tr->add($td_name);
        $td_login = TElement::tag('td', $login);
        $tr->add($td_login);
        $td_email = TElement::tag('td', $email);
        $tr->add($td_email);
        $tr->add(TElement::tag('td', ''));
        $td_active = TElement::tag('td', $active);
        $tr->add($td_active);
        if (!empty($ini['general']['require_terms']) && $ini['general']['require_terms'] == '1')
        {
            $tr->add(TElement::tag('td', ''));
        }

        $this->datagrid_form->addField($id);
        $this->datagrid_form->addField($name);
        $this->datagrid_form->addField($login);
        $this->datagrid_form->addField($email);
        $this->datagrid_form->addField($active);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup(_t('Users'));
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

        $button_new = new TButton('button_new');
        $button_new->setAction(new TAction(['SystemUserForm', 'onEdit']), _t('New'));
        $button_new->addStyleClass('btn-default');
        $button_new->setImage('fas:plus #69aa46');

        $this->datagrid_form->addField($button_new);

        $button_refresh = new TButton('button_refresh');
        $button_refresh->setAction(new TAction(['SystemUserList', 'onRefresh']), _t("Refresh"));
        $button_refresh->addStyleClass('btn-default');
        $button_refresh->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_refresh);

        $button_clear_filters = new TButton('button_clear_filters');
        $button_clear_filters->setAction(new TAction(['SystemUserList', 'onClearFilters']), _t("Clear filters"));
        $button_clear_filters->addStyleClass('btn-default');
        $button_clear_filters->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_clear_filters);

        $dropdown_button_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_exportar->setPullSide('right');
        $dropdown_button_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_exportar->addPostAction( "CSV", new TAction(['SystemUserList', 'onExportCsv'],['static' => 1]), self::$formName, 'fas:file-csv #00b894' );
        $dropdown_button_exportar->addPostAction( "XLS", new TAction(['SystemUserList', 'onExportXls'],['static' => 1]), self::$formName, 'fas:file-excel #4CAF50' );
        $dropdown_button_exportar->addPostAction( "PDF", new TAction(['SystemUserList', 'onExportPdf'],['static' => 1]), self::$formName, 'far:file-pdf #e74c3c' );

        $head_left_actions->add($button_new);
        $head_left_actions->add($button_refresh);
        $head_left_actions->add($button_clear_filters);

        $head_right_actions->add($dropdown_button_exportar);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        $container->add($panel);

        parent::add($container);
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

        $this->datagrid_form->clear();
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

        if (isset($data->active) AND ( (is_scalar($data->active) AND $data->active !== '') OR (is_array($data->active) AND (!empty($data->active)) )) )
        {

            $filters[] = new TFilter('active', '=', $data->active);// create the filter 
        }

        // fill the form with data again
        $this->datagrid_form->setData($data);

        // keep the search data in the session
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filters', $filters);

        if (isset($param['static']) && ($param['static'] == '1') )
        {
            $class = get_class($this);
            $onReloadParam = ['offset' => 0, 'first_page' => 1, ];
            AdiantiCoreApplication::loadPage($class, 'onReload', $onReloadParam);
            TScript::create('$(".select2").prev().select2("close");');
        }
        else
        {
            $this->onReload(['offset' => 0, 'first_page' => 1]);
        }
    }

    public function onDelete($param = null) 
    { 
        if(isset($param['delete']) && $param['delete'] == 1)
        {
            try
            {
                // get the paramseter $key
                $key = $param['key'];
                // open a transaction with database
                TTransaction::open($this->database);

                // instantiates object
                $object = new SystemUsers($key, FALSE); 

                // deletes the object from the database
                $object->delete();

                // close the transaction
                TTransaction::close();

                // reload the listing
                $this->onReload( $param );
                // shows the success message
                new TMessage('info', AdiantiCoreTranslator::translate('Record deleted'));
            }
            catch (Exception $e) // in case of exception
            {
                // shows the exception error message
                new TMessage('error', $e->getMessage());
                // undo all pending operations
                TTransaction::rollback();
            }
        }
        else
        {
            // define the delete action
            $action = new TAction(array($this, 'onDelete'));
            $action->setParameters($param); // pass the key paramseter ahead
            $action->setParameter('delete', 1);
            // shows a dialog to the user
            new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);   
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
            TTransaction::open($this->database);

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

    /**
     * Turn on/off an user
     */
    public function onTurnOnOff($param)
    {
        try
        {
            TTransaction::open('permission');
            $user = SystemUsers::find($param['id']);
            if ($user instanceof SystemUsers)
            {
                $user->active = $user->active == 'Y' ? 'N' : 'Y';
                $user->store();
            }
            
            TTransaction::close();
            
            $this->onReload($param);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * Clone group
     */
    public function onClone($param)
    {
        try
        {
            TTransaction::open('permission');
            $user = new SystemUsers($param['id']);
            $user->cloneUser();
            TTransaction::close();
            
            $this->onReload();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    /**
     * Impersonation user
     */
    public function onImpersonation($param)
    {
        try
        {
            $login_impersonated = TSession::getValue('login');

            TTransaction::open('permission');
            TSession::regenerate();
            $user = SystemUsers::validate( $param['login'] );
            ApplicationAuthenticationService::loadSessionVars($user);
            SystemAccessLogService::registerLogin(true, $login_impersonated);
            AdiantiCoreApplication::gotoPage('EmptyPage');
            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
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

}

