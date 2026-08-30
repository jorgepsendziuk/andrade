<?php

use Adianti\Control\TAction;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Base\TElement;
use Adianti\Widget\Util\TImage;

/**
 * Class BHelper
 *
 * A helper widget that displays an icon with a popover containing additional information.
 * The popover can have customizable content, title, size, side positioning, and an optional action.
 */
class BHelper extends TElement
{
    private $id;
    private $title;
    private $icon;
    private $action;
    private $size;
    private $hover;
    private $side;
    private $content;

    /**
     * BHelper constructor.
     *
     * Initializes the helper widget with an optional icon.
     *
     * @param TImage|null $icon The icon to be displayed. If null, a default question mark icon is used.
     */
    public function __construct(TImage $icon = null)
    {
        parent::__construct('div');
        
        if (empty($icon))
        {
            $icon = new TImage('far:question-circle');
        }

        $this->icon = $icon;
        $this->id = 'bhelper_'.rand();
        $this->side = 'auto';
    }

    /**
     * Defines the popover side positioning.
     *
     * @param string $side The popover side. Accepted values: ['auto', 'top', 'right', 'bottom', 'left'].
     *
     * @throws Exception If an invalid side parameter is provided.
     */
    public function setSide($side)
    {
        if (! in_array($side, ['auto', 'top', 'right', 'bottom', 'left']))
        {
            throw new Exception(AdiantiCoreTranslator::translate('Invalid parameter (^1) in ^2', $side, __METHOD__));
        }

        $this->side = $side;
    }

    /**
     * Sets the popover content.
     *
     * @param string $content The content to be displayed inside the popover.
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
    
    /**
     * Gets the popover content.
     *
     * @return string|null The popover content.
     */
    public function getContent()
    {
        return $this->content;
    }
    
    /**
     * Gets the size of the helper component.
     *
     * @return int|null The size of the component. Returns null if not set.
     */
    public function getSize()
    {
        return null;
    }

    /**
     * Enables or disables hover functionality for the helper.
     *
     * @param bool $hover If true, the popover will appear on hover. Default is true.
     */
    public function enableHover($hover = true)
    {
        $this->hover = $hover;
    }

    /**
     * Sets the size of the helper icon.
     *
     * @param int $size The font size (in pixels) of the icon.
     */
    public function setSize(int $size)
    {
        $this->size = $size;
    }

    /**
     * Gets the assigned action for the helper component.
     *
     * @return TAction|null The associated action or null if no action is set.
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Sets the action to be executed when the helper is clicked.
     *
     * @param TAction $action The action to be executed.
     */
    public function setAction(TAction $action)
    {
        $this->action = $action;
    }

    /**
     * Gets the title of the helper.
     *
     * @return string|null The title of the helper.
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Gets the icon of the helper component.
     *
     * @return TImage The icon object associated with the helper.
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Sets the title of the helper component.
     *
     * @param string $title The title to be displayed when hovering over the component.
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * Sets the icon for the helper component.
     *
     * @param TImage $icon The icon object to be displayed.
     */
    public function setIcon(TImage $icon)
    {
        $this->icon = $icon;
    }

    /**
     * Displays the helper component on the screen.
     *
     * This method renders the icon and configures the popover behavior, including title,
     * content, size, action, and hover settings.
     */
    public function show()
    {
        if ($this->getProperties())
        {
            foreach ($this->getProperties() as $property => $value)
            {
                $this->icon->{"{$property}"} = $value;
            }
        }
        
        if ($this->action && $this->hover)
        {
            $this->icon->style .= '; cursor: pointer';
            $this->icon->generator = 'adianti';
            $this->icon->href = $this->action->serialize();
        }
        else if ($this->action)
        {
            $action = new TElement('span');
            $action->onclick = "$('#{$this->id}').popover('hide')";
            $action->style = 'cursor: pointer';
            $action->generator = 'adianti';
            $action->href = $this->action->serialize();

            if ($this->title)
            {
                $actionTitle = clone $action;
                $actionTitle->add($this->title);
                $this->title = $actionTitle;
            }

            if ($this->content)
            {
                $actionContent = clone $action;
                $actionContent->add($this->content);
                $this->content = $actionContent;
            }
        }
        
        if ($this->size)
        {
            $this->icon->style .= "; font-size: {$this->size}px !important; text-align: center;";
        }
        
        $this->icon->{'id'} = $this->id;
        $this->icon->{'data-popover'} ="true";
        $this->icon->{'poptitle'} = htmlspecialchars(str_replace("\n", '', nl2br((string) $this->title)));
        $this->icon->{'popcontent'} = htmlspecialchars(str_replace("\n", '', nl2br((string) $this->content)));
        $this->icon->{'popside'} = $this->side;

        if (! $this->hover)
        {
            $this->icon->{'poptrigger'} = "click";
            $this->icon->style .= '; cursor: pointer';
        }

        $this->icon->show();
    }
}