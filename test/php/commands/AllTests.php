<?php
require_once(dirname(__FILE__) . '/../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');

class AllCommandsTests extends TestSuite {
	
    function __construct() {
        parent::__construct();
 		$this->addFile(TestPath . 'commands/ProjectCommands_Test.php');
 		$this->addFile(TestPath . 'commands/UserCommands_Test.php');
    }

}

?>
