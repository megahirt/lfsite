<?php


use models\commands\ProjectCommands;

use models\shared\dto\RightsHelper;
use models\shared\rights\Roles;
use models\UserModel;


require_once(dirname(__FILE__) . '/../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');
require_once(TestPath . 'common/MongoTestEnvironment.php');

class TestRightsHelper extends UnitTestCase {

	function __construct()
	{
		$e = new MongoTestEnvironment();
		$e->clean();
	}

	function testuserCanAccessMethod_unknownMethodName_false() {
		$e = new MongoTestEnvironment();
		$e->clean();
		$userId = $e->createUser('user', 'user', 'user@user.com', SiteRoles::USER);
		$this->expectException();
		$result = RightsHelper::userCanAccessMethod($userId, 'bogusMethodName', array());
	}

	function testUserCanAccessMethod_projectSettings_projectManager_true() {
		$e = new MongoTestEnvironment();
		$e->clean();
		$userId = $e->createUser('user', 'user', 'user@user.com', SiteRoles::USER);
		$user = new UserModel($userId);
		$project = $e->createProject('projectForTest');
		$projectId = $project->id->asString();
		$project->addUser($userId, ProjectRoles::PROJECT_MANAGER);
		$project->write();
		$user->addProject($projectId);
		$user->write();
		$result = RightsHelper::userCanAccessMethod($userId, 'project_settings', array($projectId));
		$this->assertTrue($result);
	}

	function testUserCanAccessMethod_projectSettings_projectMember_false() {
		$e = new MongoTestEnvironment();
		$e->clean();
		$userId = $e->createUser('user', 'user', 'user@user.com', SiteRoles::USER);
		$user = new UserModel($userId);
		$project = $e->createProject('projectForTest');
		$projectId = $project->id->asString();
		$project->addUser($userId, ProjectRoles::MEMBER);
		$project->write();
		$user->addProject($projectId);
		$user->write();
		$result = RightsHelper::userCanAccessMethod($userId, 'project_settings', array($projectId));
		$this->assertFalse($result);
	}
	
	function testUserCanAccessMethod_projectPageDto_NotAMember_false() {
		$e = new MongoTestEnvironment();
		$e->clean();
		$userId = $e->createUser('user', 'user', 'user@user.com', SiteRoles::USER);
		$project = $e->createProject('projectForTest');
		$projectId = $project->id->asString();
		$result = RightsHelper::userCanAccessMethod($userId, 'project_pageDto', array($projectId));
		$this->assertFalse($result);
	}
}
