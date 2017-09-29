<?php

namespace Api\Model\Shared\Command;

use Api\Model\Languageforge\Lexicon\Command\LexEntryCommands;
use Api\Model\Languageforge\Lexicon\LexEntryModel;
use Api\Model\Scriptureforge\Sfchecks\AnswerModel;
use Api\Model\Scriptureforge\Sfchecks\QuestionModel;
use Api\Model\Scriptureforge\Sfchecks\TextModel;
use Api\Model\Scriptureforge\Sfchecks\UnreadTextModel;
use Api\Model\Shared\ActivityModel;
use Api\Model\Shared\CommentModel;
use Api\Model\Shared\ProjectModel;
use Api\Model\Shared\UserModel;
use Api\Model\Shared\UnreadActivityModel;
use Api\Model\Shared\UnreadAnswerModel;
use Api\Model\Shared\UnreadCommentModel;
use Api\Model\Shared\UnreadQuestionModel;

class ActivityCommands
{
    /**
     *
     * @param ProjectModel $projectModel
     * @param string $questionId
     * @param string $answerId
     * @param CommentModel $commentModel
     * @param string $mode
     * @return string activity id
     */
    public static function updateComment($projectModel, $questionId, $answerId, $commentModel, $mode = "update")
    {
        $activity = new ActivityModel($projectModel);
        $question = new QuestionModel($projectModel, $questionId);
        $answer = $question->readAnswer($answerId);
        $text = new TextModel($projectModel, $question->textRef->asString());
        $user = new UserModel($commentModel->userRef->asString());
        $user2 = new UserModel($answer->userRef->asString());
        $activity->action = ($mode == 'update') ? ActivityModel::UPDATE_COMMENT : ActivityModel::ADD_COMMENT;
        $activity->userRef->id = $commentModel->userRef->asString();
        $activity->userRef2->id = $answer->userRef->asString();
        $activity->textRef->id = $text->id->asString();
        $activity->questionRef->id = $questionId;
        $activity->addContent(ActivityModel::TEXT, $text->title);
        $activity->addContent(ActivityModel::ANSWER, $answer->content);
        $activity->addContent(ActivityModel::QUESTION, $question->getTitleForDisplay());
        $activity->addContent(ActivityModel::COMMENT, $commentModel->content);
        $activity->addContent(ActivityModel::USER, $user->username);
        $activity->addContent(ActivityModel::USER2, $user2->username);
        $activityId = $activity->write();
        UnreadActivityModel::markUnreadForProjectMembers($activityId, $projectModel);
        UnreadCommentModel::markUnreadForProjectMembers($commentModel->id->asString(), $projectModel, $questionId, $commentModel->userRef->asString());

        return $activityId;
    }

    public static function addComment($projectModel, $questionId, $answerId, $commentModel)
    {
        return ActivityCommands::updateComment($projectModel, $questionId, $answerId, $commentModel, "add");
    }

    /**
     *
     * @param ProjectModel $projectModel
     * @param string $questionId
     * @param AnswerModel $answerModel
     * @param string $mode
     * @return string activity id
     */
    public static function updateAnswer($projectModel, $questionId, $answerModel, $mode = "update")
    {
        $activity = new ActivityModel($projectModel);
        $question = new QuestionModel($projectModel, $questionId);
        $text = new TextModel($projectModel, $question->textRef->asString());
        $user = new UserModel($answerModel->userRef->asString());

        $activity->action = ($mode == "update") ? ActivityModel::UPDATE_ANSWER : ActivityModel::ADD_ANSWER;
        $activity->userRef->id = $answerModel->userRef->asString();
        $activity->textRef->id = $text->id->asString();
        $activity->questionRef->id = $questionId;
        $activity->addContent(ActivityModel::TEXT, $text->title);
        $activity->addContent(ActivityModel::QUESTION, $question->getTitleForDisplay());
        $activity->addContent(ActivityModel::ANSWER, $answerModel->content);
        $activity->addContent(ActivityModel::USER, $user->username);
        $activityId = $activity->write();
        UnreadActivityModel::markUnreadForProjectMembers($activityId, $projectModel);
        UnreadAnswerModel::markUnreadForProjectMembers($answerModel->id->asString(), $projectModel, $questionId, $answerModel->userRef->asString());

        return $activityId;
    }

    public static function addAnswer($projectModel, $questionId, $answerModel)
    {
        return ActivityCommands::updateAnswer($projectModel, $questionId, $answerModel, 'add');
    }

    /**
     *
     * @param ProjectModel $projectModel
     * @param string $textId
     * @param TextModel $textModel
     * @return string activity id
     */
    public static function addText($projectModel, $textId, $textModel)
    {
        $activity = new ActivityModel($projectModel);
        $activity->action = ActivityModel::ADD_TEXT;
        $activity->textRef->id = $textId;
        $activity->addContent(ActivityModel::TEXT, $textModel->title);
        $activityId = $activity->write();
        UnreadActivityModel::markUnreadForProjectMembers($activityId, $projectModel);
        UnreadTextModel::markUnreadForProjectMembers($textId, $projectModel);

        return $activityId;
    }

