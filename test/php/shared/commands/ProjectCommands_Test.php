<?php

use Api\Model\Languageforge\Lexicon\LexEntryModel;
use Api\Model\Languageforge\Lexicon\LexOptionListModel;
use Api\Model\Languageforge\Lexicon\LexProjectModel;
use Api\Model\Scriptureforge\Sfchecks\SfchecksProjectModel;
use Api\Model\Scriptureforge\SfProjectModel;
use Api\Model\Shared\Command\ProjectCommands;
use Api\Model\Shared\Mapper\MongoStore;
use Api\Model\Shared\ProjectModel;
use Api\Model\Shared\ProjectSettingsModel;
use Api\Model\Shared\Rights\ProjectRoles;
use Api\Model\Shared\UserModel;
use Palaso\Utilities\FileUtilities;

require_once __DIR__ . '/../../TestConfig.php';
require_once SimpleTestPath . 'autorun.php';
require_once TestPhpPath . 'common/MongoTestEnvironment.php';

class TestProjectCommands extends UnitTestCase
{
    public function __construct() {
        $this->environ = new MongoTestEnvironment();
        $this->environ->clean();
        $this->save = array();
        parent::__construct();
    }

    /** @var MongoTestEnvironment Local store of mock test environment */
    private $environ;

    /** @var array <mixed> Data storage between tests */
    private $save;

    public function testDeleteProjects_ProjectOwner_NoThrow()
    {
        $this->environ->clean();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user1 = new UserModel($user1Id);

        $projectId = ProjectCommands::createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE, SfProjectModel::SFCHECKS_APP,
            $user1->id->asString(), $this->environ->website);

