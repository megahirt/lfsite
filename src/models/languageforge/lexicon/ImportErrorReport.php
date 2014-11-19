<?php
namespace models\languageforge\lexicon;

class ImportErrorReport
{

    public function __construct()
    {
        $this->nodeErrors = array();
    }

    /**
     *
     * @var array <ImportNodeError>
     */
    public $nodeErrors;

    public function hasError()
    {
        $hasError = false;
        foreach ($this->nodeErrors as $nodeError) {
            $hasError |= $nodeError->hasErrors();
        }
        return $hasError;
    }

    public function toString()
    {
        $msg = '';
        foreach ($this->nodeErrors as $nodeError) {
            if ($nodeError->hasErrors()) {
                $msg .= 'While ' . $nodeError->toString() . "\n";
            }
        }
        return $msg;
    }

    public function toFormattedString()
    {
        $msg = '';
        foreach ($this->nodeErrors as $nodeError) {
            if ($nodeError->hasErrors()) {
                $msg .= 'While ' . $nodeError->toFormattedString();
            }
        }
        return $msg . "\n";
    }

    public function toHtml()
    {
        $html = '<dl>';
        foreach ($this->nodeErrors as $nodeError) {
            if ($nodeError->hasErrors()) {
                $html .= '<dt>While ' . $nodeError->toHtml();
            }
        }
        return $html . '</dl>';
    }
}
