<?php

namespace libraries\languageforge\semdomtrans;

use models\languageforge\semdomtrans\SemDomTransTranslatedForm;
use models\languageforge\SemDomTransProjectModel;

use models\languageforge\semdomtrans\SemDomTransItemModel;
use models\languageforge\semdomtrans\SemDomTransQuestion;
use models\mapper\ArrayOf;
class SemDomXMLImporter {
	
	private $_projectModel;
	
	private $_xml;
	
	private $_runForReal;
	
	private $_lang;
	
	/**
	 * 
	 * @param string $xmlfilepath
	 * @param SemDomTransProjectModel $projectModel
	 * @param bool $testMode
	 */
	public function __construct($xmlfilepath, $projectModel, $testMode = true) {

		$this->_xml = simplexml_load_file($xmlfilepath);
		$this->_projectModel = $projectModel;
		$this->_runForReal = ! $testMode;
		$this->_lang = $projectModel->languageIsoCode;
	}
	
	public function run($english=true) {

		$possibilities = $english ? 
			$this->_xml->SemanticDomainList->CmPossibilityList->Possibilities 
			: $this->_xml->xpath("List[@field='SemanticDomainList']")[0]->Possibilities;
		
		foreach($possibilities->children() as $domainNode) {
			$this->_processDomainNode($domainNode);
		}
	}

	public function _getPathVal($xmlPath) {
		if ($xmlPath) {
			$val = (string)$xmlPath[0];
		} else {
			$val= "";
		}
		return $val;
	}
	
	private function _processDomainNode($domainNode) {
		$guid = (string)$domainNode['guid'];
		$name = $this->_getPathVal($domainNode->xpath("Name/AUni[@ws='{$this->_lang}']"));
		
		$abbreviation = $this->_getPathVal($domainNode->xpath("Abbreviation/AUni[@ws='{$this->_lang}']"));

		$description = (string) $domainNode->xpath("Description/AStr[@ws='{$this->_lang}']")[0]->xpath("Run[@ws='{$this->_lang}']")[0];
				
		
		$questions = new ArrayOf(function ($data) {
        	return new SemDomTransQuestion();
        });      
		$searchKeys = new ArrayOf(function ($data) {
        	return new SemDomTransTranslatedForm();
        });      
	

		if (property_exists($domainNode, 'Questions'))
		{
			$questionsXML = $domainNode->Questions->children();
			
			// parse nested questions
			foreach($questionsXML as $questionXML) {
				$question = $this->_getPathVal($questionXML->xpath("Question/AUni[@ws='{$this->_lang}']"));
				$terms = $this->_getPathVal($questionXML->xpath("ExampleWords/AUni[@ws='{$this->_lang}']"));
				$q = new SemDomTransQuestion($question, $terms);
				$questions[] = $q;
				$sk = new SemDomTransTranslatedForm($terms);
				$searchKeys[] = $sk;
			}
		}				
		
		// assume that we are not matching up with guids
		$itemModel = new SemDomTransItemModel($this->_projectModel);
		$itemModel->readByProperty('xmlGuid', $guid);
		
		$itemModel->name = new SemDomTransTranslatedForm($name);
		$itemModel->key = $abbreviation;
		$itemModel->description = new SemDomTransTranslatedForm($description);
		$itemModel->questions = $questions;
		$itemModel->searchKeys = $searchKeys;
		//print_r($itemModel->questions);
		
		if ($this->_runForReal) {
			$itemModel->write();
		}
		print "Processed $abbreviation $name\n";
		
		// recurse on sub-domains
		if (property_exists($domainNode, 'SubPossibilities')) {
			foreach ($domainNode->SubPossibilities->children() as $subDomainNode) {
				$this->_processDomainNode($subDomainNode);
			}
		}
	}
	
}
?>