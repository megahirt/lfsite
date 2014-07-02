<?php

use models\languageforge\lexicon\commands\LexEntryCommands;
use models\languageforge\lexicon\commands\LexProjectCommands;
use models\languageforge\lexicon\config\LexiconConfigObj;
use models\languageforge\lexicon\Example;
use models\languageforge\lexicon\LexComment;
use models\languageforge\lexicon\LexCommentReply;
use models\languageforge\lexicon\LexEntryModel;
use models\languageforge\lexicon\LexiconFieldWithComments;
use models\languageforge\lexicon\LexiconProjectModel;
use models\languageforge\lexicon\Sense;

require_once(dirname(__FILE__) . '/../../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');
require_once(TestPath . 'common/MongoTestEnvironment.php');

class TestLexEntryCommands extends UnitTestCase {
	
	function testReadEntry_NoComments_ReadBackOk() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
		$projectId = $project->id->asString();
		
		$entry = new LexEntryModel($project);
		$entry->lexeme->form('th', 'apple');

		$sense = new Sense();
		$sense->definition->form('en', 'red fruit');
		$sense->partOfSpeech->value = 'noun';
		
		$example = new Example();
		$example->sentence->form('th', 'example1');
		$example->translation->form('en', 'trans1');
		
		$sense->examples[] = $example;
		
		$entry->senses[] = $sense;
		
		$entryId = $entry->write();
		
		$newEntry = LexEntryCommands::readEntry($projectId, $entryId);
		
