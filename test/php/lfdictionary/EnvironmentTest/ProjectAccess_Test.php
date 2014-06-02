<?php 
use libraries\languageforge\lfdictionary\environment\ProjectRole;
use libraries\languageforge\lfdictionary\environment\ProjectPermission;
use libraries\languageforge\lfdictionary\environment\LFProjectAccess;
use libraries\languageforge\lfdictionary\environment\EnvironmentMapper;

require_once(dirname(__FILE__) . '/../../TestConfig.php');
require_once(SIMPLETEST_PATH . 'autorun.php');
require_once(LF_BASE_PATH . "Loader.php");
require_once(dirname(__FILE__) . '/../MockObject/AllMockObjects.php');

class TestOfLFProjectAccess extends UnitTestCase {

	function __construct() {
		EnvironmentMapper::connect(new ProjectAccessMockEnvironment());
	}
	
	function testHasPermission_Returns() {
		$p = new LFProjectAccess(TestEnvironment::PROJECT_ID, TestEnvironment::USER_ID);
		$result = $p->hasPermission(ProjectPermission::CAN_ADMIN);
		$this->assertTrue($result);
	}
	
}

?>