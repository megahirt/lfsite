<?php

namespace models\languageforge\lexicon;

use libraries\shared\LanguageData;
use models\languageforge\lexicon\config\LexConfiguration;
use models\languageforge\lexicon\dto\LexBaseViewDto;
use models\languageforge\LfProjectModel;
use models\mapper\MapOf;

class LexiconProjectModel extends LfProjectModel
{
    public function __construct($id = '')
    {
        $this->appName = LfProjectModel::LEXICON_APP;
        $this->rolesClass = 'models\languageforge\lexicon\LexiconRoles';
        $this->inputSystems = new MapOf(
            function ($data) {
                return new InputSystem();
            }
        );

        $this->config = new LexConfiguration();

        // default values
        $this->inputSystems['en'] = new InputSystem('en', 'English', 'en');
        $this->inputSystems['th'] = new InputSystem('th', 'Thai', 'th');

        parent::__construct($id);
    }

    /**
	 *
	 * @var MapOf <InputSystem>
	 */
    public $inputSystems;

    /**
	 *
	 * @var LexConfiguration
	 */
    public $config;

    /**
	 *
	 * @var string
	 */
    public $liftFilePath;

    /**
	 * Adds an input system if it doesn't already exist
	 * @param string $tag
	 * @param string $abbr
	 * @param string $name
	 */
    public function addInputSystem($tag, $abbr = '', $name = '')
    {
        static $languages = null;
        if (! key_exists($tag, $this->inputSystems)) {
            if (! $abbr) {
                $abbr = $tag;
            }
            if (! $name) {
                $name = $tag;
                if (!$languages) {
                    $languages = new LanguageData();
                }
                $languageCode = $languages->getCode($tag);
                if (key_exists($languageCode, $languages)) {
                    $name = $languages[$languageCode]->name;
                }
            }
            $this->inputSystems[$tag] = new InputSystem($tag, $name, $abbr);
        }
    }

    public function getPublicSettings($userId)
    {
        $settings = parent::getPublicSettings($userId);
        $settings['currentUserRole'] = $this->users[$userId]->role;

        return array_merge($settings, LexBaseViewDto::encode($this->id->asString(), $userId));
    }

    /**
	 * Initialize the optionlists in a project
	 */
    public function initializeNewProject()
    {
        // setup default option lists
        $optionList = new LexOptionListModel($this);
        $optionList->name = 'Part Of Speech';
        $optionList->code = 'partOfSpeech';
        $optionList->canDelete = false;
        $optionList->readFromJson(APPPATH . 'json/languageforge/lexicon/partOfSpeech.json');
        $optionList->write();

        /*
        $optionList = new LexOptionListModel($this);
        $optionList->name = 'Semantic Domains';
        $optionList->code = 'semdom';
        $optionList->canDelete = false;
        $optionList->readFromJson(APPPATH . 'json/languageforge/lexicon/semdom.json');
        $optionList->write();

        // we should have a default list for every delivered field that is an option list type
        $optionList = new LexOptionListModel($this);
        $optionList->name = 'Environments';
        $optionList->code = 'environments';
        $optionList->canDelete = false;
        $optionList->readFromJson($environmentsFilePath);
        $optionList->write();
        */

        // repeat for other delivered option list types

    }

}