		$this->assertEqual($newEntry['lexeme']['th']['value'], 'apple');
		$this->assertEqual($newEntry['senses'][0]['definition']['en']['value'], 'red fruit');
		$this->assertEqual($newEntry['senses'][0]['partOfSpeech']['value'], 'noun');
		$this->assertEqual($newEntry['senses'][0]['examples'][0]['sentence']['th']['value'], 'example1');
		$this->assertEqual($newEntry['senses'][0]['examples'][0]['translation']['en']['value'], 'trans1');

		
	}

	function testReadEntry_HasComments_ReadBackOk() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
		$projectId = $project->id->asString();
		
		$entry = new LexEntryModel($project);
		$entry->lexeme->form('th', 'apple');
		
		$reply = new LexCommentReply('reply1');
		$comment = new LexComment('this is a comment');
		$comment->score = 5;
		$comment->regarding = "apple";
		$comment->replies[] = $reply;
		
		$entry->lexeme['th']->comments[] = $comment;
		

		$sense = new Sense();
		$sense->definition->form('en', 'red fruit');
		$sense->partOfSpeech->value = 'noun';
		
		$entry->senses[] = $sense;
		
		$entryId = $entry->write();
		
		$newEntry = LexEntryCommands::readEntry($projectId, $entryId);
		
		$this->assertEqual($newEntry['lexeme']['th']['value'], 'apple');
		$this->assertEqual($newEntry['senses'][0]['definition']['en']['value'], 'red fruit');
		$this->assertEqual($newEntry['senses'][0]['partOfSpeech']['value'], 'noun');
		$this->assertEqual($newEntry['lexeme']['th']['comments'][0]['content'], 'this is a comment');
		$this->assertEqual($newEntry['lexeme']['th']['comments'][0]['score'], 5);
		$this->assertEqual($newEntry['lexeme']['th']['comments'][0]['regarding'], 'apple');
		$this->assertEqual($newEntry['lexeme']['th']['comments'][0]['replies'][0]['content'], 'reply1');


	}
	
	function testUpdateEntry_DataPersists() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
		$projectId = $project->id->asString();
		
		$userId = $e->createUser('john', 'john', 'john');
		
		$entry = new LexEntryModel($project);
		$entry->lexeme->form('th', 'apple');

		$sense = new Sense();
		$sense->definition->form('en', 'red fruit');
		$sense->gloss->form('en', 'rose fruit');
		$sense->partOfSpeech->value = 'noun';
		
		$example = new Example();
		$example->sentence->form('th', 'example1');
		$example->translation->form('en', 'trans1');
		
		$sense->examples[] = $example;
		
		$entry->senses[] = $sense;
		
		$entryId = $entry->write();

		$params = LexEntryCommands::readEntry($projectId, $entryId);
		$params['lexeme']['th']['value'] = 'rose apple';
		$params['senses'][0]['partOfSpeech']['comments'] = array(array('content' => 'i vote for adj'));
		
		LexEntryCommands::updateEntry($projectId, $params, $userId);
		
		$newEntry = LexEntryCommands::readEntry($projectId, $entryId);

		$this->assertEqual($newEntry['lexeme']['th']['value'], 'rose apple');
		$this->assertEqual($newEntry['senses'][0]['definition']['en']['value'], 'red fruit');
		$this->assertEqual($newEntry['senses'][0]['gloss']['en']['value'], 'rose fruit');
		$this->assertEqual($newEntry['senses'][0]['partOfSpeech']['value'], 'noun');
		$this->assertEqual($newEntry['senses'][0]['examples'][0]['sentence']['th']['value'], 'example1');
		$this->assertEqual($newEntry['senses'][0]['examples'][0]['translation']['en']['value'], 'trans1');
		$this->assertEqual($newEntry['senses'][0]['partOfSpeech']['comments'], array());
		
	}
	
	function testListEntries_allEntries() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
		$projectId = $project->id->asString();
		
		$sense = new Sense();
		$sense->definition->form('en', 'apple');
		
		for ($i = 0; $i < 10; $i++) {
			$entry = new LexEntryModel($project);
			$entry->lexeme->form('th', 'Apfel' . $i);
			$entry->senses[] = $sense;
			$entry->write();
		}
		
		$result = LexEntryCommands::listEntries($projectId);
		$this->assertEqual($result->count, 10);
		$this->assertEqual($result->entries[5]['lexeme'], 'Apfel5');
	}
	
	function testListEntries_missingInfoDefinition_someEntries() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
		$projectId = $project->id->asString();
		
		$sense = new Sense();
		$sense->definition->form('en', 'apple');
		
		$senseNoDef = new Sense();
		$senseNoDef->definition->form('en', '');
		
		for ($i = 0; $i < 10; $i++) {
			$entry = new LexEntryModel($project);
			$entry->lexeme->form('th', 'Apfel' . $i);
			if ($i % 2 == 0) {
				$entry->senses[] = $sense;
			}
			$entry->write();
		}
		$entry = new LexEntryModel($project);
		$entry->lexeme->form('th', 'Apfel');
		$entry->senses[] = $senseNoDef;
		$entry->write();
		
		$result = LexEntryCommands::listEntries($projectId, LexiconConfigObj::DEFINITION);
		$this->assertEqual($result->count, 6);
	}

	function testListEntries_missingInfoPartOfSpeech_someEntries() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
		$projectId = $project->id->asString();
		
		$sense = new Sense();
		$sense->definition->form('en', 'apple');
		$sense->partOfSpeech->value = 'noun';
		
		$senseNoPos = new Sense();
		$senseNoPos->definition->form('en', 'orange');
		
		for ($i = 0; $i < 10; $i++) {
			$entry = new LexEntryModel($project);
			$entry->lexeme->form('th', 'Apfel' . $i);
			$entry->senses[] = $sense;
			if ($i % 2 == 0) {
				$entry->senses[] = $senseNoPos;
			}
			$entry->write();
		}
		
		$result = LexEntryCommands::listEntries($projectId, LexiconConfigObj::POS);
		$this->assertEqual($result->count, 5);
		
	}

	function testListEntries_missingInfoExamples_someEntries() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
		$projectId = $project->id->asString();
		
		
		
		for ($i = 0; $i < 10; $i++) {
			$entry = new LexEntryModel($project);
			$entry->lexeme->form('th', 'Apfel' . $i);
			$sense = new Sense();
			$sense->definition->form('en', 'apple');
			$sense->partOfSpeech->value = 'noun';
			$example = new Example();
			if ($i % 2 == 0) {
				$example->sentence->form('th', 'Ich esse Apfeln oft');
			}
			if ($i % 3 == 0) {
				$example->translation->form('en', 'I eat Apples often');
			}
			$sense->examples[] = $example;
			$entry->senses[] = $sense;
			$entry->write();
		}
		
		$result = LexEntryCommands::listEntries($projectId, LexiconConfigObj::EXAMPLE_SENTENCE);
		$this->assertEqual($result->count, 5);

		$result = LexEntryCommands::listEntries($projectId, LexiconConfigObj::EXAMPLE_TRANSLATION);
		$this->assertEqual($result->count, 6);
	}
	
	function testListEntries_someEntriesWithNoDefinition_Ok() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
		$projectId = $project->id->asString();
		
		for ($i = 0; $i < 10; $i++) {
			$entry = new LexEntryModel($project);
			$entry->lexeme->form('th', 'Apfel' . $i);
			if ($i % 2 == 0) {
				$sense = new Sense();
				$entry->senses[] = $sense;
			}
			if ($i % 3 == 0) {
				$sense = new Sense();
				$sense->definition->form('en', 'apple');
				$sense->partOfSpeech->value = 'noun';
				$entry->senses[] = $sense;
			}
			$entry->write();
		}
		
		$result = LexEntryCommands::listEntries($projectId);

		$this->assertEqual($result->entries[0]['lexeme'], 'Apfel0');
		$this->assertEqual($result->entries[0]['definition'], '');
		$this->assertEqual($result->entries[3]['definition'], 'apple');
	}
}

?>
