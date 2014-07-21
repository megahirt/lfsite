<?php

namespace models\languageforge\lexicon;

use libraries\shared\palaso\CodeGuard;
use models\mapper\ArrayOf;

class Sense {

	function __construct($liftId = '') {
		$this->liftId = $liftId;
		$this->id = uniqid();
		$this->definition = new MultiText();
		$this->gloss = new MultiText();
		$this->partOfSpeech = new LexiconFieldWithComments();
		$this->semanticDomain = new LexiconMultiValueFieldWithComments();
		$this->examples = new ArrayOf(
			function($data) {
				return new Example();
			}
		);
		$this->customFields = new ArrayOf(
			function($data) {
				CodeGuard::checkTypeAndThrow($data, 'array');
				if (array_key_exists('value', $data)) {
					return new LexiconField();
				} elseif (array_key_exists('values', $data)) {
					return new LexiconMultiValueField();
				} else {
					return new MultiText();
				}
			}
		);
		$this->authorInfo = new AuthorInfo();
	}

	/**
	 * The id of the sense as specified in the LIFT file
	 * @var string
	 */
	public $liftId;
	
	/**
	 * uniqid
	 * @var string
	 */
	public $id;

	/**
	 * @var MultiText
	 */
	public $definition;
	
	/**
	 * @var MultiText
	 */
	public $gloss;
	
	/**
	 * @var LexiconFieldWithComments
	 */
	public $partOfSpeech;
	

	/**
	 * @var LexiconMultiValueFieldWithComments
	 */
	public $semanticDomain;

	/**
	 * @var ArrayOf<Example>
	 */
	public $examples;

	/**
	 * @var ArrayOf <>
	 */
	public $customFields;

	/**
	 * @var AuthorInfo
	 */
	public $authorInfo;
	
	
	/**
	 * 
	 * @param string $id
	 * @return Example
	 */
	public function getExample($id) {
		foreach ($this->examples as $example) {
			if ($example->id == $id) {
				return $example;
			}
		}
	}

	/**
	 * 
	 * @param string $id
	 * @param Example $model
	 */
	public function setExample($id, $model) {
		foreach ($this->examples as $key => $example) {
			if ($example->id == $id) {
				$this->examples[$key] = $model;
				break;
			}
		}
	}
	
}

?>
