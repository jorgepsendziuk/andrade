<?php
namespace Adianti\Widget\Util;

use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Control\TAction;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Base\TElement;
use Adianti\Util\AdiantiTemplateHandler;

use stdClass;

/**
 * FullCalendar Widget
 *
 * This class represents a calendar widget based on FullCalendar.
 * It allows setting various options such as event actions, time ranges,
 * enabled days, popovers, and resizable/movable events.
 *
 * @version    7.5
 * @package    widget
 * @subpackage util
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class TFullCalendar extends TElement
{
    protected $current_date;
    protected $event_action;
    protected $day_action;
    protected $update_action;
    protected $reload_action;
    protected $default_view;
    protected $min_time;
    protected $max_time;
    protected $events;
    protected $enabled_days;
    protected $popover;
    protected $poptitle;
    protected $popcontent;
    protected $resizable;
    protected $movable;
    protected $options;
    protected $full_height;


    /**
     * Class Constructor
     *
     * Initializes the FullCalendar widget with a specific date and view.
     *
     * @param string|null $current_date  The initial date of the calendar (format: YYYY-MM-DD).
     * @param string $default_view       The default calendar view (month, agendaWeek, agendaDay, listWeek).
     */
    public function __construct($current_date = NULL, $default_view = 'month')
    {
        parent::__construct('div');
        $this->current_date = $current_date ? $current_date : date('Y-m-d');
        $this->default_view = $default_view;
        $this->{'class'} = 'tfullcalendar';
        $this->{'id'}    = 'tfullcalendar_' . mt_rand(1000000000, 1999999999);
        $this->min_time  = '00:00:00';
        $this->max_time  = '24:00:00';
        $this->enabled_days = [0,1,2,3,4,5,6];
        $this->popover = FALSE;
        $this->resizable = TRUE;
        $this->movable = TRUE;
        $this->full_height = FALSE;
        $this->options = [];
    }
    
    /**
     * Set an extra option for the FullCalendar widget.
     *
     * @param string $option  The option name.
     * @param mixed $value    The value to assign to the option.
     *
     * @link https://fullcalendar.io/docs/view-specific-options
     */
    public function setOption($option, $value)
    {
        $this->options[$option] = $value;
    }
    
    /**
     * Enable or disable full height mode.
     *
     * @param bool $full_height Whether to enable full height mode (default: TRUE).
     */
    public function enableFullHeight($full_height = TRUE)
    {
        $this->full_height = $full_height;
    }

    /**
     * Set the calendar height.
     *
     * @param int $height The height value in pixels.
     */
    public function setHeight($height)
    {
        $this->options['expandRows'] = true;
        $this->options['height'] = $height;
    }

    /**
     * Define the allowed time range for the calendar.
     *
     * @param string $min_time The minimum time (format: HH:MM:SS).
     * @param string $max_time The maximum time (format: HH:MM:SS).
     */
    public function setTimeRange($min_time, $max_time)
    {
        $this->min_time = $min_time;
        $this->max_time = $max_time;
    }
    
    /**
     * Enable specific days of the week in the calendar.
     *
     * @param array $days An array of enabled days (0 = Sunday, 6 = Saturday).
     */
    public function enableDays($days)
    {
        $this->enabled_days = $days;
    }
    
    /**
     * Set the current date of the calendar.
     *
     * @param string $date The new current date (format: YYYY-MM-DD).
     */
    public function setCurrentDate($date)
    {
        $this->current_date = $date;
    }
    
    /**
     * Set the default view of the calendar.
     *
     * @param string $view The calendar view (month, agendaWeek, agendaDay, listWeek).
     */
    public function setCurrentView($view)
    {
        $this->default_view = $view;
    }
    
    /**
     * Define the action to reload the calendar.
     *
     * @param TAction $action The reload action.
     */
    public function setReloadAction(TAction $action)
    {
        $this->reload_action = $action;
    }
    
    /**
     * Define the action triggered when an event is clicked.
     *
     * @param TAction $action The event click action.
     */
    public function setEventClickAction(TAction $action)
    {
        $this->event_action = $action;
    }
    
    /**
     * Define the action triggered when a day is clicked.
     *
     * @param TAction $action The day click action.
     */
    public function setDayClickAction(TAction $action)
    {
        $this->day_action = $action;
    }
    
    /**
     * Define the action triggered when an event is updated.
     *
     * @param TAction $action The event update action.
     */
    public function setEventUpdateAction(TAction $action)
    {
        $this->update_action = $action;
    }
    
    /**
     * Enable popover for event details.
     *
     * @param string $title    The popover title.
     * @param string $content  The popover content.
     */
    public function enablePopover($title, $content)
    {
        $this->popover = TRUE;
        $this->poptitle = $title;
        $this->popcontent = $content;
    }
    
    /**
     * Disable event resizing in the calendar.
     */
    public function disableResizing()
    {
        $this->resizable = FALSE;
    }
    
    /**
     * Disable event dragging in the calendar.
     */
    public function disableDragging()
    {
        $this->movable = FALSE;
    }

    /**
     * Disable viewing events on weekends.
     */
    public function disableWeekend()
    {
        $this->setOption('businessHours', ['daysOfWeek' => [ 1, 2, 3, 4, 5 ], 'startTime' => '00:00', 'endTime' => '23:59']);
    }

    
    /**
     * Add an event to the calendar.
     *
     * @param string $id       The event ID.
     * @param string $title    The event title.
     * @param string $start    The event start time (format: YYYY-MM-DD HH:MM:SS).
     * @param string|null $end The event end time (optional, format: YYYY-MM-DD HH:MM:SS).
     * @param string|null $url The event URL (optional).
     * @param string|null $color The event color (optional, CSS color format).
     * @param mixed|null $object An optional object for popover data replacement.
     */
    public function addEvent($id, $title, $start, $end = NULL, $url = NULL, $color = NULL, $object = NULL)
    {
        $event = new stdClass;
        $event->{'id'} = $id;
        
        if ($this->popover and !empty($object))
        {
            $poptitle   = AdiantiTemplateHandler::replace($this->poptitle, $object);
            $popcontent = AdiantiTemplateHandler::replace($this->popcontent, $object);
            $event->{'title'} = self::renderPopover($title, $poptitle, $popcontent);
        }
        else
        {
            $event->{'title'} = $title;
        }
        $event->{'start'} = $start;
        $event->{'end'} = $end;
        $event->{'url'} = $url ? $url : '';
        $event->{'color'} = $color;
        
        $this->events[] = $event;
    }
    
    /**
     * Render an event title with a popover.
     *
     * @param string $title      The event title.
     * @param string $poptitle   The popover title.
     * @param string $popcontent The popover content.
     *
     * @return string The formatted HTML string with popover attributes.
     */
    public static function renderPopover($title, $poptitle, $popcontent)
    {
        return "<div data-popover='true' poptitle='{$poptitle}' popcontent='{$popcontent}' style='display:inline;cursor:pointer'> {$title} </div>";
    }
    
    /**
     * Render and display the calendar, executing the required scripts.
     */
    public function show()
    {
        $id = $this->{'id'};
        
        $language = strtolower( AdiantiCoreTranslator::getLanguage() );
        $reload_action_string = '';
        $event_action_string  = '';
        $day_action_string    = '';
        $update_action_string = '';
        $options = json_encode($this->options);
        
        if ($this->event_action)
        {
            if ($this->event_action->isStatic())
            {
                $this->event_action->setParameter('static', '1');
            }
            $event_action_string = $this->event_action->serialize();
        }
        
        if ($this->day_action)
        {
            if ($this->day_action->isStatic())
            {
                $this->day_action->setParameter('static', '1');
            }
            $day_action_string = $this->day_action->serialize();
        }
        
        if ($this->update_action)
        {
            $update_action_string = $this->update_action->serialize(FALSE);
        }
        if ($this->reload_action)
        {
            $reload_action_string = $this->reload_action->serialize(FALSE);
            $this->events = array('url' => 'engine.php?' . $reload_action_string . '&static=1');
        }
        
        $events = json_encode($this->events);
        $editable = ($this->update_action) ? 'true' : 'false';
        $movable = ($this->movable) ? 'true' : 'false';
        $resizable = ($this->resizable) ? 'true' : 'false';
        $full_height = ($this->full_height) ? 'true' : 'false';
        $hidden_days = json_encode(array_values(array_diff([0,1,2,3,4,5,6], $this->enabled_days)));
        
        $default_views = [
            'month' => 'dayGridMonth',
            'agendaWeek' => 'timeGridWeek',
            'agendaDay' => 'timeGridDay',
            'listWeeky' => 'listWeek',
        ];

        $default_view = empty($default_views[$this->default_view])? $this->default_view: $default_views[$this->default_view];

        TScript::create("tfullcalendar_start( '{$id}', {$editable}, '{$default_view}', '{$this->current_date}', '$language', $events, '{$day_action_string}', '{$event_action_string}', '{$update_action_string}', '{$this->min_time}', '{$this->max_time}', $hidden_days, {$movable}, {$resizable}, '{$options}', {$full_height});");
        parent::show();
    }
}
