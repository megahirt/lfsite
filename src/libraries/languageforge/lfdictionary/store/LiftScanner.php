<?php
namespace libraries\languageforge\lfdictionary\store;

class LiftStates 
{
	const SKIP        = 0;
	const PROCESS_ONE = 1;
	const PROCESS_ALL = 2;
}

class LiftScanner
{

	/**
	 * @var string
	 */
	private $_liftFilePath;

	/**
	 * @param string $liftFilePath
	 */
	public function __construct($liftFilePath) {
		$this->_liftFilePath = $liftFilePath;
	}
	
	public function readEntryFromGuid($guid) {
		$scanner = $this; // php 5.3 cannot access $this in anonymous functions.
		$result = null;
		$this->scanEntries(
			function ($entryGuid) use ($guid) {
				return ($entryGuid == $guid) ? LiftStates::PROCESS_ONE : LiftStates::SKIP;
			},
			function ($node) use ($scanner, &$result) {
				$result = $scanner->readEntry($node);
			}
		);
		return $result;
	}
	
	public function scanEntries($foundEntryCallback, $processEntryCallback = null) {
		$reader = new \XMLReader();
		$reader->open($this->_liftFilePath);
		$stop = false;
		while ($reader->read() && !$stop) {
			switch ($reader->nodeType) {
				case (\XMLREADER::ELEMENT):
					if ($reader->localName == 'entry') {   // Reads the LIFT file and searches for the entry node
						$guid = $reader->getAttribute('guid');
						$state = $foundEntryCallback($guid);
						if ($state == LiftStates::PROCESS_ONE || $state == LiftStates::PROCESS_ALL) {
							$node = $reader->expand(); // expands the node for that particular guid
							$dom = new \DomDocument();
							$n = $dom->importNode($node,true);
							$dom->appendChild($n);
							$sxe = simplexml_import_dom($n);
							if ($processEntryCallback !== null) {
								$processEntryCallback($sxe);
							}
							if ($state == LiftStates::PROCESS_ONE) {
								$stop = true;
							}
						}
					}
					break;
			}
		}
	}
	
	/**
	 * Reads a EntryDTO from the XmlNode $node
	 * @param XmlNode $node
	 * @return \dto\EntryDTO
	 */
	public function readEntry($node) {
		$entry = null;
		$lexicalForms = $node->{'lexical-unit'};
		if ($lexicalForms) {
			$guid = (string)$node['guid'];
			$entry = \libraries\languageforge\lfdictionary\dto\EntryDTO::create($guid);
			$entry->setGuid((string)$node['guid']);
			$entry->setEntry($this->readMultiText($lexicalForms));
			if(isset($node->{'sense'})) {
				foreach ($node->{'sense'} as $sense) {
					$entry->addSense($this->readSense($sense));
				}
			}
		}
		return $entry;
	}
	
	/**
	 * Reads a Sense from the XmlNode $node
	 * @param XmlNode $node
	 * @return \dto\Sense
	 */
	public function readSense($node) {
		$sense = new \libraries\languageforge\lfdictionary\dto\Sense();
		// Definition
		$definition = $node->{'definition'};
		$sense->setDefinition($this->readMultiText($definition));
		
		//id
		if(isset($node->{'id'})) {
			$sense->setId($node->{'id'});
		}else {
			// no id? create a one
			$sense->setId( \libraries\languageforge\lfdictionary\common\UUIDGenerate::uuid_generate_php());
		}
		
		// Part Of Speech
		if(isset($node->{'grammatical-info'})) {
			$partOfSpeech = (string)$node->{'grammatical-info'}->attributes()->value;
			$sense->setPartOfSpeech($partOfSpeech);
		}
	
		// Semantic Domain
		if(isset($node->{'trait'})) {
			$semanticDomainName = (string)$node->{'trait'}->attributes()->name;
			$semanticDomainValue = (string)$node->{'trait'}->attributes()->value;
			$sense->setSemanticDomainName($semanticDomainName);
			$sense->setSemanticDomainValue($semanticDomainValue);
		}
	
		// Examples
		$examples = $node->{'example'};
		if ($examples) {
			foreach ($examples as $example) {
				$sense->addExample($this->readExample($example));
			}
		}
		return $sense;
	}
	
	/**
	 * Reads an Example from the XmlNode $node
	 * @param XmlNode $node
	 * @return \dto\Example
	 */
	public function readExample($node) {
		$example = new \libraries\languageforge\lfdictionary\dto\Example();
	
		//id
		if(isset($node->{'id'})) {
			$example->setId($node->{'id'});
		}else {
			// no id? create a one
			$example->setId( \libraries\languageforge\lfdictionary\common\UUIDGenerate::uuid_generate_php());
		}
		
		// Example multitext
		$exampleXml = $node;
		$example->setExample($this->readMultiText($exampleXml));
		// Translation multitext
		$translationXml = $node->{'translation'};
		if(!empty($translationXml)) {
			$example->setTranslation($this->readMultiText($translationXml));
		} else {
			$translation = $node->addChild('translation');
			$translation->form['lang'] = 'en';
			$example->setTranslation($this->readMultiText($translation));
		}
		return $example;
	}
	
	/**
	 * Reads a MultiText from the XmlNode $node
	 * @param XmlNode $node
	 * @return \lfbase\dto\MultiText
	 */
	
	public function readMultiText($node) {
		$multiText = new \libraries\languageforge\lfdictionary\dto\MultiText();
		foreach ($node->{'form'} as $form) {
			$multiText->addForm((string)$form['lang'], (string)$form->{'text'});
		}
		return $multiText;
	}
		
}

?>