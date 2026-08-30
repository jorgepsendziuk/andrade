<?php

use Adianti\Base\AdiantiStandardListExportTrait;

class SystemProgramList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private $database = 'permission';
    private static $activeRecord = 'SystemProgram';
    private static $primaryKey = 'id';
    private static $formName = 'formList_SystemProgram';
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
        $controller = new TEntry('controller');

        $id->exitOnEnter();
        $name->exitOnEnter();
        $controller->exitOnEnter();

        $id->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $name->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $controller->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $id->setSize('100%');
        $name->setSize('100%');
        $controller->setSize('100%');

        // creates a Datagrid
        $this->datagrid = new TDataGrid;

        $this->datagrid_form = new TForm(self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_id = new TDataGridColumn('id', "Id", 'center' , '70px');
        $column_name = new TDataGridColumn('name', _t("Name"), 'left');
        $column_controller = new TDataGridColumn('controller', _t("Controller"), 'left');
        $column_menu = new TDataGridColumn('controller', _t('Menu path'), 'left');

        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);

        $column_menu->setTransformer( function($value, $object, $row) {
            $menuparser = new TMenuParser('menu.xml');
            $paths = $menuparser->getPath($value);
            
            if ($paths)
            {
                return implode(' &raquo; ', $paths);
            }
        });

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_name);
        $this->datagrid->addColumn($column_controller);
        $this->datagrid->addColumn($column_menu);

        $action_onEdit = new TDataGridAction(array('SystemProgramForm', 'onEdit'));
        $action_onEdit->setUseButton(false);
        $action_onEdit->setButtonClass('btn btn-default btn-sm');
        $action_onEdit->setLabel(_t("Edit"));
        $action_onEdit->setImage('far:edit #478fca');
        $action_onEdit->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onEdit);

        $action_onDelete = new TDataGridAction(array($this, 'onDelete'));
        $action_onDelete->setUseButton(false);
        $action_onDelete->setButtonClass('btn btn-default btn-sm');
        $action_onDelete->setLabel(_t("Delete"));
        $action_onDelete->setImage('fas:trash-alt #dd5a43');
        $action_onDelete->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onDelete);

        $action_onOpen = new TDataGridAction(array($this, 'onOpen'));
        $action_onOpen->setButtonClass('btn btn-default');
        $action_onOpen->setLabel(_t('Open'));
        $action_onOpen->setImage('far:folder-open green ');
        $action_onOpen->setField('controller');

        $this->datagrid->addAction($action_onOpen);

        // create the datagrid model
        $this->datagrid->createModel();

        $tr = new TElement('tr');
        $this->datagrid->prependRow($tr);

        $tr->add(TElement::tag('td', ''));
        $tr->add(TElement::tag('td', ''));
        $tr->add(TElement::tag('td', ''));
        $td_id = TElement::tag('td', $id);
        $tr->add($td_id);
        $td_name = TElement::tag('td', $name);
        $tr->add($td_name);
        $td_controller = TElement::tag('td', $controller);
        $tr->add($td_controller);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);

        $this->datagrid_form->addField($id);
        $this->datagrid_form->addField($name);
        $this->datagrid_form->addField($controller);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup(_t("Programs"));
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
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
        $button_new->setAction(new TAction(['SystemProgramForm', 'onEdit']), _t("New"));
        $button_new->addStyleClass('btn-default');
        $button_new->setImage('fas:plus #69aa46');

        $this->datagrid_form->addField($button_new);

        $button_refresh = new TButton('button_refresh');
        $button_refresh->setAction(new TAction(['SystemProgramList', 'onRefresh']), _t("Refresh"));
        $button_refresh->addStyleClass('btn-default');
        $button_refresh->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_refresh);

        $button_clear_filters = new TButton('button_clear_filters');
        $button_clear_filters->setAction(new TAction(['SystemProgramList', 'onClearFilters']), _t("Clear filters"));
        $button_clear_filters->addStyleClass('btn-default');
        $button_clear_filters->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_clear_filters);

        $dropdown_button_export = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_export->setPullSide('right');
        $dropdown_button_export->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_export->addPostAction( "CSV", new TAction(['SystemProgramList', 'onExportCsv'],['static' => 1]), self::$formName, 'fas:file-csv #00b894' );
        $dropdown_button_export->addPostAction( "XLS", new TAction(['SystemProgramList', 'onExportXls'],['static' => 1]), self::$formName, 'fas:file-excel #4CAF50' );
        $dropdown_button_export->addPostAction( "PDF", new TAction(['SystemProgramList', 'onExportPdf'],['static' => 1]), self::$formName, 'far:file-pdf #e74c3c' );

        $head_left_actions->add($button_new);
        $head_left_actions->add($button_refresh);
        $head_left_actions->add($button_clear_filters);

        $head_right_actions->add($dropdown_button_export);

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

        if (isset($data->controller) AND ( (is_scalar($data->controller) AND $data->controller !== '') OR (is_array($data->controller) AND (!empty($data->controller)) )) )
        {
            $filters[] = new TFilter('controller', 'like', "%{$data->controller}%");// create the filter 
        }

        // fill the form with data again
        $this->datagrid_form->setData($data);

        // keep the search data in the session
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filters', $filters);

        if (isset($param['static']) && ($param['static'] == '1') )
        {
            $class = get_class($this);
            $onReloadParam = ['offset' => 0, 'first_page' => 1];
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
                $object = new SystemProgram($key, FALSE); 

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

            // creates a repository for SystemProgram
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

    public static function onOpen($param)
    {
        AdiantiCoreApplication::loadPage($param['controller']);
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