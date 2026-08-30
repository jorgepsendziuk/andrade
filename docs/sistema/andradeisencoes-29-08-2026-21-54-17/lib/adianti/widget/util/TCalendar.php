<?php
namespace Adianti\Widget\Util;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Control\TAction;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Base\TElement;

/**
 * Calendar Widget
 *
 * This class represents a calendar component that can display a specific month and year,
 * highlight weekends, select specific days, and execute an action when clicking a date.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TCalendar extends TElement
{
    private $months;
    private $year;
    private $month;
    private $width;
    private $height;
    private $action;
    private $selectedDays;
    private $weekendHighlight;
    
    /**
     * Class Constructor
     *
     * Initializes the calendar with default settings, including width, height,
     * month names, and weekend highlight settings.
     */
    public function __construct()
    {
        parent::__construct('div');
        $this->{'class'} = 'tcalendar';
        $this->width = 400;
        $this->height = 300;
        $this->selectedDays = array();
        $this->weekendHighlight = false;
        $this->months = [AdiantiCoreTranslator::translate('January'), AdiantiCoreTranslator::translate('February'), AdiantiCoreTranslator::translate('March'), AdiantiCoreTranslator::translate('April'), AdiantiCoreTranslator::translate('May'), AdiantiCoreTranslator::translate('June'),
                         AdiantiCoreTranslator::translate('July'), AdiantiCoreTranslator::translate('August'), AdiantiCoreTranslator::translate('September'), AdiantiCoreTranslator::translate('October'), AdiantiCoreTranslator::translate('November'), AdiantiCoreTranslator::translate('December')];
    }
    
    /**
     * Enable weekend highlighting
     *
     * This method enables a visual highlight for weekends in the calendar.
     */
    public function highlightWeekend()
    {
        $this->weekendHighlight = true;
    }
    
    /**
     * Set the calendar's dimensions
     *
     * Defines the width and height of the calendar display.
     *
     * @param int $width  The width of the calendar in pixels
     * @param int $height The height of the calendar in pixels
     */
    public function setSize($width, $height)
    {
        $this->width  = $width;
        $this->height = $height;
    }
    
    /**
     * Set the current month to display
     *
     * Defines which month should be displayed in the calendar.
     *
     * @param int $month The month to display (1-12)
     */
    public function setMonth($month)
    {
        $this->month = $month;
    }
    
    /**
     * Set the current year to display
     *
     * Defines which year should be displayed in the calendar.
     *
     * @param int $year The year to display (e.g., 2025)
     */
    public function setYear($year)
    {
        $this->year = $year;
    }
    
    /**
     * Get the current month
     *
     * Returns the currently set month in the calendar.
     *
     * @return int|null The current month (1-12) or null if not set
     */
    public function getMonth()
    {
        return $this->month;
    }
    
    /**
     * Get the current year
     *
     * Returns the currently set year in the calendar.
     *
     * @return int|null The current year or null if not set
     */
    public function getYear()
    {
        return $this->year;
    }
    
    /**
     * Set the action for date selection
     *
     * Defines an action to be executed when a specific day is clicked.
     *
     * @param TAction $action The action to be executed
     */
    public function setAction(TAction $action)
    {
        $this->action = $action;
    }
    
    /**
     * Select specific days
     *
     * Marks a collection of days as selected.
     *
     * @param array $days An array of integers representing the days to be selected (e.g., [1, 5, 10])
     */
    public function selectDays(array $days)
    {
        $this->selectedDays = $days;
    }
    
    /**
     * Render the calendar
     *
     * Generates the calendar HTML structure, applies styles, and sets up interactions.
     * Displays the selected month and year, highlights weekends if enabled,
     * and executes the assigned action when a day is clicked.
     */
    public function show()
    {
        $this->{'style'} = "width: {$this->width}px; height: {$this->height}px";
        
        $this->month = $this->month ? $this->month : date('m');
        $this->year = $this->year ? $this->year : date('Y');
        
        $table = new TTable;
        $table-> width = '100%';
        parent::add($table);
        
        $row = $table->addRow();
        $cell = $row->addCell($this->months[$this->month -1] . ' ' . $this->year);
        $cell->{'colspan'} = 7;
        $cell->{'class'} = 'calendar-header';
        
        $row = $table->addRow();
        $row->addCell(substr(AdiantiCoreTranslator::translate('Sunday'),0,1))->{'class'} = 'calendar-header-day';
        $row->addCell(substr(AdiantiCoreTranslator::translate('Monday'),0,1))->{'class'} = 'calendar-header-day';
        $row->addCell(substr(AdiantiCoreTranslator::translate('Tuesday'),0,1))->{'class'} = 'calendar-header-day';
        $row->addCell(substr(AdiantiCoreTranslator::translate('Wednesday'),0,1))->{'class'} = 'calendar-header-day';
        $row->addCell(substr(AdiantiCoreTranslator::translate('Thursday'),0,1))->{'class'} = 'calendar-header-day';
        $row->addCell(substr(AdiantiCoreTranslator::translate('Friday'),0,1))->{'class'} = 'calendar-header-day';
        $row->addCell(substr(AdiantiCoreTranslator::translate('Saturday'),0,1))->{'class'} = 'calendar-header-day';
        
        
        $prev_year  = $this->year;
        $next_year  = $this->year;
        $prev_month = $this->month - 1;
        $next_month = $this->month + 1;
         
        if ($prev_month == 0 )
        {
            $prev_month = 12;
            $prev_year = $this->year - 1;
        }
        
        if ($next_month == 13 )
        {
            $next_month = 1;
            $next_year = $this->year + 1;
        }
        
        $timestamp = mktime( 0, 0, 0, $this->month, 1, $this->year );
        $maxday = date("t", $timestamp);
        $thismonth = getdate ($timestamp);
        $startday = $thismonth['wday'];
        $dayofweek = 0;
        
        for ($i=0; $i<($maxday + $startday); $i++)
        {
            if (($i % 7) == 0 )
            {
                $row = $table->addRow();
                $row->{'class'} = 'calendar-rowdata';
                $dayofweek = 0;
            }
            
            if ($i < $startday)
            {
                $row->addCell('');
                $dayofweek ++;
            }
            else
            {
                $current_day = ($i - $startday + 1);
                $cell = $row->addCell( $current_day );
                $dayofweek ++;
                
                if (in_array($current_day, $this->selectedDays))
                {
                    $cell->{'class'} = 'calendar-data calendar-selected';
                }
                else
                {
                    $cell->{'class'} = 'calendar-data';
                }
                
                if ($this->weekendHighlight)
                {
                    if ($dayofweek == 1 || $dayofweek == 7)
                    {
                        $cell->{'class'} .= ' weekend';
                    }
                }
                
                $cell->{'valign'} = 'middle';
                
                if ($this->action instanceof TAction)
                {
                    $this->action->setParameter('year', $this->year); 
                    $this->action->setParameter('month', $this->month);
                    $this->action->setParameter('day', $current_day);
                    $string_action = $this->action->serialize(FALSE);
                    $cell->{'onclick'} = "__adianti_ajax_exec('{$string_action}')";
                }
            }
        }
        parent::show();
    }
}
