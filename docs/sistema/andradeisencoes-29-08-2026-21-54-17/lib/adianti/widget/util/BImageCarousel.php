<?php

use Adianti\Widget\Base\TElement;
use Adianti\Widget\Base\TScript;

/**
 * Class BImageCarousel
 *
 * This class represents an image carousel component based on Splide.js.
 * It allows the addition of image sources, configuration of dimensions,
 * and optional thumbnail navigation.
 *
 * @package Adianti\Widget\Base
 */
class BImageCarousel extends TElement
{
    private $id;
    private $sources;
    private $thumbs;
    private $width;
    private $height;
    private $widthThumb;
    private $heightThumb;
    private $customOptionsThumb;
    private $customOptions;

    /**
     * BImageCarousel constructor.
     *
     * Initializes the carousel with a unique ID, default dimensions, 
     * and an empty list of image sources.
     */
    public function __construct()
    {
        parent::__construct('section');
        $this->id = 'bimagecarousel_' . mt_rand(1000000000, 1999999999);
        $this->thumbs = FALSE;
        $this->sources = [];
        $this->width = '100%';
        $this->height = '100%';
        $this->widthThumb = 100;
        $this->heightThumb = 60;
    }

    /**
     * Sets the dimensions of the image carousel.
     *
     * @param string|int $width  The width of the carousel (e.g., '100%', '600px').
     * @param string|int $height The height of the carousel (e.g., '100%', '400px').
     */
    public function setSize($width, $height)
    {
        $width = (strstr($width, '%') !== FALSE) ? $width : "{$width}px";
        $height = (strstr($height, '%') !== FALSE) ? $height : "{$height}px";

        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Enables thumbnail navigation for the carousel.
     */
    public function enableThumbs()
    {
        $this->thumbs = TRUE;
    }

    /**
     * Sets the dimensions of the thumbnail images.
     *
     * @param string|int $width  The width of the thumbnails (e.g., '100px', '80%').
     * @param string|int $height The height of the thumbnails (e.g., '60px', '50%').
     */
    public function setSizeThumbs($width, $height)
    {
        $width = (strstr($width, '%') !== FALSE) ? $width : "{$width}px";
        $height = (strstr($height, '%') !== FALSE) ? $height : "{$height}px";

        $this->widthThumb = $width;
        $this->heightThumb = $height;
    }

    /**
     * Disables thumbnail navigation for the carousel.
     */
    public function disableThumbs()
    {
        $this->thumbs = FALSE;
    }

    /**
     * Sets a custom ID for the carousel.
     *
     * @param string $id The unique identifier for the carousel.
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Gets the ID of the carousel.
     *
     * @return string The unique identifier of the carousel.
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Adds an image source to the carousel.
     *
     * @param string $source The URL of the image to be added.
     */
    public function addSource($source)
    {
        $this->sources[] = $source;
    }

    /**
     * Sets multiple image sources for the carousel.
     *
     * @param array $sources An array of image URLs.
     */
    public function setSources($sources)
    {
        $this->sources = $sources;
    }

    /**
     * Retrieves the list of image sources in the carousel.
     *
     * @return array An array of image URLs.
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * Creates the HTML structure for the Splide carousel.
     *
     * @param string $id    The ID of the carousel.
     * @param string $class Additional CSS class for styling.
     *
     * @return TElement The generated carousel HTML element.
     */
    private function mount($id, $class = '')
    {
        $el = new TElement('section');
        $el->id = $id;
        $el->class = "splide bimagecarousel " . $class;

        $track = new TElement('div');
        $track->class = "splide__track";

        $list = new TElement('ul');
        $list->class = 'splide__list';

        $track->add($list);

        if ($this->sources)
        {
            foreach($this->sources as $source)
            {
                $item = new TElement("li");
                $item->class = "splide__slide";
                $item->add(TElement::tag('img', '', ['src' => $source]));
                
                $list->add($item);
            }
        }

        $el->add($track);
        
        return $el;
    }

    /**
     * Gets the main Splide instance.
     *
     * @return TElement The main Splide carousel element.
     */
    private function getSplide()
    {
        return $this->mount($this->id);
    }

    /**
     * Gets the thumbnail navigation Splide instance.
     *
     * @return TElement The thumbnail Splide carousel element.
     */
    private function getThumbs()
    {
        return $this->mount($this->id.'thumb', 'thumb');
    }

    /**
     * Sets custom options for the main Splide carousel.
     *
     * @param array $options An associative array of Splide.js options.
     */
    public function setCustomOptions($options)
    {
        $this->customOptions = $options;
    }

    /**
     * Sets custom options for the thumbnail navigation carousel.
     *
     * @param array $options An associative array of Splide.js options for thumbnails.
     */
    public function setCustomOptionsThumb($options)
    {
        $this->customOptionsThumb = $options;
    }

    /**
     * Retrieves the configuration options for the main Splide carousel.
     *
     * @return array An associative array of Splide.js options.
     */
    public function getOptions()
    {
        $options = [
            'type' => 'fade',
            'rewind' => true,
            'pagination' => ! $this->thumbs,
            'arrows'  => ! $this->thumbs,
            'width' => $this->width,
            'height' => $this->height,
        ];

        return array_merge($options, ($this->customOptions??[]));
    }

    /**
     * Retrieves the configuration options for the thumbnail navigation carousel.
     *
     * @return array An associative array of Splide.js options for thumbnails.
     */
    public function getOptionsThumb()
    {
        $options = [
            'fixedWidth' => $this->widthThumb,
            'fixedHeight' => $this->heightThumb,
            'gap' => 10,
            'rewind' => true,
            'pagination' => false,
            'isNavigation' => true,
            'width' => $this->width,
            'breakpoints' => [
                '600' => ['fixedWidth' => 60, 'fixedHeight' => 44]
            ],
        ];

        return array_merge($options, ($this->customOptionsThumb??[]));
    }

    /**
     * Renders the image carousel on the page.
     *
     * This method generates the necessary HTML structure and initializes 
     * the Splide carousel via JavaScript.
     */
    public function show()
    {
        $options = json_encode($this->getOptions());
        $optionsThumb = json_encode($this->getOptionsThumb());

        if ($this->thumbs)
        {
            $div = new TElement('div');
            $div->add($this->getSplide());
            $div->add($this->getThumbs());
            $div->setProperties($this->getProperties());
            $div->show();
            
            TScript::create("bimagecarousel_start('{$this->id}', {$options}, '{$this->id}thumb', {$optionsThumb})");
        }
        else
        {
            $splide = $this->getSplide();
            $splide->setProperties($this->getProperties());
            $splide->show();
            
            TScript::create("bimagecarousel_start('{$this->id}', {$options})");
        }
    }
}