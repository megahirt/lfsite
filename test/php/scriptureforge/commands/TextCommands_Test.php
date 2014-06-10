<?php

use models\shared\rights\ProjectRoles;
use models\shared\rights\SiteRoles;
use models\commands\TextCommands;
use models\TextModel;
use models\UserModel;

require_once(dirname(__FILE__) . '/../../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');
require_once(TestPath . 'common/MongoTestEnvironment.php');

class TestTextCommands extends UnitTestCase {

	function testDeleteTexts_1Text_1Removed() {
		$e = new MongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT);
		$text = new TextModel($project);
		$text->title = "Some Title";
		$text->write();
		
		$count = TextCommands::archiveTexts($project->id->asString(), array($text->id->asString()));
		
		$this->assertEqual($count, 1);
	}
	
	function testArchiveTexts_2Texts_1Archived() {
		$e = new MongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT);
		
		$text1 = new TextModel($project);
		$text1->title = "Some Title";
		$text1->write();
		$text2 = new TextModel($project);
		$text2->title = "Another Title";
		$text2->write();
		
		$this->assertEqual($text1->isArchived, false);
		$this->assertEqual($text2->isArchived, false);
		
		$count = TextCommands::archiveTexts($project->id->asString(), array($text1->id->asString()));
		
		$text1->read($text1->id->asString());
		$text2->read($text2->id->asString());
		$this->assertEqual($count, 1);
		$this->assertEqual($text1->isArchived, true);
		$this->assertEqual($text2->isArchived, false);
	}
	
	function testPublishTexts_2ArchivedTexts_1Published() {
		$e = new MongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT);
		
		$text1 = new TextModel($project);
		$text1->title = "Some Title";
		$text1->isArchived = true;
		$text1->write();
		$text2 = new TextModel($project);
		$text2->title = "Another Title";
		$text2->isArchived = true;
		$text2->write();
		
		$this->assertEqual($text1->isArchived, true);
		$this->assertEqual($text2->isArchived, true);
		
		$count = TextCommands::publishTexts($project->id->asString(), array($text1->id->asString()));
		
		$text1->read($text1->id->asString());
		$text2->read($text2->id->asString());
		$this->assertEqual($count, 1);
		$this->assertEqual($text1->isArchived, false);
		$this->assertEqual($text2->isArchived, true);
	}
	
}

?>
