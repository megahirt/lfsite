<?php

namespace models\languageforge\lexicon;

class InputSystem
{
    public function __construct($tag = 'qaa', $name = '', $abbr = '')
    {
        $this->tag = $tag;
        $this->abbreviation = $abbr;
        $this->languageName = $name;
        $this->isRightToLeft = false;
    }

    /**
     * @var string
     */
    public $abbreviation;

    /**
     * @var string
     */
    public $tag; // RFC5646 tag e.g. qaa-x-lang

    /**
     * @var string
     */
    public $languageName;

    /**
     * @var boolean
     */
    public $isRightToLeft;

}
