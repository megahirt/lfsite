<?php

use models\languageforge\lexicon\commands\LexProjectCommands;
use models\languageforge\lexicon\dto\LexBaseViewDto;
use models\languageforge\lexicon\LexiconProjectModel;
use models\languageforge\lexicon\LiftMergeRule;
use models\commands\ProjectCommands;
use models\shared\rights\ProjectRoles;
use models\shared\rights\SiteRoles;
use models\UserModel;

require_once(dirname(__FILE__) . '/../../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');
require_once(TestPath . 'common/MongoTestEnvironment.php');
require_once(dirname(__FILE__) . '/LexTestData.php');

class TestLexProjectCommands extends UnitTestCase {

	function testUpdateConfig_ConfigPersists() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$userId = $e->createUser("User", "Name", "name@example.com");
		$user = new UserModel($userId);
		$user->role = SiteRoles::USER;

		$project = $e->createProject(SF_TESTPROJECT);
		$projectId = $project->id->asString();
		
		$project->addUser($userId, ProjectRoles::CONTRIBUTOR);
		$user->addProject($projectId);
		$user->write();
		$project->write();
				
		$config = json_decode(json_encode(LexBaseViewDto::encode($projectId, $userId)['config']), true);
		
		$this->assertTrue($config['tasks']['addMeanings']['visible']);
		$this->assertEqual($config['entry']['fields']['lexeme']['inputSystems'][0], 'th');

		$config['tasks']['addMeanings']['visible'] = false;
		$config['entry']['fields']['lexeme']['inputSystems'] = array('my', 'th');
		
		LexProjectCommands::updateConfig($projectId, $config);
		
		$project2 = new LexiconProjectModel($projectId);
		
		// test for a few default values
		$this->assertEqual($project2->inputSystems['en']->tag, 'en');
		$this->assertTrue($project2->config->tasks['dbe']->visible);
		$this->assertEqual($project2->config->entry->fields['lexeme']->label, 'Word');
		
		// test for updated values
		$this->assertFalse($project2->config->tasks['addMeanings']->visible);
		$this->assertEqual($project2->config->entry->fields['lexeme']->inputSystems[0], 'my');
		$this->assertEqual($project2->config->entry->fields['lexeme']->inputSystems[1], 'th');
	}
	
	function testProjectCRUD_CRUDOK() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
			
		// Create
		$param = array(
				'id' => '',
				'projectname' => SF_TESTPROJECT,
				'projectCode' => 'SomeCode',
				'featured' => true
		);
		$userId = $e->createUser('userName', 'User Name', 'user@example.com', SiteRoles::SYSTEM_ADMIN);
		$id = LexProjectCommands::updateProject($param, $userId);
		$this->assertNotNull($id);
		$this->assertEqual(24, strlen($id));
	
		// Read
		$result = LexProjectCommands::readProject($id);
		$this->assertNotNull($result['id']);
		$this->assertEqual(SF_TESTPROJECT, $result['projectname']);
		$this->assertEqual('SomeCode', $result['projectCode']);
		$this->assertTrue($result['featured']);
		$this->assertTrue(isset($result['inputSystems']));
		$this->assertTrue(isset($result['config']));
		
		// Update
		$result['projectCode'] = 'AnotherCode';
		$id = LexProjectCommands::updateProject($e->fixJson($result), $userId);
		$this->assertNotNull($id);
		$this->assertEqual($result['id'], $id);
	
		// Delete
		$result = ProjectCommands::deleteProjects(array($id));
		$this->assertTrue($result);
	}
	
	function testImportLift_EachDuplicateSetting_LiftFileAddedOk() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT);
		$projectId = $project->id->asString();
		$import = LexTestData::Import(LiftMergeRule::IMPORT_LOSES, false);
		
		// no LIFT file initially
		$fileName = str_replace(array('/', '\\', '?', '%', '*', ':', '|', '"', '<', '>'), '_', $import['file']['name']);	// replace special characters with _
		$filePath = $project->getAssetsFolderPath() . '/' . $fileName;
		$this->assertFalse(file_exists($filePath), 'Imported LIFT file should not exist');
		
		// importLoses: LIFT file added
		LexProjectCommands::importLift($projectId, $import);
		$this->assertTrue(file_exists($filePath), 'Imported LIFT file should be in expected location');
		
		// create another LIFT file
		$filePathOther = $project->getAssetsFolderPath() . '/other-' . $fileName;
		@rename($filePath, $filePathOther); 
		$this->assertTrue(file_exists($filePathOther), 'Other LIFT file should exist');
		$this->assertFalse(file_exists($filePath), 'Imported LIFT file should not exist');

		// importLoses: LIFT file not added, other still exists
		LexProjectCommands::importLift($projectId, $import);
		$this->assertTrue(file_exists($filePathOther), 'Other LIFT file should exist');
		$this->assertFalse(file_exists($filePath), 'Imported LIFT file should not exist');
		
		// importWins: LIFT file added, other removed
		$import = LexTestData::ImportSettings($import, LiftMergeRule::IMPORT_WINS);
		LexProjectCommands::importLift($projectId, $import);
		$this->assertFalse(file_exists($filePathOther), 'Other LIFT file should not exist');
		$this->assertTrue(file_exists($filePath), 'Imported LIFT file should exist');
		
		// create another LIFT file
		$filePathOther = $project->getAssetsFolderPath() . '/other-' . $fileName;
		@rename($filePath, $filePathOther);
		$this->assertTrue(file_exists($filePathOther), 'Other LIFT file should exist');
		$this->assertFalse(file_exists($filePath), 'Imported LIFT file should not exist');
		
		// createDuplicates: LIFT file added, other removed
		$import = LexTestData::ImportSettings($import, LiftMergeRule::CREATE_DUPLICATES);
		LexProjectCommands::importLift($projectId, $import);
		$this->assertFalse(file_exists($filePathOther), 'Other LIFT file should not exist');
		$this->assertTrue(file_exists($filePath), 'Imported LIFT file should exist');
	}
	
}

?>
