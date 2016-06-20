<?php

namespace Api\Model\Languageforge\Lexicon\Config;

use Api\Model\Mapper\ArrayOf;
use Api\Model\Mapper\MapOf;

class LexiconFieldListConfigObj extends LexiconConfigObj
{
    public function __construct()
    {
        parent::__construct();
        $this->type = LexiconConfigObj::FIELDLIST;
        $this->fieldOrder = new ArrayOf();
        $this->fields = new MapOf(
            function ($data) {
                switch ($data['type']) {
                    case LexiconConfigObj::FIELDLIST:
                        return new LexiconFieldListConfigObj();
                    case LexiconConfigObj::MULTITEXT:
                        return new LexiconMultitextConfigObj();
                    case LexiconConfigObj::OPTIONLIST:
                        return new LexiconOptionlistConfigObj();
                    case LexiconConfigObj::MULTIOPTIONLIST:
                        return new LexiconMultiOptionlistConfigObj();
                    case LexiconConfigObj::PICTURES:
                        return new LexPicturesConfigObj();
                    default:
                        $type = $data['type'];
                        throw new \Exception("Unknown field list config type: $type");
                }
            }
        );
    }

    /**
     * @var ArrayOf<string> fieldName
     */
    public $fieldOrder;

    /**
     * @var MapOf<LexiconConfigObj>
     */
    public $fields;

}
