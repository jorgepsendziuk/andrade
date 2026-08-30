<?php
namespace Adianti\Widget\Util;

use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Container\TTable;
use Adianti\Widget\Util\TImage;

use DateInterval;
use DatePeriod;
use DateTime;
use Exception;
use stdClass;

/**
 * TGantt Widget
 *
 * This class implements a Gantt chart widget, allowing event scheduling and visualization in different time modes.
 *
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @author     Artur Comunello
 * @author     Lucas Tomasi
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TGantt extends TElement
{
    private $view_mode;
    private $events;
    private $rows;
    private $size;
    private $minutesStep;
    private $eventAction;
    private $start_date;
    private $end_date;
    private $interval;
    private $dates = [];
    private $headerActions = [];
    private $reloadAction;
    private $updateAction;
    private $dayClickAction;
    private $dragEvent;
    private $hours;
    private $count_hours;
    private $remove_space = FALSE;
    private $title = '';
    private $view_mode_button = FALSE;
    private $size_mode_button = FALSE;
    private $view_mode_options;
    private $size_mode_options;
    
    private $stripedMonths = FALSE;
    private $stripedRows = FALSE;
    private $transformTimeTitle = NULL;
    private $transformEventLabel = NULL;

    const MODE_DAYS            = 'MODE_DAYS';
    const MODE_MONTHS          = 'MODE_MONTHS';
    const MODE_DAYS_WITH_HOUR  = 'MODE_DAYS_WITH_HOUR';
    const MODE_MONTHS_WITH_DAY = 'MODE_MONTHS_WITH_DAY';

    const HOURS    = ['00', '01', '12', '18'];
    const HOURS_24 = ['00', '01','02','03', '04','05','06', '07', '08','09','10','11', '12', '13', '14', '15', '16', '17', '18', '19','20','21','22','23'];

    const SIZES         = ['xs', 'sm', 'md', 'lg'];
    const SIZESPX       = [30, 60, 120, 240];
    const SIZESPXHORAS  = [120, 240, 480, 960];
    const COLUMNHOURVAL = 24;

    const ADJUST_MARGIN = ['xs' => 0, 'sm' => 2, 'md' => 4, 'lg' => 10];
    
    /**
     * Class Constructor
     *
     * Initializes the Gantt chart with a given view mode and size.
     *
     * @param string $view_mode The visualization mode (e.g., MODE_DAYS, MODE_MONTHS)
     * @param string $size The size mode ('xs', 'sm', 'md', 'lg') (default: 'md')
     *
     * @throws Exception If an invalid interval is provided
     */
    public function __construct($view_mode, $size = 'md')
    {
        $this->id     = 'tgantt' . mt_rand(1000000000, 1999999999);
        $this->events = [];
        $this->view_mode   = $view_mode;
        $this->size        = $size;
        $this->start_date  = date('Y-m-d');
        $this->hours       = self::HOURS;
        $this->count_hours = count(self::HOURS);

        if (in_array($view_mode, [self::MODE_DAYS, self::MODE_DAYS_WITH_HOUR]))
        {
            $this->setInterval('15 days');
        }
        else
        {
            $this->setInterval('2 month');
        }
    }
    
    /**
     * Set the view mode of the Gantt chart.
     *
     * @param string $view_mode The desired view mode (e.g., MODE_DAYS, MODE_MONTHS)
     */
    public function setViewMode($view_mode)
    {
        $this->view_mode = $view_mode;
    }
    
    /**
     * Get the current view mode of the Gantt chart.
     *
     * @return string The current view mode.
     */
    public function getViewMode()
    {
        return $this->view_mode;
    }
    
    /**
     * Render an HTML popover with a title and content.
     *
     * @param string $title The main event title.
     * @param string $poptitle The title of the popover.
     * @param string $popcontent The content of the popover.
     *
     * @return string The rendered HTML string.
     */
    public static function renderPopover($title, $poptitle, $popcontent)
    {
        return "<div data-popover='true' poptitle='{$poptitle}' popcontent='{$popcontent}' style='display:flex;cursor:pointer; padding: 0px 7px'> {$title} </div>";
    }

    /**
     * Set the title for the Gantt chart header.
     *
     * @param string $title The title to be displayed.
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Set the size mode of the Gantt chart.
     *
     * @param string $size The size mode ('xs', 'sm', 'md', 'lg').
     *
     * @throws Exception If an invalid size parameter is provided.
     */
    public function setSizeMode($size)
    {
        if (! in_array($size, self::SIZES))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', $size, __METHOD__));
        }

        $this->size = $size;
    }
    
    /**
     * Get the current size mode of the Gantt chart.
     *
     * @return string The current size mode.
     */
    public function getSizeMode()
    {
        return $this->size;
    }
    
    /**
     * Set a custom transformer function for the time title.
     *
     * @param callable $transformer A callable function that takes (start, end, events) as parameters.
     */
    public function setTransformerTimeTitle(callable $transformer)
    {
        $this->transformTimeTitle = $transformer;
    }

    /**
     * Set a custom transformer function for event labels.
     *
     * @param callable $transformEventLabel A callable function that takes (event object, events list, times) as parameters.
     */
    public function setTransformerEventLabel(callable $transformEventLabel)
    {
        $this->transformEventLabel = $transformEventLabel;
    }
    
    /**
     * Set the start date of the Gantt chart.
     *
     * @param string $date The start date in 'Y-m-d' format.
     */
    public function setStartDate($date)
    {
        $this->start_date = $date;
        $this->setInterval($this->interval);
    }

    /**
     * Get the start date of the Gantt chart.
     *
     * @return string The start date in 'Y-m-d' format.
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * Get the end date of the Gantt chart.
     *
     * @return string The end date in 'Y-m-d' format.
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * Set the interval between dates in the Gantt chart.
     *
     * @param string $interval A string representing the interval (e.g., '1 month', '10 days').
     */
    public function setInterval($interval = '1 month')
    {
        $this->interval = $interval;
        $this->end_date = date('Y-m-d', strtotime("{$this->start_date} + {$interval} - 1 day"));
        
        if ($this->view_mode == self::MODE_MONTHS)
        {
            $start = new DateTime($this->start_date);
            $start->modify('first day of this month');
            $this->start_date = $start->format('Y-m-d');

            $end = new DateTime($this->end_date);
            $end->modify('last day of this month');
            $this->end_date = $end->format('Y-m-d');
        }
    }
    
    /**
     * Remove spacing between events on the Gantt chart.
     */
    public function removeSpaceBetweenEvents()
    {
        $this->remove_space = TRUE;
    }

    /**
     * Enable striped background on columns.
     */
    public function enableStripedMonths()
    {
        $this->stripedMonths = TRUE;
    }

    /**
     * Enable striped background on rows.
     */
    public function enableStripedRows()
    {
        $this->stripedRows = TRUE;
    }

    /**
     * Enable 24-hour mode for the Gantt chart.
     */
    public function enableFullHours()
    {
        $this->hours       = self::HOURS_24;
        $this->count_hours = count(self::HOURS_24);
    }

    /**
     * Set the reload action for the Gantt chart.
     *
     * @param TAction $reloadAction The action to reload events.
     */
    public function setReloadAction(TAction $reloadAction)
    {
        $this->reloadAction = $reloadAction;
    }

    /**
     * Set the action triggered when a day is clicked.
     *
     * @param TAction $action The action to be executed.
     */
    public function setDayClickAction(TAction $action)
    {
        $this->dayClickAction = $action;
        $this->dayClickAction->setParameter('register_state', 'false');
    }

    /**
     * Add a header button action.
     *
     * @param TAction $action The action to be executed.
     * @param string $label The button label (optional).
     * @param TImage|null $icon The button icon (optional).
     *
     * @return TElement The generated button element.
     */
    public function addHeaderAction(TAction $action, $label = '', TImage $icon = null)
    {
        $button = new TElement('button');

        if ($icon)
        {
            $button->add($icon);    
        }
        
        $button->add($label);
        $button->{'generator'} = 'adianti';
        $button->{'class'} = 'btn btn-sm ';

        $this->headerActions[] = [$button, $action];

        return $button;
    }
    
    /**
     * Add a widget to the header.
     *
     * @param mixed $widget The widget element.
     *
     * @return mixed The added widget.
     */
    public function addHeaderWidget($widget)
    {
        $this->headerActions[] = [$widget];
        return $widget;
    }
    
    /**
     * Add a new row to the Gantt chart.
     *
     * @param mixed $id The row identifier.
     * @param string $label The row label.
     */
    public function addRow( $id, $label )
    {
        $row = new stdClass;
        $row->{'id'}    = $id;
        $row->{'title'} = $label;

        $this->rows[] = $row;
    }
    
    /**
     * Clear all events from the Gantt chart.
     */
    public function clearEvents()
    {
        $this->events = [];
    }
    
    /**
     * Add a new event to the Gantt chart.
     *
     * @param mixed $id The event identifier.
     * @param mixed $rowId The row ID where the event is placed.
     * @param string $title The event title.
     * @param string $start_time The event start time (Y-m-d H:i format).
     * @param string $end_time The event end time (Y-m-d H:i format).
     * @param string|null $color The event background color (optional).
     * @param float|null $percent The event completion percentage (optional).
     */
    public function addEvent($id, $rowId, $title, $start_time, $end_time, $color = NULL, $percent = null)
    {
        $event = new stdClass;
        $event->{'id'}    = $id;
        $event->{'rowId'} = $rowId;
        $event->{'title'} = $title;
        $event->{'start_time'} = $start_time;
        $event->{'end_time'}   = $end_time;
        $event->{'color'} = $color;
        $event->{'percent'} = $percent;

        if (empty($this->events[$rowId]))
        {
            $this->events[$rowId] = [];
        }

        $this->events[$rowId][] = $event;
    }

    /**
     * Set the action triggered when an event is clicked.
     *
     * @param TAction $action The action to be executed.
     */
    public function setEventClickAction( $action )
    {
        $this->eventAction = $action;
        $this->eventAction->setParameter('register_state', 'false');
    }
    
    /**
     * Enable drag-and-drop functionality for events.
     *
     * @param TAction $updateAction The action to execute when an event is moved.
     * @param int $minutesStep The time step in minutes (default: 1440).
     */
    public function enableDragEvent( TAction $updateAction, $minutesStep = 1440)
    {
        $this->dragEvent    = true;
        $this->updateAction = $updateAction;
        $this->minutesStep  = $minutesStep;
    }

    /**
     * Get all dates within the defined interval.
     *
     * This method generates an array of DateTime objects representing the range of dates covered by the Gantt chart.
     *
     * @return DateTime[] An array of DateTime objects representing the date range.
     */
    private function getDates()
    {
        if ( !empty( $this->dates ) )
        {
            return $this->dates;
        }
        
        $begin = new DateTime( $this->start_date );
        $end   = new DateTime( $this->end_date   );
        $end = $end->modify( '+1 day' );

        $interval = new DateInterval('P1D');
        $daterange = new DatePeriod($begin, $interval, $end);

        foreach ($daterange as $date)
        {
            $this->dates[] = $date;
        }

        return $this->dates;
    }

    /**
     * Get the pixel value corresponding to the minute step.
     *
     * This method calculates the pixel width for each time step based on the defined minute step and column size.
     *
     * @return float The pixel value per minute step.
     */
    private function getPixelValue()
    {
        $columnSzie = $this->getColumnSize();
        #Tamanho da Coluna / ( 24 horas (representacao do minuteStep em horas) ) )
        return $columnSzie / ( 24 / ( $this->minutesStep / 60 ) );
    }

    /**
     * Get the column size based on the zoom level and view mode.
     *
     * @return int The width of each column in pixels.
     */
    private function getColumnSize()
    {
        $key = array_search($this->size, self::SIZES);

        switch ($this->view_mode)
        {
            case self::MODE_DAYS:
            case self::MODE_MONTHS_WITH_DAY:
            case self::MODE_MONTHS:
                return self::SIZESPX[$key];
                break;
            case self::MODE_DAYS_WITH_HOUR:
                return self::SIZESPX[$key] * $this->count_hours;
                break;
        }
    }

    /**
     * Get the title for the Gantt chart time range.
     *
     * This method formats the start and end time range for display. If a custom transformer is set, it applies that transformation.
     *
     * @return string The formatted time title.
     */
    private function geTimeTitle()
    {
        $months = [];
        $start  = new DateTime($this->start_date);
        $end    = new DateTime($this->end_date);

        if ($this->transformTimeTitle)
        {
            return call_user_func_array($this->transformTimeTitle, [$start->format('Y-m-d H:i'), $end->format('Y-m-d H:i'), $this->events]);
        }

        $s = mb_substr(AdiantiCoreTranslator::translate($start->format('F')), 0, 3);
        $e = mb_substr(AdiantiCoreTranslator::translate($end->format('F')), 0, 3);

        $months[$s] = $s;
        $months[$e] = $e;

        return implode(' - ', $months);
    }

    /**
     * Render the header of the Gantt chart.
     *
     * This method creates the title bar with navigation buttons (previous, today, next) and any configured header actions.
     *
     * @return TElement The generated header element.
     */
    private function renderHeader()
    {
        $title = new TElement( 'div' );
        $title->class = 'panel-heading tgantt-title';
        $title->add( $this->title );
        
        $todayAction = clone $this->reloadAction;
        $todayAction->setParameter('start_time', date('Y-m-d'));
        $todayAction->setParameter('end_time', date('Y-m-d', strtotime("now +{$this->interval}")));

        $todayButton = new TElement('button');
        $todayButton->add(AdiantiCoreTranslator::translate('Today'));
        $todayButton->{'id'} = 'now';
        $todayButton->{'generator'} = 'adianti';
        $todayButton->{'style'} = 'margin: 0 20px 3px 20px;';
        $todayButton->{'class'} = 'btn btn-sm btn-primary';
        $todayButton->{'href'} = $todayAction->serialize(TRUE);
        
        $previusWeekAction = clone $this->reloadAction;
        $previusWeekAction->setParameter('start_time', date('Y-m-d', strtotime("{$this->start_date} -{$this->interval}")));
        $previusWeekAction->setParameter('end_time', date('Y-m-d', strtotime("{$this->start_date} - 1 day")));

        $previusWeek = new TElement('button');
        $previusWeek->add(new TImage('fa:chevron-left'));
        $previusWeek->{'id'} = 'previous_week';
        $previusWeek->{'generator'} = 'adianti';
        $previusWeek->{'class'} = 'btn btn-default';
        $previusWeek->{'href'} = $previusWeekAction->serialize(TRUE);

        $next_end = new DateTime($this->end_date);
        $next_end->modify('+'.$this->interval);
        $next_end->modify('last day of this month');
        
        $nextWeekAction = clone $this->reloadAction;
        $nextWeekAction->setParameter('start_time', date('Y-m-d', strtotime("{$this->end_date} + 1 day")));
        $nextWeekAction->setParameter('end_time', $next_end->format('Y-m-d'));
        
        $nextWeek = new TElement('button');
        $nextWeek->add(new TImage('fa:chevron-right'));
        $nextWeek->{'id'} = 'previous_week';
        $nextWeek->{'generator'} = 'adianti';
        $nextWeek->{'class'} = 'btn btn-default';
        $nextWeek->{'href'} = $nextWeekAction->serialize(TRUE);

        $title->add( $todayButton );
        $title->add( $previusWeek );
        $title->add( $nextWeek );

        $month = new TElement( 'span' );
        $month->add($this->geTimeTitle());
        $month->{'style'} = 'margin-left: 25px;margin-right: auto;';

        $title->add($month);

        if ($this->headerActions)
        {
            foreach($this->headerActions as $headerAction)
            {
                $event_ids = array_map(function($e) { return $e->{'id'}; }, array_column($this->events, 0));
                $widget = $headerAction[0];
                
                if (!empty($headerAction[1]))
                {
                    $action = $headerAction[1];
                    
                    $action->setParameter('start_time', $this->start_date);
                    $action->setParameter('end_time', $this->end_date);
                    $action->setParameter('interval', $this->interval);
                    $action->setParameter('event_ids', $event_ids);
                    $action->setParameter('view_mode', $this->view_mode);
                    $action->setParameter('size_mode', $this->size);
                    
                    $widget->{'href'} = $action->serialize(TRUE);
                }
                
                // $action->setParameter('register_state', 'false');
                // $action->setParameter('static', '1');
                
                $title->add($widget);
            }
        }

        return $title;
    }

    /**
     * Render the left sidebar (aside) of the Gantt chart.
     *
     * This section contains row labels and structured row elements.
     *
     * @return TElement The generated sidebar element.
     */
    private function renderAside()
    {
        $tableAside = new TTable;
        $tableAside->{'class'} = 'table-rows';

        //Somente se for no modo de horas
        switch ( $this->view_mode)
        {
            case self::MODE_DAYS_WITH_HOUR:
            case self::MODE_MONTHS_WITH_DAY:
                $tableRow = $tableAside->addRow();
                $cell = $tableRow->addCell('');
                $cell->{'style'} = 'border-bottom: unset';
                break;
        }

        $tableRow = $tableAside->addRow();
        $tableRow->{'style'} = 'height: 30px !important;';

        $cell = $tableRow->addCell( '' );
        $cell->{'style'} = 'height: 30px !important;border-top:unset;';

        if( !empty( $this->dayInterval) )
        {
            $row = $tableAside->addRow();
            $row->{'class'} = 'tgantt-head-hour';
            $cell = $row->addCell( '&nbsp;' );
        }
        
        if (!empty($this->rows))
        {
            foreach ($this->rows as $row)
            {
                $tableRow = $tableAside->addRow();
                $cell = $tableRow->addCell( $row->{'title'} );
    
                if (strip_tags($row->{'title'}) == $row->{'title'})
                {
                    $cell->{'style'} = 'padding: 15px';
                }
            }
        }
        
        $aside = new TElement( 'aside' );
        $aside->{'class'} = 'fixedTable-sidebar';
        $aside->add( $tableAside );

        return $aside;
    }

    /**
     * Render the month header of the Gantt chart.
     *
     * This method generates the column headers representing months.
     *
     * @param TTable $time_table The table where the month headers will be added.
     */
    private function renderMonthHeader($time_table)
    {
        $monthsColspan    = [];
        $table_row        = $time_table->addRow();
        $table_row->{'class'} = 'tgantt-head tgantt-head-day';

        foreach ($this->getDates() as $date)
        {
            $month = AdiantiCoreTranslator::translate($date->format('F'));
            if(!isset($monthsColspan[$month]))
            {
                $monthsColspan[$month]  = 1;
            }
            else
            {
                $monthsColspan[$month]++;
            }
        }

        $months = [];

        foreach ($this->getDates() as $date)
        {
            $month = AdiantiCoreTranslator::translate($date->format('F'));

            if(!isset($months[$month]))
            {
                $months[$month]  = 1;

                $dayLabel        = new TElement( 'div' );
                $dayLabel->{'class'} = 'tgantt-weekly-header-day-label';
                $dayLabel->add($month);

                $h4 = new TElement( 'h4' );
                $h4->{'class'} = 'tgantt-weekly-header-info-' . $this->size;
                $h4->add( $dayLabel );

                $cell = $table_row->addCell( $h4 );
                $cell->{'colspan'} = $monthsColspan[$month];
            }
        }
    }

    /**
     * Render the month and day headers of the Gantt chart.
     *
     * This method generates two rows: one for months and another for days.
     *
     * @param TTable $time_table The table where the headers will be added.
     */
    private function renderMonthDayHeader($time_table)
    {
        $monthsColspan = [];
        $table_row = $time_table->addRow();
        $table_row->{'class'} = 'tgantt-head tgantt-head-day';

        $hours_row = $time_table->addRow();

        foreach ($this->getDates() as $date)
        {
            $month = AdiantiCoreTranslator::translate($date->format('F'));

            if(!isset($monthsColspan[$month]))
            {
                $monthsColspan[$month]  = 1;
            }
            else
            {
                $monthsColspan[$month]++;
            }
        }

        $months = [];

        $pintar = TRUE;

        foreach ($this->getDates() as $date)
        {
            $month = AdiantiCoreTranslator::translate($date->format('F'));

            if(!isset($months[$month]))
            {
                $months[$month]  = 1;

                $dayLabel        = new TElement( 'div' );
                $dayLabel->{'class'} = 'tgantt-weekly-header-day-label';
                $dayLabel->add($month);

                $h4 = new TElement( 'h4' );
                $h4->{'class'} = 'tgantt-weekly-header-info-' . $this->size;
                $h4->add( $dayLabel );

                // Name month
                $cell = $table_row->addCell( $h4 );
                $cell->{'colspan'} = $monthsColspan[$month];
                
                if ($pintar AND $this->stripedMonths)
                {
                    $cell->{'class'} = 'tgannt-cell-opacity';
                    $pintar = FALSE;
                }
                else
                {
                    $pintar = TRUE;
                }
            }

            // Day of month
            $cell = $hours_row->addCell($date->format('d'));
            $cell->{'class'} = 'hour-cell';

            if (!$pintar AND $this->stripedMonths)
            {
                $cell->{'class'} .= ' tgannt-cell-opacity';
            }
        }
    }

    /**
     * Render the daily headers of the Gantt chart.
     *
     * This method generates a single row containing the abbreviated day names and dates.
     *
     * @param TTable $time_table The table where the headers will be added.
     */
    private function renderDailyHeader( $time_table )
    {
        $table_row = $time_table->addRow();
        $table_row->{'class'} = 'tgantt-head';

        foreach ($this->getDates() as $date)
        {
            $dayLabel = new TElement( 'div' );
            $dayLabel->{'class'} = 'tgantt-weekly-header-day-label';
            $dayLabel->add( mb_substr(AdiantiCoreTranslator::translate($date->format( 'l' )), 0,3 ) );
            
            $dayNumber = new TElement( 'div' );
            $dayNumber->{'class'} = 'tgantt-weekly-header-day-number-' . $this->size;
            $dayNumber->add( $date->format( 'd' ) );

            $h4 = new TElement( 'h4' );
            $h4->{'class'} = 'tgantt-weekly-header-info-' . $this->size;
            $h4->add( $dayLabel );
            $h4->add( $dayNumber );

            $table_row->addCell( $h4 );
        }
    }

    /**
     * Render the daily and hourly headers of the Gantt chart.
     *
     * This method generates two rows: one for days and another for hours.
     *
     * @param TTable $time_table The table where the headers will be added.
     */
    private function renderDailyHourHeader( $time_table )
    {
        $table_row = $time_table->addRow();
        $table_row->{'class'} = 'tgantt-head tgantt-head-day';

        $hours_row = $time_table->addRow();

        foreach ($this->getDates() as $date)
        {
            $dayLabel = new TElement( 'div' );
            $dayLabel->{'class'} = 'tgantt-weekly-header-day-label';
            $dayLabel->add( mb_substr(AdiantiCoreTranslator::translate($date->format( 'l' )), 0,3 ) );

            $dayNumber = new TElement( 'div' );
            $dayNumber->{'class'} = 'tgantt-weekly-header-day-number-' . $this->size;
            $dayNumber->add( $date->format( 'd' ) );

            $h4 = new TElement( 'h4' );
            $h4->{'class'} = 'tgantt-weekly-header-info-' . $this->size;
            $h4->add( $dayLabel );
            $h4->add( $dayNumber );

            $cell = $table_row->addCell( $h4 );
            $cell->{'colspan'} = $this->count_hours;

            foreach ($this->hours as $hour)
            {
                $cell = $hours_row->addCell($hour);
                $cell->{'class'} = 'hour-cell';
            }
        }
    }

    /**
     * Render the time table header based on the selected view mode.
     *
     * This method determines whether to render a daily, monthly, or hourly header.
     *
     * @param TTable $table The table where the headers will be added.
     *
     * @throws Exception If an invalid view mode is provided.
     */
    private function renderTimeTableHeader($table)
    {
        switch ($this->view_mode)
        {
            case self::MODE_DAYS:
                $this->renderDailyHeader($table);
                break;
            case self::MODE_DAYS_WITH_HOUR:
                $this->renderDailyHourHeader($table);
                break;
            case self::MODE_MONTHS_WITH_DAY:
                $this->renderMonthDayHeader($table);
                break;
            case self::MODE_MONTHS:
                $this->renderMonthHeader($table);
                break;
            default:
                throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', 'mode', '__construct'));
                break;
        }
    }

    /**
     * Render the time table grid of the Gantt chart.
     *
     * This method generates the grid where events are placed, applying row and column styling based on the configuration.
     *
     * @return TElement The generated time table grid.
     */
    private function renderTimeTable()
    {
        $time_table = new TTable;
        $time_table->{'class'} = 'table-content';

        $this->renderTimeTableHeader( $time_table );
        
        $pintarRow = TRUE;
        
        if (!empty($this->rows))
        {
            foreach ($this->rows as $index => $row)
            {
                $table_row = $time_table->addRow();
    
                $pintarColumn = TRUE;
                $mes_anterior = NULL;
    
                $pintarRow = $this->stripedRows ? $index % 2 != 0 : FALSE;
    
                foreach ($this->getDates() as $date)
                {
                    $month = AdiantiCoreTranslator::translate($date->format('F'));
    
                    if (is_null($mes_anterior))
                    {
                        $mes_anterior = $month;
                    }
    
                    if ($month != $mes_anterior)
                    {
                        $pintarColumn = $this->stripedMonths ? (!$pintarColumn) : FALSE;
                    }
    
                    switch ( $this->view_mode)
                    {
                        case self::MODE_DAYS:
                        case self::MODE_MONTHS_WITH_DAY:
                        case self::MODE_MONTHS:
                            $cell = $table_row->addCell( '' );
                            $cell->{'data-date'} = $date->format('Y-m-d H:i');
                            $cell->{'class'} = 'tgantt-cell';
                            $cell->{'style'} = '';
    
                            if ($this->remove_space)
                            {
                                $cell->{'style'} .= 'padding:unset;';
                            }
    
                            if (($pintarColumn || $pintarRow) && ! ($pintarColumn && $pintarRow) && ($this->stripedMonths || $this->stripedRows))
                            {
                                $cell->{'class'} .= ' tgannt-cell-opacity';
                            }
                           
                            break;
                        default:
                            foreach ($this->hours as $hour)
                            {
                                $cell = $table_row->addCell('');
                                
                                $cell->{'data-date'} = $date->format("Y-m-d {$hour}:i");
                                $cell->{'class'} = 'tgantt-cell';
                                $cell->{'style'} = '';
    
                                if ($this->remove_space)
                                {
                                    $cell->{'style'} .= 'padding:unset;';
                                }
                            }
                            break;
                    }
    
                    $mes_anterior = AdiantiCoreTranslator::translate($date->format('F'));
                }
    
                if( !empty( $this->events[ $row->{'id'} ] ) )
                {
                    $cell = $table_row->getChildren()[0]; //First cell
    
                    foreach ($this->events[ $row->{'id'} ] as $event )
                    {
                        $cell->add( $this->renderEvent( $event) );
                    }
                }
                
                $table_row->{'data-id'} = $row->{'id'};
            }
        }
        
        $divFixedContent = new TElement( 'div' );
        $divFixedContent->{'class'} = 'fixedTable-body';
        $divFixedContent->add( $time_table);

        return $divFixedContent;
    }

    /**
     * Render a Gantt chart event.
     *
     * This method formats an event's position, width, and styling based on its start and end time, color, and percentage completion.
     *
     * @param stdClass $event The event data object.
     *
     * @return TElement The rendered event element.
     */
    private function renderEvent($event)
    {
        $div                = new TElement('div');
        $div->{'id'}        = $event->{'id'};
        $div->{'class'}     = 'tgantt-event';
        $div->{'data'}      = base64_encode(json_encode($event));

        if ($this->transformEventLabel) {
            $div->add( call_user_func_array($this->transformEventLabel, [$event, $this->events, [$this->start_date, $this->end_date]]) );
        } else {
            $div->add( $event->{'title'} );
        }

        if ($this->eventAction)
        {
            $this->eventAction->setParameter('id', $event->{'id'});
            $this->eventAction->setParameter('key', $event->{'id'});
            $this->eventAction->setParameter('view_mode', $this->view_mode);
            $this->eventAction->setParameter('size_mode', $this->size);
            
            $div->{'generator'} = 'adianti';
            $div->{'href'}      = $this->eventAction->serialize(TRUE);
        }

        //Gantt begin and end dates
        $strScheduleStart = strtotime( $this->start_date );

        //Event begin and end dates
        $strEventStart = strtotime( $event->{'start_time'} );
        $strEventEnd   = strtotime( $event->{'end_time'}   );

        // Event duration in hours
        $eventHourDuration = ($strEventEnd - $strEventStart) /(3600);

        // Total hours of event divide by quatity of hours step column multipled for size of column
        $width = ( round($eventHourDuration) / self::COLUMNHOURVAL ) * $this->getColumnSize();

        $marginBegin = ( $strEventStart - $strScheduleStart ) / (3600);
        $marginLeft = (( $marginBegin / self::COLUMNHOURVAL ) * $this->getColumnSize()) - self::ADJUST_MARGIN[$this->size];

        $div->{'style'} = "width:{$width}px;margin-left:{$marginLeft}px;";

        if (!empty($event->{'color'}))
        {
            if ($event->{'percent'})
            {
                $div->{'style'} .= "background: linear-gradient(90deg, {$event->{'color'}} {$event->{'percent'}}%, {$event->{'color'}}40 {$event->{'percent'}}%)";
            }
            else
            {
                $div->{'style'} .= "background:" . $event->{'color'};
            }
        }
        else if ($event->{'percent'})
        {
            $div->{'style'} .= "background: linear-gradient(90deg, #9e9e9e {$event->{'percent'}}%, #9e9e9e40 {$event->{'percent'}}%)";
        }

        if ($this->remove_space)
        {
            $div->{'style'} .= ';display:block;';
        }

        return $div;
    }

    /**
     * Render the entire Gantt chart structure.
     *
     * This method combines the header, aside, and time table components into a structured layout.
     *
     * @return TElement The fully rendered Gantt chart element.
     */
    private function renderGantt()
    {
        $schedule = new TElement( 'div' );
        $schedule->{'class'} = "fixed-table-" . $this->size;
        $schedule->add( $this->renderAside() );
        $schedule->add( $this->renderTimeTable() );

        return $schedule;
    }
    
    /**
     * Enable the view mode selection button.
     *
     * @param bool $with_label Whether to display a label (default: TRUE).
     * @param bool $with_icon Whether to display an icon (default: TRUE).
     * @param string|null $label The button label (optional).
     * @param string|null $icon The button icon (optional).
     */
    function enableViewModeButton($with_label = TRUE, $with_icon = TRUE, $label = NULL, $icon = NULL)
    {
        $this->view_mode_button = TRUE;
        $this->view_mode_options = [$with_label, $with_icon, $label, $icon];
    }
    
    /**
     * Enable the size mode selection button.
     *
     * @param bool $with_label Whether to display a label (default: TRUE).
     * @param bool $with_icon Whether to display an icon (default: TRUE).
     * @param string|null $label The button label (optional).
     * @param string|null $icon The button icon (optional).
     */
    function enableSizeModeButton($with_label = TRUE, $with_icon = TRUE, $label = NULL, $icon = NULL)
    {
        $this->size_mode_button = TRUE;
        $this->size_mode_options = [$with_label, $with_icon, $label, $icon];
    }
    
    /**
     * Display the Gantt chart.
     */
    public function show()
    {
        if ($this->view_mode_button)
        {
            $current_view_mode = $this->getViewMode();
            
            $reloadAction = clone $this->reloadAction;
            $reloadAction->setParameter('register_state', 'false');
            
            // header actions (change view mode)
            $dropdown1 = new TDropDown( $this->view_mode_options[0] ? ($this->view_mode_options[2] ?? AdiantiCoreTranslator::translate('View mode')) : '',
                                        $this->view_mode_options[1] ? ($this->view_mode_options[3] ?? 'fa:eye' ): '');
            $dropdown1->setButtonClass('btn btn-default waves-effect dropdown-toggle');
            $dropdown1->addAction( AdiantiCoreTranslator::translate('Months'),           $reloadAction->cloneWithParameters(['view_mode' => 'MODE_MONTHS']), ($current_view_mode == 'MODE_MONTHS') ? 'fas:circle' : 'far:circle' );
            $dropdown1->addAction( AdiantiCoreTranslator::translate('Months with days'), $reloadAction->cloneWithParameters(['view_mode' => 'MODE_MONTHS_WITH_DAY']), ($current_view_mode == 'MODE_MONTHS_WITH_DAY') ? 'fas:circle' : 'far:circle' );
            $dropdown1->addAction( AdiantiCoreTranslator::translate('Days'),             $reloadAction->cloneWithParameters(['view_mode' => 'MODE_DAYS']), ($current_view_mode == 'MODE_DAYS') ? 'fas:circle' : 'far:circle' );
            $dropdown1->addAction( AdiantiCoreTranslator::translate('Days with hours'),  $reloadAction->cloneWithParameters(['view_mode' => 'MODE_DAYS_WITH_HOUR']), ($current_view_mode == 'MODE_DAYS_WITH_HOUR') ? 'fas:circle' : 'far:circle' );
            
            $this->addHeaderWidget( $dropdown1 );
        }
        
        if ($this->size_mode_button)
        {
            $current_size_mode = $this->getSizeMode();
            
            $reloadAction = clone $this->reloadAction;
            $reloadAction->setParameter('register_state', 'false');
            
            // header actions (change zoom mode)
            $dropdown2 = new TDropDown( $this->size_mode_options[0] ? ($this->size_mode_options[2] ?? AdiantiCoreTranslator::translate('Zoom mode') ): '',
                                        $this->size_mode_options[1] ? ($this->size_mode_options[3] ?? 'fa:search') : '');
            $dropdown2->setButtonClass('btn btn-default waves-effect dropdown-toggle');
            $dropdown2->addAction( AdiantiCoreTranslator::translate('Large'),     $reloadAction->cloneWithParameters(['size_mode' => 'lg']), ($current_size_mode == 'lg') ? 'fas:circle' : 'far:circle' );
            $dropdown2->addAction( AdiantiCoreTranslator::translate('Medium'),    $reloadAction->cloneWithParameters(['size_mode' => 'md']), ($current_size_mode == 'md') ? 'fas:circle' : 'far:circle' );
            $dropdown2->addAction( AdiantiCoreTranslator::translate('Small'),     $reloadAction->cloneWithParameters(['size_mode' => 'sm']), ($current_size_mode == 'sm') ? 'fas:circle' : 'far:circle' );
            $dropdown2->addAction( AdiantiCoreTranslator::translate('Condensed'), $reloadAction->cloneWithParameters(['size_mode' => 'xs']), ($current_size_mode == 'xs') ? 'fas:circle' : 'far:circle' );
            
            $this->addHeaderWidget( $dropdown2 );
        }
        
        $panel = new TElement( 'div' );
        $panel->{'id'} = $this->id;
        $panel->{'class'} = 'panel panel-default tgantt';
        $panel->add( $this->renderHeader() );
        $panel->add( $this->renderGantt() );
        $panel->show();

        $minutesStep = '0';
        $pixelValue = '0';
        $update_action_string = '';
        $day_click_action_string = '';

        if ($this->dragEvent)
        {
            $minutesStep = $this->minutesStep;
            $pixelValue  = $this->getPixelValue();
            $this->updateAction->setParameter('view_mode', $this->view_mode);
            $this->updateAction->setParameter('size_mode', $this->size);
            $update_action_string = $this->updateAction->serialize(FALSE);
        }

        if ($this->dayClickAction)
        {
            $this->dayClickAction->setParameter('view_mode', $this->view_mode);
            $this->dayClickAction->setParameter('size_mode', $this->size);
            $day_click_action_string = $this->dayClickAction->serialize(TRUE);
        }
        
        TScript::create("tgantt_start( '#{$this->id}', '{$day_click_action_string}', '{$minutesStep}','{$pixelValue}', '{$update_action_string}');");
    }
}