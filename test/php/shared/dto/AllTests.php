<?php

require_once(dirname(__FILE__) . '/../../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');

class AllSharedDtoTests extends TestSuite {
	
    function __construct() {
        parent::__construct();
        $this->addFile(TestPath . 'shared/dto/ManageUsersDto_Test.php');
		$this->addFile(TestPath . 'shared/dto/ProjectListDto_Test.php');
    }

}

?>
