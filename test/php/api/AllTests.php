<?php
require_once(dirname(__FILE__) . '/../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');

class AllApiTests extends TestSuite {
	
    function __construct() {
        parent::__construct();
  		$this->addFile(TestPath . 'api/API_CRUD_Test.php');
    }

}

?>
