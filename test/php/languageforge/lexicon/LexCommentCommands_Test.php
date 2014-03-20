<?php

use models\languageforge\lexicon\commands\LexCommentCommands;

use models\languageforge\lexicon\settings\LexiconConfigObj;

use models\languageforge\lexicon\commands\LexProjectCommands;

use models\languageforge\lexicon\commands\LexEntryCommands;

use models\languageforge\lexicon\LexCommentReply;

use models\languageforge\lexicon\LexComment;

use models\languageforge\lexicon\Example;

use models\languageforge\lexicon\Sense;

use models\languageforge\lexicon\LexiconFieldWithComments;

use models\languageforge\lexicon\LexEntryModel;

use models\languageforge\lexicon\LexiconProjectModel;

require_once(dirname(__FILE__) . '/../../TestConfig.php');
require_once(SimpleTestPath . 'autorun.php');

require_once(TestPath . 'common/MongoTestEnvironment.php');

class TestLexCommentCommands extends UnitTestCase {
	
	function testUpdateLexemeComment_NewComment_CommentAdded() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT);
		$projectId = $project->id->asString();
		
		$entry = new LexEntryModel($project);
		$ws = 'th';
		$entry->lexeme->form($ws, 'apple');

		$sense = new Sense();
		$sense->definition->form('en', 'red fruit');
		$sense->partOfSpeech->value = 'noun';
		
		$example = new Example();
		$example->sentence->form('th', 'example1');
		$example->translation->form('en', 'trans1');
		
		$sense->examples[] = $example;
		
		$entry->senses[] = $sense;
		
		$entryId = $entry->write();
		
		$commentData = array(
			'id' => '',
			'content' => 'I like this lexeme a lot',
			'regarding' => 'apple',
			'score' => 5
		);
		
		LexCommentCommands::updateLexemeComment($projectId, $entryId, $ws, $commentData, '12345');
		
		$entry->read($entryId);
		
		$comment = $entry->lexeme[$ws]->comments[0];
		$this->assertEqual($comment->content, 'I like this lexeme a lot');
		$this->assertEqual($comment->score, 0, "comment score should not be updated by this method");
		$this->assertEqual($comment->regarding, 'apple');
		$this->assertNotEqual($comment->id, '', 'comment should have a unique id');
	}
	
	function testUpdateLexemeComment_ExistingComment_CommentUpdatedOk() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT);
		$projectId = $project->id->asString();
		
		$entry = new LexEntryModel($project);
		$ws = 'th';
		$entry->lexeme->form($ws, 'apple');

		$sense = new Sense();
		$sense->definition->form('en', 'red fruit');
		$sense->partOfSpeech->value = 'noun';
		
		$example = new Example();
		$example->sentence->form('th', 'example1');
		$example->translation->form('en', 'trans1');
		
		$sense->examples[] = $example;
		
		$entry->senses[] = $sense;
		
		$entryId = $entry->write();
		
		$commentData = array(
			'id' => '',
			'content' => 'I like this lexeme a lot',
			'regarding' => 'apple',
			'score' => 5
		);
		
		$entryArray = LexCommentCommands::updateLexemeComment($projectId, $entryId, $ws, $commentData, '12345');
		
		$commentId = $entryArray['lexeme'][$ws]['comments'][0]['id'];

		$commentData = array(
			'id' => $commentId,
			'content' => 'I changed my mind.  Not so much',
			'regarding' => 'apple2',
			'score' => 2
		);
		
		$entryArray = LexCommentCommands::updateLexemeComment($projectId, $entryId, $ws, $commentData, '12345');
		
		$entry->read($entryId);

		$comment = $entry->lexeme[$ws]->comments[0];
		$this->assertEqual($comment->content, 'I changed my mind.  Not so much');
		$this->assertEqual($comment->score, 0, "comment score should not be updated by this method");
		$this->assertEqual($comment->regarding, 'apple', "regarding field should not be updated on existing comment");
		$this->assertNotEqual($comment->id, '', 'comment should have a unique id');
		
	}
	
	function testUpdateLexemeReply_NewReply_ReplyAdded() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT);
		$projectId = $project->id->asString();
		
		$entry = new LexEntryModel($project);
		$ws = 'th';
		$entry->lexeme->form($ws, 'apple');

		$sense = new Sense();
		$sense->definition->form('en', 'red fruit');
		$sense->partOfSpeech->value = 'noun';
		
		$example = new Example();
		$example->sentence->form('th', 'example1');
		$example->translation->form('en', 'trans1');
		
		$sense->examples[] = $example;
		
		$entry->senses[] = $sense;
		
		$entryId = $entry->write();
		
		$commentData = array(
			'id' => '',
			'content' => 'I like this lexeme a lot',
			'regarding' => 'apple',
			'score' => 5
		);
		
		$entryArray = LexCommentCommands::updateLexemeComment($projectId, $entryId, $ws, $commentData, '12345');
		
		$commentId = $entryArray['lexeme'][$ws]['comments'][0]['id'];

		$replyData = array(
			'id' => '',
			'content' => 'Plus 1'
		);
		
		$entryArray = LexCommentCommands::updateLexemeReply($projectId, $entryId, $ws, $commentId, $replyData, '12345');
		
		$entry->read($entryId);

		$reply = $entry->lexeme[$ws]->comments[0]->replies[0];
		$this->assertEqual($reply->content, 'Plus 1');
		$this->assertNotEqual($reply->id, '', 'comment should have a unique id');
		
	}
	
	function testUpdateLexemeReply_ExistingReply_ReplyUpdatedOk() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT);
		$projectId = $project->id->asString();
		
		$entry = new LexEntryModel($project);
		$ws = 'th';
		$entry->lexeme->form($ws, 'apple');

		$sense = new Sense();
		$sense->definition->form('en', 'red fruit');
		$sense->partOfSpeech->value = 'noun';
		
		$example = new Example();
		$example->sentence->form('th', 'example1');
		$example->translation->form('en', 'trans1');
		
		$sense->examples[] = $example;
		
		$entry->senses[] = $sense;
		
		$entryId = $entry->write();
		
		$commentData = array(
			'id' => '',
			'content' => 'I like this lexeme a lot',
			'regarding' => 'apple',
			'score' => 5
		);
		
		$entryArray = LexCommentCommands::updateLexemeComment($projectId, $entryId, $ws, $commentData, '12345');
		
		$commentId = $entryArray['lexeme'][$ws]['comments'][0]['id'];

		$replyData = array(
			'id' => '',
			'content' => 'Plus 1'
		);
		
		$entryArray = LexCommentCommands::updateLexemeReply($projectId, $entryId, $ws, $commentId, $replyData, '12345');
		
		$replyId = $entryArray['lexeme'][$ws]['comments'][0]['replies'][0]['id'];

		$replyData = array(
			'id' => $replyId,
			'content' => 'Plus 2'
		);

		LexCommentCommands::updateLexemeReply($projectId, $entryId, $ws, $commentId, $replyData, '12345');
		
		$entry->read($entryId);

		$reply = $entry->lexeme[$ws]->comments[0]->replies[0];
		$this->assertEqual($reply->content, 'Plus 2');
	}

	function testUpdateSenseComment_ExistingComment_UpdatesOk() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT);
		$projectId = $project->id->asString();
		
		$entry = new LexEntryModel($project);
		$entry->lexeme->form('th', 'apple');

		$sense = new Sense();
		$ws = 'en';
		$sense->definition->form($ws, 'red fruit');
		$sense->partOfSpeech->value = 'noun';
		
		$example = new Example();
		$example->sentence->form('th', 'example1');
		$example->translation->form('en', 'trans1');
		
		$sense->examples[] = $example;
		
		$entry->senses[] = $sense;
		
		$entryId = $entry->write();
		
		$commentData = array(
			'id' => '',
			'content' => 'I like this lexeme a lot',
			'regarding' => 'apple',
			'score' => 5
		);
		
		$entryArray = LexCommentCommands::updateSenseComment($projectId, $entryId, $sense->id, 'definition', $ws, $commentData, '12345');
		
		$commentId = $entryArray['senses'][0]['definition'][$ws]['comments'][0]['id'];

		$commentData = array(
			'id' => $commentId,
			'content' => 'I changed my mind.  Not so much',
			'regarding' => 'apple2',
			'score' => 2
		);
		
		$entryArray = LexCommentCommands::updateSenseComment($projectId, $entryId, $sense->id, 'definition', $ws, $commentData, '12345');
		
		$entry->read($entryId);

		$comment = $entry->senses[0]->definition[$ws]->comments[0];
		$this->assertEqual($comment->content, 'I changed my mind.  Not so much');
		$this->assertEqual($comment->score, 0, "comment score should not be updated by this method");
		$this->assertEqual($comment->regarding, 'apple', "regarding field should not be updated on existing comment");
		$this->assertNotEqual($comment->id, '', 'comment should have a unique id');
		
	}
	
	function testUpdateSenseReply_ExistingReply_UpdatesOk() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT);
		$projectId = $project->id->asString();
		
		$entry = new LexEntryModel($project);
		$ws = 'en';
		$entry->lexeme->form('th', 'apple');

		$sense = new Sense();
		$sense->definition->form($ws, 'red fruit');
		$sense->partOfSpeech->value = 'noun';
		
		$example = new Example();
		$example->sentence->form('th', 'example1');
		$example->translation->form('en', 'trans1');
		
		$sense->examples[] = $example;
		
		$entry->senses[] = $sense;
		
		$entryId = $entry->write();
		
		$commentData = array(
			'id' => '',
			'content' => 'I like this lexeme a lot',
			'regarding' => 'apple',
			'score' => 5
		);
		
		$entryArray = LexCommentCommands::updateSenseComment($projectId, $entryId, $sense->id, 'definition', $ws, $commentData, '12345');
		
		$commentId = $entryArray['senses'][0]['definition'][$ws]['comments'][0]['id'];

		$replyData = array(
			'id' => '',
			'content' => 'Plus 1'
		);
		
		$entryArray = LexCommentCommands::updateSenseReply($projectId, $entryId, $sense->id, 'definition', $ws, $commentId, $replyData, '12345');
		
		$replyId = $entryArray['senses'][0]['definition'][$ws]['comments'][0]['replies'][0]['id'];

		$replyData = array(
			'id' => $replyId,
			'content' => 'Plus 2'
		);

		LexCommentCommands::updateSenseReply($projectId, $entryId, $sense->id, 'definition', $ws, $commentId, $replyData, '12345');
		
		$entry->read($entryId);

		$reply = $entry->senses[0]->definition[$ws]->comments[0]->replies[0];
		$this->assertEqual($reply->content, 'Plus 2');
		$this->assertNotEqual($reply->id, '', 'comment should have a unique id');
	}

	function testUpdateExampleComment_ExistingComment_UpdatesOk() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT);
		$projectId = $project->id->asString();
		
		$entry = new LexEntryModel($project);
		$entry->lexeme->form('th', 'apple');

		$sense = new Sense();
		$sense->definition->form('en', 'red fruit');
		$sense->partOfSpeech->value = 'noun';
		
		$ws = 'th';
		$comment = new LexComment('test comment');
		$comment->regarding = 'example1';
		$commentId = $comment->id;
		$example = new Example();
		$example->sentence->form($ws, 'example1');
		$example->sentence[$ws]->comments[] = $comment;
		$example->translation->form('en', 'trans1');
		
		$sense->examples[] = $example;
		
		$entry->senses[] = $sense;
		
		$entryId = $entry->write();
		
		$commentData = array(
			'id' => $commentId,
			'content' => 'improved comment',
			'regarding' => 'whatever',
			'score' => 5
		);
		
		$entryArray = LexCommentCommands::updateExampleComment($projectId, $entryId, $sense->id, $example->id, 'sentence', $ws, $commentData, '12345');
		
		$entry->read($entryId);

		$comment = $entry->senses[0]->examples[0]->sentence[$ws]->comments[0];
		$this->assertEqual($comment->content, 'improved comment');
		$this->assertEqual($comment->score, 0, "comment score should not be updated by this method");
		$this->assertEqual($comment->regarding, 'example1', "regarding field should not be updated on existing comment");
		$this->assertNotEqual($comment->id, '', 'comment should have a unique id');
		
	}
	
	function testUpdateExampleReply_ExistingReply_UpdatesOk() {
		$e = new LexiconMongoTestEnvironment();
		$e->clean();
		
		$project = $e->createProject(SF_TESTPROJECT);
		$projectId = $project->id->asString();
		
		$entry = new LexEntryModel($project);
		$entry->lexeme->form('th', 'apple');

		$sense = new Sense();
		$sense->definition->form('en', 'red fruit');
		$sense->partOfSpeech->value = 'noun';
		
		$ws = 'th';
		$reply = new LexCommentReply('simple reply');
		$replyId = $reply->id;
		$comment = new LexComment('test comment');
		$comment->regarding = 'example1';
		$comment->replies[] = $reply;
		$commentId = $comment->id;
		$example = new Example();
		$example->sentence->form($ws, 'example1');
		$example->sentence[$ws]->comments[] = $comment;
		$example->translation->form('en', 'trans1');
		
		$sense->examples[] = $example;
		
		$entry->senses[] = $sense;
		
		$entryId = $entry->write();
		
		$replyData = array(
			'id' => $replyId,
			'content' => 'improved reply',
		);
		
		$entryArray = LexCommentCommands::updateExampleReply($projectId, $entryId, $sense->id, $example->id, 'sentence', $ws, $commentId, $replyData, '12345');
		
		$entry->read($entryId);

		$reply = $entry->senses[0]->examples[0]->sentence[$ws]->comments[0]->replies[0];
		$this->assertEqual($reply->content, 'improved reply');
		$this->assertNotEqual($reply->id, '', 'comment should have a unique id');
		
	}
	
}

?>
