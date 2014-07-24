<?php

namespace models\languageforge\lexicon\commands;

use libraries\shared\palaso\CodeGuard;
use models\languageforge\lexicon\config\LexiconConfigObj;
use models\languageforge\lexicon\LexEntryModel;
use models\languageforge\lexicon\LexEntryWithCommentsEncoder;
use models\languageforge\lexicon\LexEntryListModel;
use models\languageforge\lexicon\LexCommentModel;
use models\languageforge\lexicon\LexCommentReply;
use models\languageforge\lexicon\LexiconProjectModel;
use models\mapper\JsonDecoder;
use models\mapper\JsonEncoder;

class LexCommentCommands {
    public static function updateComment($projectId, $userId, $params) {
        // if this is an update, assert that the $userId and the userRef on the comment are the same, otherwise throw

    }

    public static function updateReply($projectId, $userId, $commentId, $params) {
        // if this is an update, assert that the $userId and the userRef on the comment are the same, otherwise throw

    }

    public static function deleteComment($projectId, $userId, $commentId) {
        // if the userId is different from the author, throw if user does not have DELETE privilege

    }

    public static function deleteReply($projectId, $userId, $commentId, $replyId) {
        // if the userId is different from the author, throw if user does not have DELETE privilege

    }






	/**
	 * 
	 * @param string $projectId
	 * @param array $comment - comment data array to create or update
	 * @param string $userId
	 * @throws \Exception
	 * @return array
	 */
    /*
	public static function updateCommentOrReply($projectId, $comment, $userId) {
		CodeGuard::checkTypeAndThrow($comment, 'array');
		$project = new LexiconProjectModel($projectId);
		$entry = new LexEntryModel($project, $comment['entryId']);
		$field = $comment['field'];
		switch ($field) {
			case 'lexeme':
				self::updateComment($entry->lexeme[$comment['inputSystem']], $comment, $userId);
				break;
			case 'sense_definition':
				$sense = $entry->getSense($comment['senseId']);
				self::updateComment($sense->definition[$comment['inputSystem']], $comment, $userId);
				break;
			case 'sense_gloss':
				$sense = $entry->getSense($comment['senseId']);
				self::updateComment($sense->gloss[$comment['inputSystem']], $comment, $userId);
				break;
			case 'sense_partOfSpeech':
			case 'sense_semanticDomain':
				$field = substr($field, 6);
				$sense = $entry->getSense($comment['senseId']);
				self::updateComment($sense->$field, $comment, $userId);
				break;
			case 'sense_example_sentence':
			case 'sense_example_translation':
				$field = substr($field, 14);
				$sense = $entry->getSense($comment['senseId']);
				$example = $sense->getExample($comment['exampleId']);
				$multitext = $example->$field;
				self::updateComment($multitext[$comment['inputSystem']], $comment, $userId);
				break;
			default:
				throw new \Exception("unknown comment field '$field' in LexCommentCommands::updateComment");
		}
		$entry->write();
		return LexEntryWithCommentsEncoder::encode($entry);
	}
	
	
	public static function deleteCommentById($projectId, $entryId, $commentId) {
		// loop through all possible comment arrays and remove the comment with the matching id...
	}
    */

	
	/**
	 * 
	 * @param LexiconFieldWithComments $field
	 * @param array $data
	 * @param string $userId
	 */
    /*
	private static function updateComment($field, $data, $userId) {
		$id = $data['id'];
		$existing = ($id != '');
		if (key_exists('parentId', $data)) {
			$comment = $field->getComment($data['parentId']);
			if ($existing) {
				$reply = $comment->getReply($id);
			} else {
				$reply = new LexCommentReply();
			}
			$reply->content = $data['content'];
			$reply->dateModified = new \DateTime();
			$reply->userRef->id = $userId;
			
			if ($existing) {
				$comment->setReply($id, $reply);
			} else {
				$comment->replies[] = $reply;
			}
		} else {
			if ($existing) {
				$comment = $field->getComment($id);
			} else {
				$comment = new LexCommentModel();
				$comment->regarding = $data['regarding'];
			}
			$comment->content = $data['content'];
			$comment->dateModified = new \DateTime();
			$comment->userRef->id = $userId;
			
			if ($existing) {
				$field->setComment($id, $comment);
			} else {
				$field->comments[] = $comment;
			}
		}
	}
    */
	
}

?>
