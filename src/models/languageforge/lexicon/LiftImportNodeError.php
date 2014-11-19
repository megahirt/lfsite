<?php
namespace models\languageforge\lexicon;

class LiftImportNodeError extends ImportNodeError
{

    const ENTRY = 'entry';
    const SENSE = 'sense';
    const EXAMPLE = 'example';
    const MULTITEXT = 'multitext';

    /**
     *
     * @var array <LiftImportNodeError>
     */
    protected $subnodeErrors;

    public function addUnhandledField($typeName)
    {
        $this->errors[] = array(
            'error' => 'UnhandledField',
            'type' => $typeName
        );
    }

    public function addUnhandledTrait($traitName)
    {
        $this->errors[] = array(
            'error' => 'UnhandledTrait',
            'name' => $traitName
        );
    }

    public function addUnhandledMedia($url, $context)
    {
        $this->errors[] = array(
            'error' => 'UnhandledMedia',
            'url' => $url,
            'context' => $context
        );
    }

    public function addUnhandledNote($noteType)
    {
        $this->errors[] = array(
            'error' => 'UnhandledNote',
            'type' => $noteType
        );
    }

    /**
     * Creates the specific string for each of $errors
     * This should be overwritten by each parent class
     *
     * @param string $termEnd
     * @param string $dataStart
     * @param string $dataEnd
     * @throws \Exception
     * @return string
     */
    protected function toErrorString($termEnd = '', $dataStart = ', ', $dataEnd = '') {
        $msg = "processing $this->type '$this->identifier'" . $termEnd;
        foreach ($this->errors as $error) {
    	    $msg .= $dataStart;
            switch ($error['error']) {
            	case 'UnhandledElement':
            	    $msg .= "unhandled element '" . $error['element'] . "'";
            	    break;
                case 'UnhandledField':
                    $msg .= "unhandled field '" . $error['type'] . "'";
                    break;
                case 'UnhandledTrait':
                    $msg .= "unhandled trait '" . $error['name'] . "'";
                    break;
                case 'UnhandledNote':
                    $msg .= "unhandled note '" . $error['type'] . "'";
                    break;
                case 'UnhandledMedia':
                    $msg .= "unhandled media '" . $error['url'] . "' in " . $error['context'];
                    break;
                default:
                    throw new \Exception("Unknown error type '" . $error['error'] . "' while processing identifier '" . $this->identifier . "'");
            }
            $msg .= $dataEnd;
        }
        return $msg;
    }
}
