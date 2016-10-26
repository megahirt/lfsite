<?php

use Api\Model\Scriptureforge\Sfchecks\Dto\ProjectSettingsDto;
use Api\Model\Scriptureforge\Sfchecks\TextModel;
use Api\Model\Shared\Rights\ProjectRoles;
use Api\Model\Shared\Rights\SystemRoles;
use Api\Model\Shared\UserModel;

require_once __DIR__ . '/../../TestConfig.php';
require_once SimpleTestPath . 'autorun.php';
require_once TestPhpPath . 'common/MongoTestEnvironment.php';

class TestProjectSettingsDto extends UnitTestCase
{
    public function testEncode_ProjectWith2Users1Unvalidated_DtoCorrect1User()
    {
        $e = new MongoTestEnvironment();
        $e->clean();

        $user1Id = $e->createUser("", "", "");
        $user1 = new UserModel($user1Id);
        $user1->role = SystemRoles::USER;

        $user2Id = $e->createUser("User", "Name", "name@example.com");
        $user2 = new UserModel($user2Id);
        $user2->role = SystemRoles::USER;

        $project = $e->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $projectId = $project->id->asString();

        $project->addUser($user1Id, ProjectRoles::CONTRIBUTOR);
        $user1->addProject($projectId);
        $user1->write();
        $project->addUser($user2Id, ProjectRoles::CONTRIBUTOR);
        $user2->addProject($projectId);
        $user2->write();
        $project->write();

        $text1 = new TextModel($project);
        $text1->title = "Some Title";
        $text1->write();
        $text2 = new TextModel($project);
        $text2->title = "Archived Title";
        $text2->isArchived = true;
        $text2->write();

        $dto = ProjectSettingsDto::encode($projectId, $user2Id);

        $this->assertEqual($dto['count'], 1);
        $this->assertIsA($dto['entries'], 'array');
        $this->assertEqual($dto['entries'][0]['id'], $user2Id);
        $this->assertEqual($dto['entries'][0]['name'], 'Name');
        $this->assertEqual($dto['entries'][0]['role'], ProjectRoles::CONTRIBUTOR);
        $this->assertEqual(count($dto['archivedTexts']), 1);
        $this->assertEqual($dto['archivedTexts'][0]['title'], 'Archived Title');
        $this->assertTrue(count($dto['rights']) > 0, "No rights in dto");
        $this->assertEqual($dto['bcs']['op'], 'settings');
        $this->assertEqual($dto['bcs']['project'], array('id' => $projectId, 'crumb' => SF_TESTPROJECT));
        $this->assertFalse(isset($dto['project']['users']));
        $this->assertEqual($dto['project']['id'], $projectId);
    }

}
