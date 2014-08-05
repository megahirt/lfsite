<?php

require_once(dirname(__FILE__) . '/../../../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');

class AllLexiconModelTests extends TestSuite {
	
    function __construct() {
        parent::__construct();
        $this->addFile(TestPath . 'languageforge/lexicon/models/LexEntryModel_Test.php');
        $this->addFile(TestPath . 'languageforge/lexicon/models/SenseModel_Test.php');
    }

}

?>
