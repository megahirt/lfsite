<?php 
namespace models\languageforge\lexicon;

use models\languageforge\lexicon\config\LexConfiguration;
use models\languageforge\lexicon\config\LexiconConfigObj;
use models\languageforge\lexicon\commands\LexEntryCommands;
use models\ProjectModel;

class LexEntryListModel extends \models\mapper\MapperListModel {

	/**
	 * 
	 * @var LexConfiguration
	 */
	private $_config;
	
	public static function mapper($databaseName) {
		static $instance = null;
		if (null === $instance) {
			$instance = new \models\mapper\MongoMapper($databaseName, 'lexicon');
		}
		return $instance;
	}

	/**
	 * 
	 * @param ProjectModel $projectModel
     * @param int $newerThanTimestamp
	 */
	public function __construct($projectModel, $newerThanTimestamp = null) {
		$lexProject = new LexiconProjectModel($projectModel->id->asString());
		$this->_config = $lexProject->config;


        if (!is_null($newerThanTimestamp)) {
            $startDate = new \MongoDate($newerThanTimestamp);
            parent::__construct( self::mapper($projectModel->databaseName()), array('dateModified'=> array('$gte' => $startDate)), array('guid', 'lexeme', 'senses'));
        } else {
		    parent::__construct( self::mapper($projectModel->databaseName()), array(), array('guid', 'lexeme', 'senses'));
        }
	}
	
	private function getDefinition($entry) {
		$senses = $entry['senses'];
		if (count($senses) > 0 && array_key_exists('definition', $senses[0]) && count($senses[0]['definition']) > 0) {
			$ws = $this->_config->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::DEFINITION]->inputSystems[0];
			$definition = $senses[0][LexiconConfigObj::DEFINITION];
			if (isset($definition[$ws])) {
				return $definition[$ws]['value'];
			}
		}
		return '';
	}

	private function getGloss($entry) {
		$senses = $entry['senses'];
		if (count($senses) > 0 && array_key_exists('gloss', $senses[0]) && count($senses[0]['gloss']) > 0) {
			$ws = $this->_config->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::GLOSS]->inputSystems[0];
			$gloss = $senses[0][LexiconConfigObj::GLOSS];
			if (isset($gloss[$ws])) {
				return $gloss[$ws]['value'];
			}
		}
		return '';
	}
	
	private function getLexeme($entry) {
		$lexeme = $entry['lexeme'];
		if (count($lexeme) > 0) {
			$ws = $this->_config->entry->fields[LexiconConfigObj::LEXEME]->inputSystems[0];
			// TODO: actually figure out the preferred writing system for display and use that
			if (isset($lexeme[$ws])) {
				return $lexeme[$ws]['value'];
			}
		}
		return '';	
	}
	
	public function readForDto($missingInfo = '') {
		parent::read();
		$entriesToReturn = array();
		
		if ($missingInfo != '') {
			foreach ($this->entries as $entry) {
				$senses = $entry['senses'];
				$foundMissingInfo = false;
				if (count($senses) == 0) {
					$foundMissingInfo = true;
				} else {
					foreach ($senses as $sense) {
						switch ($missingInfo) {
							case LexiconConfigObj::DEFINITION:
								$definition = $sense['definition'];
								if (count($definition) == 0) {
									$foundMissingInfo = true;
								} else {
									foreach ($definition as $form) {
										if ($form['value'] == '') {
											$foundMissingInfo = true;
										}
									}
								}
								break;
	
							case LexiconConfigObj::POS:
								if (!array_key_exists('value', $sense['partOfSpeech']) || $sense['partOfSpeech']['value'] == '') {
									$foundMissingInfo = true;
								}
								break;
	
							case LexiconConfigObj::EXAMPLE_SENTENCE:
								$examples = $sense['examples'];
								if (count($examples) == 0) {
									$foundMissingInfo = true;
								} else {
									foreach ($examples as $example) {
										if (!array_key_exists('sentence', $example) || count($example['sentence']) == 0) {
											$foundMissingInfo = true;
										} else {
											foreach ($example['sentence'] as $form) {
												if ($form['value'] == '') {
													$foundMissingInfo = true;
												}
											}
										}
									}
								}
								break;
	
							case LexiconConfigObj::EXAMPLE_TRANSLATION:
								$examples = $sense['examples'];
								if (count($examples) == 0) {
									$foundMissingInfo = true;
								} else {
									foreach ($examples as $example) {
										if (!array_key_exists('translation', $example) || count($example['translation']) == 0) {
											$foundMissingInfo = true;
										} else {
											foreach ($example['translation'] as $form) {
												if ($form['value'] == '') {
													$foundMissingInfo = true;
												}
											}
										}
									}
								}
								break;
							
							default:
								throw new \Exception("Unknown missingInfoType = " . $missingInfo);
						}
						if ($foundMissingInfo) {
							break;
						}
					}
				}
				if ($foundMissingInfo) {
					$entriesToReturn[] = $entry;
				}
			} // end of foreach
			$this->entries = $entriesToReturn;
			$this->count = count($this->entries);
		}
	}

	/**
	 * If the $value of $propertyName exists in entries return the entry
	 * @param string $propertyName
	 * @param unknown $value
	 * @return array|boolean $entry or false if not found
	 */
	public function searchEntriesFor($propertyName, $value) {
		foreach ($this->entries as $entry) {
			if ($entry[$propertyName] == $value) {
				return $entry;
			}
		}
		return false;
	}
	
}

?>