        ProjectCommands::deleteProjects(array($projectId), $user1Id);
    }

    public function testDeleteProjects_NotProjectOwner_Throw()
    {
        $this->environ->clean();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user2Id = $this->environ->createUser("user2name", "User2 Name", "user2@example.com");
        $user1 = new UserModel($user1Id);
        $user2 = new UserModel($user2Id);

        $projectId = ProjectCommands::createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE, SfProjectModel::SFCHECKS_APP,
            $user1->id->asString(), $this->environ->website);
        $project = new ProjectModel($projectId);
        $project->addUser($user2->id->asString(), ProjectRoles::MANAGER);
        $this->expectException();
        ProjectCommands::deleteProjects(array($projectId), $user2Id);
    }

    public function testArchiveProjects_PublishedProject_ProjectArchived()
    {
        $this->environ->clean();

        $project = $this->environ->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $projectId = $project->id->asString();
        $ownerId = $project->ownerRef->asString();

        $this->assertFalse($project->isArchived);

        ProjectCommands::archiveProject($projectId, $ownerId);

        $project->read($projectId);
        $this->assertTrue($project->isArchived);
    }

    public function testCheckIfArchivedAndThrow_NonArchivedProject_NoThrow()
    {
        $this->environ->clean();

        $project = $this->environ->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        // Project not archived, no throw expected
        ProjectCommands::checkIfArchivedAndThrow($project);
    }

    public function testCheckIfArchivedAndThrow_ArchivedProject_Throw()
    {
        $this->environ->clean();

        $project = $this->environ->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $project->isArchived = true;
        $project->write();

        $this->assertTrue($project->isArchived);
        $this->expectException();
        ProjectCommands::checkIfArchivedAndThrow($project);
    }

    public function testPublishProjects_ArchivedProject_ProjectPublished()
    {
        $this->environ->clean();

        $project = $this->environ->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $project->isArchived = true;
        $projectId = $project->write();

        $this->assertTrue($project->isArchived);

        $count = ProjectCommands::publishProjects(array($projectId));

        $project->read($projectId);
        $this->assertEqual($count, 1);
        $this->assertFalse($project->isArchived);
    }

    public function testUpdateUserRole_UpdateUserInProject_UserJoinedProject()
    {
        $this->environ->clean();

        // setup parameters: user, project and params
        $userId = $this->environ->createUser("existinguser", "Existing Name", "existing@example.com");
        $user = new UserModel($userId);
        $project = $this->environ->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $projectId = $project->id->asString();

        // update user role in project
        $updatedUserId = ProjectCommands::updateUserRole($projectId, $user->id->asString(), ProjectRoles::MANAGER);

        // read from disk
        $updatedUser = new UserModel($updatedUserId);
        $sameProject = new ProjectModel($projectId);

        // user updated and joined to project
        $this->assertEqual($updatedUser->id, $userId);
        $this->assertNotEqual($updatedUser->role, ProjectRoles::MANAGER);
        $projectUser = $sameProject->listUsers()->entries[0];
        $this->assertEqual($projectUser['name'], "Existing Name");
        $userProject = $updatedUser->listProjects($this->environ->website->domain)->entries[0];
        $this->assertEqual($userProject['projectName'], SF_TESTPROJECT);
    }

    public function testUpdateUserRole_JoinTwice_JoinedOnce()
    {
        $this->environ->clean();

        // setup user and project
        $project = $this->environ->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $projectId = $project->id->asString();
        $userId = $this->environ->createUser("existinguser", "Existing Name", "existing@example.com");

        // update user role in project once
        $updatedUserId = ProjectCommands::updateUserRole($projectId, $userId);

        // read from disk
        $sameUser = new UserModel($updatedUserId);
        $sameProject = new ProjectModel($projectId);

        // user in project once and project has one user
        $this->assertEqual($sameProject->listUsers()->count, 1);
        $this->assertEqual($sameUser->listProjects($this->environ->website->domain)->count, 1);

        // update user role in project again
        $updatedUserId = ProjectCommands::updateUserRole($projectId, $userId);

        // read from disk again
        $sameProject->read($projectId);
        $sameUser->read($updatedUserId);

        // user still in project once and project still has one user
        $this->assertEqual($sameProject->listUsers()->count, 1);
        $this->assertEqual($sameUser->listProjects($this->environ->website->domain)->count, 1);
    }

    public function testRemoveUsers_NoUsers_NoThrow()
    {
        $this->environ->clean();

        // setup parameters: project and users
        $project = $this->environ->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $projectId = $project->id->asString();
        $userIds = array();

        // there are no users in project
        $this->assertEqual($project->listUsers()->count, 0);

        // remove users from project with no users - no throw expected
        ProjectCommands::removeUsers($projectId, $userIds);
    }

    public function testReadSettings_CanReadSettings()
    {
        $this->environ->clean();

        // setup project and users
        $project = $this->environ->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $projectId = $project->id->asString();
        $projectSettings = new ProjectSettingsModel($projectId);
        $projectSettings->smsSettings->accountId = "12345";
        $projectSettings->write();

        $result = ProjectCommands::readProjectSettings($projectId);

        $this->assertEqual($result['sms']['accountId'], "12345");
    }

    public function testRemoveUsers_UsersInProject_RemovedFromProject()
    {
        $this->environ->clean();

        // setup project and users
        $project = $this->environ->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $projectId = $project->id->asString();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user2Id = $this->environ->createUser("user2name", "User2 Name", "user2@example.com");
        $user3Id = $this->environ->createUser("user3name", "User3 Name", "user3@example.com");
        $user1 = new UserModel($user1Id);
        $user2 = new UserModel($user2Id);
        $user3 = new UserModel($user3Id);
        $project->addUser($user1->id->asString(), ProjectRoles::CONTRIBUTOR);
        $project->addUser($user2->id->asString(), ProjectRoles::CONTRIBUTOR);
        $project->addUser($user3->id->asString(), ProjectRoles::CONTRIBUTOR);
        $project->write();
        $user1->addProject($project->id->asString());
        $user1->write();
        $user2->addProject($project->id->asString());
        $user2->write();
        $user3->addProject($project->id->asString());
        $user3->write();

        // read from disk
        $otherProject = new ProjectModel($projectId);
        $otherUser1 = new UserModel($user1Id);

        // each user in project, project has each user
        $user1Project = $otherUser1->listProjects($this->environ->website->domain)->entries[0];
        $this->assertEqual($user1Project['projectName'], SF_TESTPROJECT);
        $user2Project = $otherUser1->listProjects($this->environ->website->domain)->entries[0];
        $this->assertEqual($user2Project['projectName'], SF_TESTPROJECT);
        $user3Project = $otherUser1->listProjects($this->environ->website->domain)->entries[0];
        $this->assertEqual($user3Project['projectName'], SF_TESTPROJECT);
        $projectUser1 = $otherProject->listUsers()->entries[0];
        $this->assertEqual($projectUser1['username'], "user1name");
        $projectUser2 = $otherProject->listUsers()->entries[1];
        $this->assertEqual($projectUser2['username'], "user2name");
        $projectUser3 = $otherProject->listUsers()->entries[2];
        $this->assertEqual($projectUser3['username'], "user3name");

        // remove users from project
        $userIds = array($user1->id->asString(), $user2->id->asString(), $user3->id->asString());
        ProjectCommands::removeUsers($projectId, $userIds);

        // read from disk
        $sameProject = new ProjectModel($projectId);
        $sameUser1 = new UserModel($user1Id);
        $sameUser2 = new UserModel($user2Id);
        $sameUser3 = new UserModel($user3Id);

        // project has no users, each user not in project
        $this->assertEqual($sameProject->listUsers()->count, 0);
        $this->assertEqual($sameUser1->listProjects($this->environ->website->domain)->count, 0);
        $this->assertEqual($sameUser2->listProjects($this->environ->website->domain)->count, 0);
        $this->assertEqual($sameUser3->listProjects($this->environ->website->domain)->count, 0);
    }

    public function testRemoveUsers_ProjectOwner_NotRemovedFromProject()
    {
        $this->environ->clean();

        // setup project and users.  user1 is the project owner
        $project = $this->environ->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $projectId = $project->id->asString();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user2Id = $this->environ->createUser("user2name", "User2 Name", "user2@example.com");
        $user1 = new UserModel($user1Id);
        $user2 = new UserModel($user2Id);
        $project->addUser($user1->id->asString(), ProjectRoles::CONTRIBUTOR);
        $project->addUser($user2->id->asString(), ProjectRoles::CONTRIBUTOR);
        $project->ownerRef = $user2Id;
        $project->write();
        $user1->addProject($project->id->asString());
        $user1->write();
        $user2->addProject($project->id->asString());
        $user2->write();

        // save data for rest of this test
        $this->save['projectId'] = $projectId;
        $this->save['user1Id'] = $user1Id;
        $this->save['user2Id'] = $user2Id;

        // remove users from project.  user1 still remains as project owner
        $userIds = array($user1->id->asString(), $user2->id->asString());
        $this->expectException();
        $this->environ->inhibitErrorDisplay();
        ProjectCommands::removeUsers($projectId, $userIds);

        // nothing runs in the current test function after an exception. IJH 2014-11
    }
    // this test was designed to finish testRemoveUsers_ProjectOwner_NotRemovedFromProject
    public function testRemoveUsers_ProjectOwner_NotRemovedFromProject_RestoreErrorDisplay()
    {
        // restore error display after last test
        $this->environ->restoreErrorDisplay();

        // read from disk
        $sameProject = new ProjectModel($this->save['projectId']);
        $sameUser1 = new UserModel($this->save['user1Id']);
        $sameUser2 = new UserModel($this->save['user2Id']);

        // project still has project owner
        $this->assertEqual($sameProject->listUsers()->count, 1);
        $this->assertEqual($sameUser1->listProjects($this->environ->website->domain)->count, 0);
        $this->assertEqual($sameUser2->listProjects($this->environ->website->domain)->count, 1);
    }

    public function testProjectCodeExists_CodeExists_True()
    {
        $this->environ->clean();

        $project = $this->environ->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $project->write();

        $this->assertTrue(ProjectCommands::projectCodeExists(SF_TESTPROJECTCODE));
    }

    public function testProjectCodeExists_CodeDoesNotExist_False()
    {
        $this->environ->clean();

        $project = $this->environ->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $project->write();

        $this->assertFalse(ProjectCommands::projectCodeExists('randomcode'));
    }

    public function testCreateProject_NewProject_ProjectOwnerSet()
    {
        $this->environ->clean();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user1 = new UserModel($user1Id);

        $projectId = ProjectCommands::createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE, SfProjectModel::SFCHECKS_APP,
            $user1->id->asString(), $this->environ->website);

        $project = new ProjectModel($projectId);
        $this->assertTrue($project->ownerRef->asString() == $user1->id->asString());
    }

    public function testCreateProject_SfChecksProject_IndexesCreated()
    {
        $this->environ->clean();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user1 = new UserModel($user1Id);

        $projectId = ProjectCommands::createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE, SfProjectModel::SFCHECKS_APP,
            $user1->id->asString(), $this->environ->website);

        $project = new SfchecksProjectModel($projectId);
        $collectionName = 'activity';
        $databaseName = $project->databaseName();
        $indexCount = iterator_count(MongoStore::getCollectionIndexes($databaseName, $collectionName));
        $this->assertTrue($indexCount >= 1);
        $index = ['key' => ['_id' => 1]];
        $this->assertTrue(MongoStore::isIndexFieldNameInCollection($index, $databaseName, $collectionName));
    }

    public function testMongoStoreIsIndexFieldNameInCollection_LexProject_Ok()
    {
        // setup
        $this->environ = new LexiconMongoTestEnvironment();
        $this->environ->clean();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user1 = new UserModel($user1Id);
        $srProject = null;
        $projectId = ProjectCommands::createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE,
            LexProjectModel::LEXICON_APP, $user1->id->asString(), $this->environ->website, $srProject);
        $project = new LexProjectModel($projectId);
        $databaseName = $project->databaseName();
        $collectionName = LexEntryModel::mapper($databaseName)->getCollectionName();

        // is not in collection
        $index = ['key' => ['code' => 1]];
        $this->assertFalse(MongoStore::isIndexFieldNameInCollection($index, $databaseName, $collectionName, $indexName));
        $this->assertEqual($indexName, '');

        // is not in collection irrespective of field order
        $index = ['key' => ['code' => -1]];
        $this->assertFalse(MongoStore::isIndexFieldNameInCollection($index, $databaseName, $collectionName, $indexName));

        // is in collection
        $index = ['key' => ['guid' => 1]];
        $this->assertTrue(MongoStore::isIndexFieldNameInCollection($index, $databaseName, $collectionName, $indexName));
        $this->assertEqual($indexName, 'guid_1');

        // is in collection irrespective of field order
        $index = ['key' => ['guid' => -1]];
        $this->assertTrue(MongoStore::isIndexFieldNameInCollection($index, $databaseName, $collectionName, $indexName));

        // is in collection if its not the first key in collection
        $index = ['key' => ['dirtySR' => 1]];
        $this->assertTrue(MongoStore::isIndexFieldNameInCollection($index, $databaseName, $collectionName, $indexName));
        $this->assertEqual($indexName, 'guid_1_dirtySR_1');

        // is in collection if its not the first key in index
        $index = ['key' => ['code' => 1, 'dirtySR' => 1]];
        $this->assertTrue(MongoStore::isIndexFieldNameInCollection($index, $databaseName, $collectionName, $indexName));
        $this->assertEqual($indexName, 'guid_1_dirtySR_1');
    }

    public function testMongoStoreIsAllIndexFieldNamesInCollection_LexProject_Ok()
    {
        // setup
        $this->environ = new LexiconMongoTestEnvironment();
        $this->environ->clean();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user1 = new UserModel($user1Id);
        $srProject = null;
        $projectId = ProjectCommands::createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE,
            LexProjectModel::LEXICON_APP, $user1->id->asString(), $this->environ->website, $srProject);
        $project = new LexProjectModel($projectId);
        $databaseName = $project->databaseName();
        $collectionName = LexEntryModel::mapper($databaseName)->getCollectionName();

        // is not in collection
        $index = ['key' => ['code' => 1]];
        $this->assertFalse(MongoStore::isAllIndexFieldNamesInCollection($index, $databaseName, $collectionName, $indexName));
        $this->assertEqual($indexName, '');

        // is not in collection irrespective of field order
        $index = ['key' => ['code' => -1]];
        $this->assertFalse(MongoStore::isAllIndexFieldNamesInCollection($index, $databaseName, $collectionName, $indexName));

        // is not in collection if only the second field name exists
        $index = ['key' => ['code' => 1, 'dirtySR' => 1]];
        $this->assertFalse(MongoStore::isAllIndexFieldNamesInCollection($index, $databaseName, $collectionName, $indexName));
        $this->assertEqual($indexName, '');

        // is not in collection if only one field name matches
        $index = ['key' => ['dirtySR' => 1]];
        $this->assertFalse(MongoStore::isAllIndexFieldNamesInCollection($index, $databaseName, $collectionName, $indexName));
        $this->assertEqual($indexName, '');

        // is in collection
        $index = ['key' => ['guid' => 1]];
        $this->assertTrue(MongoStore::isAllIndexFieldNamesInCollection($index, $databaseName, $collectionName, $indexName));
        $this->assertEqual($indexName, 'guid_1');

        // is in collection irrespective of field order
        $index = ['key' => ['guid' => -1]];
        $this->assertTrue(MongoStore::isAllIndexFieldNamesInCollection($index, $databaseName, $collectionName, $indexName));

        // is in collection if both match
        $index = ['key' => ['guid' => 1, 'dirtySR' => 1]];
        $this->assertTrue(MongoStore::isAllIndexFieldNamesInCollection($index, $databaseName, $collectionName, $indexName));
        $this->assertEqual($indexName, 'guid_1_dirtySR_1');

        // is in collection if both match irrespective of field name order
        $index = ['key' => ['dirtySR' => 1, 'guid' => 1]];
        $this->assertTrue(MongoStore::isAllIndexFieldNamesInCollection($index, $databaseName, $collectionName, $indexName));
        $this->assertEqual($indexName, 'guid_1_dirtySR_1');
    }

    public function testMongoStoreIsIndexIdenticalInCollection_LexProject_Ok()
    {
        // setup
        $this->environ = new LexiconMongoTestEnvironment();
        $this->environ->clean();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user1 = new UserModel($user1Id);
        $srProject = null;
        $projectId = ProjectCommands::createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE,
            LexProjectModel::LEXICON_APP, $user1->id->asString(), $this->environ->website, $srProject);
        $project = new LexProjectModel($projectId);
        $databaseName = $project->databaseName();
        $collectionName = LexEntryModel::mapper($databaseName)->getCollectionName();

        // is not in collection
        $index = ['key' => ['code' => 1]];
        $this->assertFalse(MongoStore::isIndexIdenticalInCollection($index, $databaseName, $collectionName));

        // is not in collection irrespective of field order
        $index = ['key' => ['code' => -1]];
        $this->assertFalse(MongoStore::isIndexIdenticalInCollection($index, $databaseName, $collectionName));

        // is not in collection if field missing
        $index = ['key' => ['guid' => 1]];
        $this->assertFalse(MongoStore::isIndexIdenticalInCollection($index, $databaseName, $collectionName));

        // is not in collection irrespective of field order and if field missing
        $index = ['key' => ['guid' => -1]];
        $this->assertFalse(MongoStore::isIndexIdenticalInCollection($index, $databaseName, $collectionName));

        // is not in collection if with 2 field names and field missing
        $index = ['key' => ['guid' => 1, 'dirtySR' => 1]];
        $this->assertFalse(MongoStore::isIndexIdenticalInCollection($index, $databaseName, $collectionName));

        // is not in collection if field different
        $index = ['key' => ['guid' => 1], 'unique' => false];
        $this->assertFalse(MongoStore::isIndexIdenticalInCollection($index, $databaseName, $collectionName));

        // is in collection
        $index = ['key' => ['guid' => 1], 'unique' => true];
        $this->assertTrue(MongoStore::isIndexIdenticalInCollection($index, $databaseName, $collectionName));

        // is in collection if with 2 field names
        $index = ['key' => ['guid' => 1, 'dirtySR' => 1], 'unique' => true];
        $this->assertTrue(MongoStore::isIndexIdenticalInCollection($index, $databaseName, $collectionName));
    }

    public function testMongoStoreEnsureIndexesInCollection_LexProject_Ok()
    {
        // setup
        $this->environ = new LexiconMongoTestEnvironment();
        $this->environ->clean();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user1 = new UserModel($user1Id);
        $srProject = null;
        $projectId = ProjectCommands::createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE,
            LexProjectModel::LEXICON_APP, $user1->id->asString(), $this->environ->website, $srProject);
        $project = new LexProjectModel($projectId);
        $databaseName = $project->databaseName();
        $collectionName = LexEntryModel::mapper($databaseName)->getCollectionName();
        $indexes = LexEntryModel::mapper($databaseName)->INDEXES_REQUIRED;
        foreach ($indexes as $index) {
            $this->assertTrue(MongoStore::isIndexIdenticalInCollection($index, $databaseName, $collectionName),
                'index not in lexicon: ' . var_export($index, true));
        }
        $additionalIndex = ['key' => ['test' => 1]];
        $indexes[] = $additionalIndex;
        $this->assertFalse(MongoStore::isAllIndexFieldNamesInCollection($additionalIndex, $databaseName, $collectionName));

        MongoStore::ensureIndexesInCollection($databaseName, $collectionName, $indexes);

        foreach ($indexes as $index) {
            $this->assertTrue(MongoStore::isIndexIdenticalInCollection($index, $databaseName, $collectionName),
                'index not in lexicon: ' . var_export($index, true));
        }
    }

    public function testCreateProject_LexProject_IndexesCreated()
    {
        $this->environ = new LexiconMongoTestEnvironment();
        $this->environ->clean();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user1 = new UserModel($user1Id);
        $srProject = null;

        $projectId = ProjectCommands::createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE,
            LexProjectModel::LEXICON_APP, $user1->id->asString(), $this->environ->website, $srProject);

        $project = new LexProjectModel($projectId);
        $databaseName = $project->databaseName();
        $collectionName = LexEntryModel::mapper($databaseName)->getCollectionName();
        $indexCount = iterator_count(MongoStore::getCollectionIndexes($databaseName, $collectionName));
        $this->assertTrue($indexCount >= 3);
        $index = ['key' => ['guid' => 1]];
        $this->assertTrue(MongoStore::isAllIndexFieldNamesInCollection($index, $databaseName, $collectionName));
        $collectionName = LexOptionListModel::mapper($databaseName)->getCollectionName();
        $indexCount = iterator_count(MongoStore::getCollectionIndexes($databaseName, $collectionName));
        $this->assertTrue($indexCount >= 2);
        $index = ['key' => ['code' => 1]];
        $this->assertTrue(MongoStore::isAllIndexFieldNamesInCollection($index, $databaseName, $collectionName));
    }

    public function testCreateProject_NoSRProject_NotSRProjectWithNoLinks()
    {
        $this->environ = new LexiconMongoTestEnvironment();
        $this->environ->clean();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user1 = new UserModel($user1Id);
        $srProject = null;

        $projectId = ProjectCommands::createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE,
            LexProjectModel::LEXICON_APP, $user1->id->asString(), $this->environ->website, $srProject);

        $project = new LexProjectModel($projectId);
        $assetImagePath = $project->getImageFolderPath();
        $assetAudioPath = $project->getAudioFolderPath();
        $this->assertFalse($project->hasSendReceive());
        $this->assertFalse(is_link($assetImagePath));
        $this->assertFalse(is_link($assetAudioPath));

        $projectWorkPath = $project->getSendReceiveWorkFolder();
        FileUtilities::removeFolderAndAllContents($project->getAssetsFolderPath());
        FileUtilities::removeFolderAndAllContents($projectWorkPath);
    }

    public function testCreateProject_NewSRProject_SRProjectWithLinks()
    {
        $this->environ = new LexiconMongoTestEnvironment();
        $this->environ->clean();
        $user1Id = $this->environ->createUser("user1name", "User1 Name", "user1@example.com");
        $user1 = new UserModel($user1Id);
        $srProject = array(
            'identifier' => 'srIdentifier',
            'name' => 'srName',
            'repository' => 'http://public.languagedepot.org',
            'role' => 'manager'
        );

        $projectId = ProjectCommands::createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE,
            LexProjectModel::LEXICON_APP, $user1->id->asString(), $this->environ->website, $srProject);

        $project = new LexProjectModel($projectId);
        $assetImagePath = $project->getImageFolderPath();
        $assetAudioPath = $project->getAudioFolderPath();
        $this->assertTrue($project->hasSendReceive());
        $this->assertTrue(is_link($assetImagePath));
        $this->assertTrue(is_link($assetAudioPath));

        $projectWorkPath = $project->getSendReceiveWorkFolder();
        FileUtilities::removeFolderAndAllContents($project->getAssetsFolderPath());
        FileUtilities::removeFolderAndAllContents($projectWorkPath);
    }

}
