<?php

use Api\Model\Shared\Dto\RightsHelper;
use Api\Model\Shared\ProjectModel;
use Api\Model\Shared\Rights\ProjectRoles;
use Api\Model\Shared\Rights\SystemRoles;
use Api\Model\Shared\UserModel;
use PHPUnit\Framework\TestCase;

class RightsHelperTest extends TestCase
{
    /** @var MongoTestEnvironment Local store of mock test environment */
    private static $environ;

    public static function setUpBeforeClass()
    {
        self::$environ = new MongoTestEnvironment();
        self::$environ->clean();
    }

    /**
     * Cleanup test environment
     */
    public function tearDown()
    {
        self::$environ->clean();
    }

    public function testuserCanAccessMethod_unknownMethodName_Exception()
    {
        $this->expectException(Exception::class);

        $userId = self::$environ->createUser('user', 'user', 'user@user.com', SystemRoles::USER);
        $rh = new RightsHelper($userId, null, self::$environ->website);
        self::$environ->inhibitErrorDisplay();

        $rh->userCanAccessMethod('bogusMethodName', array());

        // nothing runs in the current test function after an exception. IJH 2014-11
    }
    /**
     * @depends testuserCanAccessMethod_unknownMethodName_Exception
     */
    public function testuserCanAccessMethod_unknownMethodName_RestoreErrorDisplay()
    {
        // restore error display after last test
        self::$environ->restoreErrorDisplay();
        $this->assertEquals(1, ini_get('display_errors'));
    }

    public function testUserCanAccessMethod_projectSettings_projectManager_true()
    {
        $userId = self::$environ->createUser('user', 'user', 'user@user.com', SystemRoles::USER);
        $user = new UserModel($userId);
        $project = self::$environ->createProject('projectForTest', 'projTestCode');
        $projectId = $project->id->asString();
        $project->addUser($userId, ProjectRoles::MANAGER);
        $project->appName = 'sfchecks';
        $project->write();
        $user->addProject($projectId);
        $user->write();
        $project = ProjectModel::getById($projectId);
        $rh = new RightsHelper($userId, $project, self::$environ->website);
        $result = $rh->userCanAccessMethod('project_settings', array());
        $this->assertTrue($result);
    }

    public function testUserCanAccessMethod_projectSettings_projectMember_false()
    {
        $userId = self::$environ->createUser('user', 'user', 'user@user.com', SystemRoles::USER);
        $user = new UserModel($userId);
        $project = self::$environ->createProject('projectForTest', 'projTestCode');
        $projectId = $project->id->asString();
        $project->addUser($userId, ProjectRoles::CONTRIBUTOR);
        $project->appName = 'sfchecks';
        $project->write();
        $user->addProject($projectId);
        $user->write();
        $project = ProjectModel::getById($projectId);
        $rh = new RightsHelper($userId, $project, self::$environ->website);
        $result = $rh->userCanAccessMethod('project_settings', array());
        $this->assertFalse($result);
    }

    public function testUserCanAccessMethod_projectPageDto_NotAMember_false()
    {
        $userId = self::$environ->createUser('user', 'user', 'user@user.com', SystemRoles::USER);
        $project = self::$environ->createProject('projectForTest', 'projTestCode');
        $project->appName = 'sfchecks';
        $project->write();
        $projectId = $project->id->asString();
        $project = ProjectModel::getById($projectId);
        $rh = new RightsHelper($userId, $project, self::$environ->website);
        $result = $rh->userCanAccessMethod('project_pageDto', array());
        $this->assertFalse($result);
    }
}
