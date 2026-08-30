<?php
namespace Adianti\Widget\Datagrid;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Control\TAction;
use Adianti\Registry\TSession;
use Adianti\Widget\Container\TTable;

use Exception;

/**
 * Class TPageNavigation
 *
 * Provides navigation controls for a data grid.
 * This class allows handling pagination, record counts, ordering, and navigation actions.
 *
 * @version    7.5
 * @package    widget
 * @subpackage datagrid
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TPageNavigation
{
    private $limit;
    private $count;
    private $order;
    private $page;
    private $first_page;
    private $action;
    private $width;
    private $direction;
    private $hidden;
    private $resume;
    
    /**
     * Initializes the page navigation with default settings.
     */
    public function __construct()
    {
        $this->hidden = false;
        $this->resume = false;
    }
    
    /**
     * Hides the pagination component.
     */
    public function hide()
    {
        $this->hidden = true;
    }
    
    /**
     * Enables the record counter display.
     */
    public function enableCounters()
    {
        $this->resume = true;
    }
    
    /**
     * Retrieves the pagination summary string.
     *
     * @return string The formatted string showing the range of records being displayed.
     */
    private function getResume()
    {
        if( !$this->getCount() )
        {
            return AdiantiCoreTranslator::translate('No records found');
        }
        else
        {
            $max = number_format( (min(( $this->getLimit() * $this->getPage() ) , $this->getCount())) , 0, '', '.');
            $min = number_format( (($this->getLimit() * ($this->getPage() - 1) ) + 1) , 0, '', '.');
            
            return AdiantiCoreTranslator::translate('^1 to ^2 from ^3 records', $min, $max, number_format($this->getCount(), 0 , '', '.'));
        }
    }
    
    /**
     * Sets the maximum number of records per page.
     *
     * @param int $limit The number of records per page.
     */
    public function setLimit($limit)
    {
        $this->limit  = (int) $limit;
    }
    
    /**
     * Retrieves the maximum number of records per page.
     *
     * @return int The number of records per page.
     */
    public function getLimit()
    {
        return $this->limit;
    }
    
    /**
     * Sets the width of the pagination component.
     *
     * @param int|string $width The width of the pagination element.
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }
    
    /**
     * Sets the total count of records.
     *
     * @param int $count The total number of records.
     */
    public function setCount($count)
    {
        $this->count = (int) $count;
    }
    
    /**
     * Retrieves the total count of records.
     *
     * @return int The total number of records.
     */
    public function getCount()
    {
        return $this->count;
    }
    
    /**
     * Sets the current page number.
     *
     * @param int $page The current page index.
     */
    public function setPage($page)
    {
        $this->page = (int) $page;
    }
    
    /**
     * Retrieves the current page number.
     *
     * @return int The current page index.
     */
    public function getPage()
    {
        return $this->page;
    }
    
    /**
     * Sets the first page number.
     *
     * @param int $first_page The index of the first page.
     */
    public function setFirstPage($first_page)
    {
        $this->first_page = (int) $first_page;
    }
    
    /**
     * Sets the sorting order.
     *
     * @param string $order The column name used for sorting.
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }
    
    /**
     * Sets the sorting direction.
     *
     * @param string $direction The sorting direction ('asc' or 'desc').
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;
    }
    
    /**
     * Sets the pagination properties.
     *
     * @param array $properties An associative array containing pagination settings:
     *                          - 'order' (string): The sorting column.
     *                          - 'page' (int): The current page.
     *                          - 'direction' (string): Sorting direction ('asc' or 'desc').
     *                          - 'first_page' (int): The first page number.
     */
    public function setProperties($properties)
    {
        $order      = isset($properties['order'])  ? addslashes($properties['order'])  : '';
        $page       = isset($properties['page'])   ? $properties['page']   : 1;
        $direction  = (isset($properties['direction']) AND in_array($properties['direction'], array('asc', 'desc')))  ? $properties['direction']   : NULL;
        $first_page = isset($properties['first_page']) ? $properties['first_page']: 1;
        
        $this->setOrder($order);
        $this->setPage($page);
        $this->setDirection($direction);
        $this->setFirstPage($first_page);
    }
    
    /**
     * Sets the navigation action.
     *
     * @param TAction $action The action triggered when the user navigates.
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Retrieves the navigation action.
     *
     * @return TAction The action triggered when the user navigates.
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * Retains the last user pagination settings in the session.
     *
     * @param string $listName The session key used to store pagination data.
     */
    public function keepLastPagination($listName = '')
    {
        if($this->action)
        {
            $this->action->setParameter('keep_pagination', '1');
        }
        
        if(!empty($_REQUEST['keep_pagination']))
        {
            TSession::setValue($listName.'_pagination_properties', $_REQUEST);
        }
        else
        {
            $pagination_properties = TSession::getValue($listName.'_pagination_properties');
            if($pagination_properties)
            {
                if( !isset($_REQUEST['offset']))
                {
                    $_REQUEST['offset'] = $pagination_properties['offset'];    
                }
                elseif($_REQUEST['offset'] == 0 && empty($_REQUEST['page']))
                {
                    $_REQUEST['page'] = 1;   
                }
                
                if(!empty($_REQUEST['order']) && !empty($_REQUEST['direction']) && empty($_REQUEST['page']))
                {
                    $_REQUEST['page'] = 1;
                    $_REQUEST['offset'] = 0;
                }
                
                if(empty($_REQUEST['limit']))
                {
                    $_REQUEST['limit'] = $pagination_properties['limit'];    
                }
                if(empty($_REQUEST['page']))
                {
                    $_REQUEST['page'] = $pagination_properties['page'];    
                }
                if(empty($_REQUEST['first_page']))
                {
                    $_REQUEST['first_page'] = $pagination_properties['first_page'];    
                }
                if(empty($_REQUEST['order']))
                {
                    $_REQUEST['order'] = $pagination_properties['order'] ?? null;    
                }
                if(empty($_REQUEST['direction']))
                {
                    $_REQUEST['direction'] = $pagination_properties['direction'];    
                }
            }
        }
    }

    /**
     * Renders the page navigation component.
     *
     * @throws Exception If the navigation action is not set.
     */
    public function show()
    {
        if ($this->hidden)
        {
            return;
        }
        
        if (!$this->action instanceof TAction)
        {
            throw new Exception(AdiantiCoreTranslator::translate('You must call ^1 before add this component', __CLASS__ . '::' . 'setAction()'));
        }
        
        if ($this->resume)
        {
            $total = new TElement('div');
            $total->{'class'} = 'tpagenavigation_resume';
            $total->add($this->getResume());
            $total->show();
        }
        
        $first_page = isset($this->first_page) ? $this->first_page : 1;
        $direction  = 'asc';
        $page_size  = isset($this->limit) ? $this->limit : 10;
        $max = 10;
        $registros = $this->count;
        
        if (!$registros)
        {
            $registros = 0;
        }
        
        if ($page_size > 0)
        {
            $pages = (int) ($registros / $page_size) - $first_page +1;
        }
        else
        {
            $pages = 1;
        }
        
        $resto = 0;
        if ($page_size>0)
        {
            $resto = $registros % $page_size;
        }
        
        $pages += $resto > 0 ? 1 : 0;
        $last_page = min($pages, $max);
        
        $nav = new TElement('nav');
        $nav->{'class'} = 'tpagenavigation';
        $nav->{'align'} = 'center';
        
        $ul = new TElement('ul');
        $ul->{'class'} = 'pagination';
        $ul->{'style'} = 'display:inline-flex;';
        $nav->add($ul);
        
        if ($first_page > 1)
        {
            // first
            $item = new TElement('li');
            $link = new TElement('a');
            $span = new TElement('span');
            $link->{'aria-label'} = 'Previous';
            $ul->add($item);
            $item->add($link);
            $link->add($span);
            $this->action->setParameter('offset', 0);
            $this->action->setParameter('limit',  $page_size);
            $this->action->setParameter('direction', $this->direction);
            $this->action->setParameter('page',   1);
            $this->action->setParameter('first_page', 1);
            $this->action->setParameter('order', $this->order);

            $link->{'class'}     = "page-link";
            $link->{'href'}      = $this->action->serialize();
            $link->{'generator'} = 'adianti';
            $span->add(TElement::tag('span', '', ['class'=>'fa fa-angle-double-left']));
            
            // previous
            $item = new TElement('li');
            $link = new TElement('a');
            $span = new TElement('span');
            $link->{'aria-label'} = 'Previous';
            $ul->add($item);
            $item->add($link);
            $link->add($span);
            $this->action->setParameter('offset', ($first_page - $max -1) * $page_size);
            $this->action->setParameter('limit',  $page_size);
            $this->action->setParameter('direction', $this->direction);
            $this->action->setParameter('page',   $first_page - $max);
            $this->action->setParameter('first_page', $first_page - $max);
            $this->action->setParameter('order', $this->order);

            $link->{'class'}     = "page-link";
            $link->{'href'}      = $this->action->serialize();
            $link->{'generator'} = 'adianti';
            $span->add(TElement::tag('span', '', ['class'=>'fa fa-angle-left'])); //$span->add('&laquo;');
        }
        
        // active pages
        for ($n = $first_page; $n <= $last_page + $first_page -1; $n++)
        {
            $offset = ($n -1) * $page_size;
            $item = new TElement('li');
            $link = new TElement('a');
            $span = new TElement('span');
            $ul->add($item);
            $item->add($link);
            $link->add($span);
            $span->add($n);
            
            $this->action->setParameter('offset', $offset);
            $this->action->setParameter('limit',  $page_size);
            $this->action->setParameter('direction', $this->direction);
            $this->action->setParameter('page',   $n);
            $this->action->setParameter('first_page', $first_page);
            $this->action->setParameter('order', $this->order);
            
            $link->{'href'}      = $this->action->serialize();
            $link->{'generator'} = 'adianti';
            $link->{'class'}     = 'page-link';

            if ($this->page == $n)
            {
                $item->{'class'} = 'active page-item';
            }
            else
            {
                $item->{'class'} = 'page-item';
            }
        }
        
        // inactive pages/placeholders
        for ($z=$n; $z<=10; $z++)
        {
            $item = new TElement('li');
            $link = new TElement('a');
            $span = new TElement('span');
            $item->{'class'} = 'off page-item';
            $link->{'class'} = 'page-link';
            $ul->add($item);
            $item->add($link);
            $link->add($span);
            $span->add($z);
        }
        
        if ($pages > $max)
        {
            // next
            $first_page = $n;
            $item = new TElement('li');
            $link = new TElement('a');
            $span = new TElement('span');
            $link->{'aria-label'} = "Next";
            $ul->add($item);
            $item->add($link);
            $link->add($span);
            $this->action->setParameter('offset',  ($n -1) * $page_size);
            $this->action->setParameter('limit',   $page_size);
            $this->action->setParameter('direction', $this->direction);
            $this->action->setParameter('page',    $n);
            $this->action->setParameter('first_page', $n);
            $this->action->setParameter('order', $this->order);
            $link->{'class'}     = "page-link";
            $link->{'href'}      = $this->action->serialize();
            $link->{'generator'} = 'adianti';
            $span->add(TElement::tag('span', '', ['class'=>'fa fa-angle-right'])); //$span->add('&raquo;');
            
            // last
            $item = new TElement('li');
            $link = new TElement('a');
            $span = new TElement('span');
            $link->{'aria-label'} = "Next";
            $ul->add($item);
            $item->add($link);
            $link->add($span);
            $this->action->setParameter('offset',  ceil($registros / $page_size)* $page_size - $page_size);
            $this->action->setParameter('limit',   $page_size);
            $this->action->setParameter('direction', $this->direction);
            $this->action->setParameter('page',    ceil($registros / $page_size));
            $this->action->setParameter('first_page', (int) ($registros / ($page_size *10)) *10 +1);
            $this->action->setParameter('order', $this->order);
            $link->{'class'}     = "page-link";
            $link->{'href'}      = $this->action->serialize();
            $link->{'generator'} = 'adianti';
            $span->add(TElement::tag('span', '', ['class'=>'fa fa-angle-double-right']));
        }
        
        $nav->show();
    }
}
