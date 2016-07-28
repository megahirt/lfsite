<?php

namespace Api\Model\Languageforge\Lexicon;

use Api\Model\Mapper\MapOf;
use Api\Model\Mapper\ObjectForEncoding;
use LazyProperty\LazyPropertiesTrait;

class Example extends ObjectForEncoding
{
    use LazyPropertiesTrait;

    public function __construct($liftId = '', $guid = '')
    {
        $this->setPrivateProp('liftId');
        $this->setReadOnlyProp('guid');
        $this->setReadOnlyProp('authorInfo');
        if ($liftId) $this->liftId = $liftId;
        if (!$guid || !Guid::isValid($guid)) $guid = Guid::create();
        $this->guid = $guid;

        $this->initLazyProperties([
            'authorInfo',
            'sentence',
            'translation',
            'reference',
            'customFields',
            'examplePublishIn'
        ], false);
    }

    protected function createProperty($name) {
        switch ($name) {
            case 'authorInfo':
                return new AuthorInfo();
            case 'sentence':
            case 'translation':
            case 'reference':
                return new MultiText();
            case 'examplePublishIn':
                return new LexiconMultiValueField();
            case 'customFields':
                return new MapOf('\Api\Model\Languageforge\Lexicon\generateCustomField');
            default:
                return '';
        }
    }

    /**
     * The id of the example as specified in the LIFT file
     * @var string
     */
    public $liftId;

    /** @var MultiText */
    public $sentence;

    /** @var MultiText */
    public $translation;

    /** @var MapOf<MultiText|LexMultiParagraph|LexiconField|LexiconMultiValueField> */
    public $customFields;

    /** @var AuthorInfo */
    public $authorInfo;

    /** @var string */
    public $guid;

    // less common fields used in FLEx

    /** @var MultiText */
    public $reference;

    /** @var LexiconMultiValueField */
    public $examplePublishIn;

}