    /**
     * @param ProjectModel $projectModel
     * @param string $questionId
     * @param QuestionModel $questionModel
     * @return string activity id
     */
    public static function addQuestion($projectModel, $questionId, $questionModel)
    {
        $activity = new ActivityModel($projectModel);
        $text = new TextModel($projectModel, $questionModel->textRef->asString());
        $activity->action = ActivityModel::ADD_QUESTION;
        $activity->textRef->id = $questionModel->textRef->asString();
        $activity->questionRef->id = $questionId;
        $activity->addContent(ActivityModel::TEXT, $text->title);
        $activity->addContent(ActivityModel::QUESTION, $questionModel->getTitleForDisplay());
        $activityId = $activity->write();
        UnreadActivityModel::markUnreadForProjectMembers($activityId, $projectModel);
        UnreadQuestionModel::markUnreadForProjectMembers($questionId, $projectModel);

        return $activityId;
    }

    /**
     *
     * @param ProjectModel $projectModel
     * @param string $userId
     * @return string activity id
     */
    public static function addUserToProject($projectModel, $userId)
    {
        $activity = new ActivityModel($projectModel);
        $activity->action = ActivityModel::ADD_USER_TO_PROJECT;
        $activity->userRef->id = $userId; // we can use the userRef in this case because we don't keep track of the user that performed this action
        $user = new UserModel($userId);
        $activity->addContent(ActivityModel::USER, $user->username);
        $activityId = $activity->write();
        UnreadActivityModel::markUnreadForProjectMembers($activityId, $projectModel);

        return $activityId;
    }

    // this may only be useful to log this activity for answers on which the user has commented on or has answered him/herself
    // TODO: how do we implement this?
    /**
     *
     * @param ProjectModel $projectModel
     * @param string $questionId
     * @param string $answerId
     * @param string $userId
     * @param string $mode
     * @return string activity id
     */
    public static function updateScore($projectModel, $questionId, $answerId, $userId, $mode = 'increase')
    {
        $question = new QuestionModel($projectModel, $questionId);
        $text = new TextModel($projectModel, $question->textRef->asString());
        $answer = $question->answers[$answerId];
        $user = new UserModel($userId);
        $user2 = new UserModel($answer->userRef->asString());
        $activity = new ActivityModel($projectModel);
        $activity->action = ($mode == 'increase') ? ActivityModel::INCREASE_SCORE : ActivityModel::DECREASE_SCORE;
        $activity->userRef->id = $userId;
        $activity->textRef->id = $text->id->asString();
        $activity->questionRef->id = $questionId;
        $activity->addContent(ActivityModel::TEXT, $text->title);
        $activity->addContent(ActivityModel::QUESTION, $question->getTitleForDisplay());
        $activity->addContent(ActivityModel::ANSWER, $answer->content);
        $activity->addContent(ActivityModel::USER, $user->username);
        $activity->addContent(ActivityModel::USER, $user2->username);
        $activityId = $activity->write();
        UnreadActivityModel::markUnreadForProjectMembers($activityId, $projectModel);

        return $activityId;
    }

    /**
     *
     * @param ProjectModel $projectModel
     * @param string $userId
     * @param LexEntryModel $entry
     * @param string $action
     * @return string activity id
     */
    public static function writeEntry($projectModel, $userId, $entry, $action)
    {
        $activity = new ActivityModel($projectModel);
        $activity->entryRef->id = $entry->id->asString();
        $user = new UserModel($userId);
        $activity->userRef->id = $userId;
        if ($action == 'update') {
            $activity->action = ActivityModel::UPDATE_ENTRY;
            $title = LexEntryCommands::getEntryLexeme($projectModel->id->asString(), $entry->id->asString());
        } else {
            $activity->action = ActivityModel::ADD_ENTRY;
            try {
                $title = LexEntryCommands::getEntryLexeme($projectModel->id->asString(), $entry->id->asString());
            } catch (\Exception $ex) {
                $title = '';
            }
        }

        $activity->addContent(ActivityModel::ENTRY, $title);
        $activity->addContent(ActivityModel::USER, $user->username);

        return $activity->write();
    }

    /**
     *
     * @param ProjectModel $projectModel
     * @param string $userId
     * @param string $id entry id
     * @return string activity id
     */
    public static function deleteEntry($projectModel, $userId, $id)
    {
        $activity = new ActivityModel($projectModel);
        $activity->userRef->id = $userId;
        $activity->action = ActivityModel::DELETE_ENTRY;

        $lexeme = LexEntryCommands::getEntryLexeme($projectModel->id->asString(), $id);
        $activity->addContent(ActivityModel::ENTRY, $lexeme);

        return $activity->write();
    }
}
