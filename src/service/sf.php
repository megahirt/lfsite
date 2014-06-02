<?php

use libraries\scriptureforge\sfchecks\ParatextExport;
use libraries\shared\palaso\exceptions\UserNotAuthenticatedException;
use libraries\shared\palaso\exceptions\UserUnauthorizedException;
use libraries\shared\palaso\CodeGuard;
use libraries\shared\palaso\JsonRpcServer;
use libraries\shared\Website;
use libraries\scriptureforge\sfchecks\Communicate;
use libraries\scriptureforge\sfchecks\Email;
use models\commands\ActivityCommands;
use models\commands\MessageCommands;
use models\commands\ProjectCommands;
use models\commands\QuestionCommands;
use models\commands\QuestionTemplateCommands;
use models\commands\TextCommands;
use models\commands\UserCommands;
use models\languageforge\lexicon\commands\LexCommentCommands;
use models\languageforge\lexicon\commands\LexEntryCommands;
use models\languageforge\lexicon\commands\LexProjectCommands;
use models\languageforge\lexicon\dto\LexBaseViewDto;
use models\languageforge\lexicon\dto\LexDbeDto;
use models\languageforge\lexicon\dto\LexManageUsersDto;
use models\languageforge\lexicon\dto\LexProjectDto;
use models\scriptureforge\dto\ProjectSettingsDto;
use models\shared\dto\ActivityListDto;
use models\shared\dto\RightsHelper;
use models\shared\dto\UserProfileDto;
use models\mapper\Id;
use models\mapper\JsonDecoder;
use models\mapper\JsonEncoder;
use models\mapper\MongoStore;
use models\shared\rights\Domain;
use models\shared\rights\Operation;
use models\shared\rights\Roles;
use models\sms\SmsSettings;
use models\AnswerModel;
use models\ProjectModel;
use models\ProjectSettingsModel;
use models\QuestionModel;
use models\UnreadMessageModel;
use models\UserModel;
use models\UserProfileModel;

require_once(APPPATH . 'vendor/autoload.php');
require_once(APPPATH . 'config/sf_config.php');
require_once(APPPATH . 'models/ProjectModel.php');
require_once(APPPATH . 'models/QuestionModel.php');
require_once(APPPATH . 'models/QuestionTemplateModel.php');
require_once(APPPATH . 'models/TextModel.php');
require_once(APPPATH . 'models/UserModel.php');

class Sf
{
	/**
	 * @var string
	 */
	private $_userId;
	
	private $_controller;
	
	private $_site;
	
	public function __construct($controller) {
		$this->_userId = (string)$controller->session->userdata('user_id');
		$this->_controller = $controller;
		$this->_site = Website::getSiteName();

		// "Kick" session every time we use an API call, so it won't time out
		$this->update_last_activity();

		// TODO put in the LanguageForge style error handler for logging / jsonrpc return formatting etc. CP 2013-07
 		ini_set('display_errors', 0);
	}
	
	//---------------------------------------------------------------
	// IMPORTANT NOTE TO THE DEVELOPERS 
	//---------------------------------------------------------------
	// When adding a new api method, also add your method name and appropriate RightsHelper statement as required by 
	// the method's context (project context or site context) to the RightsHelper::userCanAccessMethod() method 
	// FYI userCanAccessMethod() is a whitelist.  Anything not explicitly listed is denied access
	//
	// If an api method is ever renamed, remember to update the name in this method as well
	//---------------------------------------------------------------
	

	/*
	 * 
 ____               ___       ___                                   
/\  _`\            /\_ \     /\_ \                                  
\ \ \L\ \     __   \//\ \    \//\ \      ___    __  __  __    ____  
 \ \  _ <'  /'__`\   \ \ \     \ \ \    / __`\ /\ \/\ \/\ \  /',__\ 
  \ \ \L\ \/\  __/    \_\ \_    \_\ \_ /\ \L\ \\ \ \_/ \_/ \/\__, `\
   \ \____/\ \____\   /\____\   /\____\\ \____/ \ \___x___/'\/\____/
    \/___/  \/____/   \/____/   \/____/ \/___/   \/__//__/   \/___/ 
	
	 */
	
	//---------------------------------------------------------------
	// USER API
	//---------------------------------------------------------------
	
	/**
	 * Read a user from the given $id
	 * @param string $id
	 * @return array
	 */
	public function user_read($id) {
		return UserCommands::readUser($id);
	}
	
	/**
	 * Read the user profile from $id
	 * @param string $id
	 * @return UserProfileDto
	 */
	public function user_readProfile() {
		return UserProfileDto::encode($this->_userId);
	}
	
	/**
	 * Create/Update a User
	 * @param UserModel $json
	 * @return string Id of written object
	 */
	public function user_update($params) {
		return UserCommands::updateUser($params);
	}

	/**
	 * Create/Update a User Profile
	 * @param UserProfileModel $json
	 * @return string Id of written object
	 */
	public function user_updateProfile($params) {
		return UserCommands::updateUserProfile($params, $this->_userId);
	}

	/**
	 * Delete users
	 * @param array<string> $userIds
	 * @return int Count of deleted users
	 */
 	public function user_delete($userIds) {
 		return UserCommands::deleteUsers($userIds);
 	}

 	/**
 	 * @param string $userName
 	 * @param string $projectId
 	 * @return CreateSimpleDto
 	 */
 	public function user_createSimple($projectId, $userName) {
 		return UserCommands::createSimple($userName, $projectId, $this->_userId);
 	}
 	
 	// TODO Pretty sure this is going to want some paging params
	/**
	 * @return \models\UserListModel
	 */
	public function user_list() {
		return UserCommands::listUsers();
	}
	
	public function user_typeahead($term) {
		return UserCommands::userTypeaheadList($term);
	}
	
	public function change_password($userId, $newPassword) {
		return UserCommands::changePassword($userId, $newPassword, $this->_userId);
	}
	
	public function username_exists($username) {
		// intentionally we have no security here: people can see what users exist by trial and error
		return UserModel::userNameExists($username);
	}
	
	/**
	 * Register a new user with password
	 * @param UserModel $json
	 * @return string Id of written object
	 */
	public function user_register($params) {
		return UserCommands::register($params, $this->_controller->session->userdata('captcha_info'), $_SERVER['HTTP_HOST']);
	}
	
	public function user_create($params) {
		return UserCommands::createUser($params);
	}
	
	public function get_captcha_src() {
		return UserCommands::getCaptchaSrc($this->_controller);
	}
	
	public function user_readForRegistration($validationKey) {
		return UserCommands::readForRegistration($validationKey);
	}
	
	public function user_updateFromRegistration($validationKey, $params) {
		return UserCommands::updateFromRegistration($validationKey, $params);
	}
	
	public function user_sendInvite($projectId, $toEmail) {
		return UserCommands::sendInvite($this->_userId, $toEmail, $projectId, $_SERVER['HTTP_HOST']);
	}
	
	
	
	
	//---------------------------------------------------------------
	// GENERAL PROJECT API
	//---------------------------------------------------------------
	
	/**
	 * 
	 * @param string $projectName
	 * @param string $appName
	 * @return string - projectId
	 */
	public function project_create($projectName, $appName) {
		return ProjectCommands::createProject($projectName, $appName, $this->_userId, $this->_site);
	}

	/**
	 * Delete projects
	 * @param array<string> $projectIds
	 * @return int Count of deleted projects
	 */
 	public function project_delete($projectIds) {
 		return ProjectCommands::deleteProjects($projectIds);
 	}

	// TODO Pretty sure this is going to want some paging params
	public function project_list() {
		return ProjectCommands::listProjects();
	}
	
	public function project_list_dto() {
		return \models\shared\dto\ProjectListDto::encode($this->_userId, $this->_site);
	}
	
	public function project_updateUserRole($projectId, $params) {
		return ProjectCommands::updateUserRole($projectId, $params);
	}
	
	public function project_removeUsers($projectId, $userIds) {
		return ProjectCommands::removeUsers($projectId, $userIds);
	}
	
	
	//---------------------------------------------------------------
	// Activity Log
	//---------------------------------------------------------------

	public function activity_list_dto() {
		return \models\shared\dto\ActivityListDto::getActivityForUser($this->_site, $this->_userId);
	}
	
	
	
	/*
	 *  ____   ___  ____  __  ____  ____  _  _  ____  ____    ____  __  ____   ___  ____ 
	 * / ___) / __)(  _ \(  )(  _ \(_  _)/ )( \(  _ \(  __)  (  __)/  \(  _ \ / __)(  __)
	 * \___ \( (__  )   / )(  ) __/  )(  ) \/ ( )   / ) _)    ) _)(  O ))   /( (_ \ ) _) 
	 * (____/ \___)(__\_)(__)(__)   (__) \____/(__\_)(____)  (__)  \__/(__\_) \___/(____)
	 * 
	 */
	
	//---------------------------------------------------------------
	// SCRIPTUREFORGE PROJECT API
	//---------------------------------------------------------------

	/**
	 * Create/Update a Project
	 * @param array $object
	 * @return string Id of written object
	 */
	public function project_update($object) {
		return ProjectCommands::updateProject($object, $this->_userId);
	}

	/**
	 * Read a project from the given $id
	 * @param string $id
	 */
	public function project_read($id) {
		return ProjectCommands::readProject($id);
	}
	
	public function project_settings($projectId) {
		return ProjectSettingsDto::encode($projectId, $this->_userId);
	}
	
	public function project_updateSettings($projectId, $smsSettingsArray, $emailSettingsArray) {
		return ProjectCommands::updateProjectSettings($projectId, $smsSettingsArray, $emailSettingsArray);
	}
	
	public function project_readSettings($projectId) {
		return ProjectCommands::readProjectSettings($projectId);
	}
	
	public function project_pageDto($projectId) {
		return \models\scriptureforge\dto\ProjectPageDto::encode($projectId, $this->_userId);
	}

	
	//---------------------------------------------------------------
	// MESSAGE API
	//---------------------------------------------------------------
	public function message_markRead($projectId, $messageId) {
		return MessageCommands::markMessageRead($projectId, $messageId, $this->_userId);
	}
	
	public function message_send($projectId, $userIds, $subject, $emailTemplate, $smsTemplate) {
		return MessageCommands::sendMessage($projectId, $userIds, $subject, $emailTemplate, $smsTemplate);
	}
	
	
	//---------------------------------------------------------------
	// TEXT API
	//---------------------------------------------------------------
	
	public function text_update($projectId, $object) {
		return TextCommands::updateText($projectId, $object);
	}
	
	public function text_read($projectId, $textId) {
		return TextCommands::readText($projectId, $textId);
	}
	
	public function text_delete($projectId, $textIds) {
		return TextCommands::deleteTexts($projectId, $textIds);
	}
	
	public function text_list_dto($projectId) {
		return \models\scriptureforge\dto\TextListDto::encode($projectId, $this->_userId);
	}

	public function text_settings_dto($projectId, $textId) {
		return \models\scriptureforge\dto\TextSettingsDto::encode($projectId, $textId, $this->_userId);
	}
	
	public function text_exportComments($projectId, $params) {
		return ParatextExport::exportCommentsForText($projectId, $params['textId'], $params);
	}
	
	//---------------------------------------------------------------
	// Question / Answer / Comment API
	//---------------------------------------------------------------
	
	public function question_update($projectId, $object) {
		return QuestionCommands::updateQuestion($projectId, $object);
	}
	
	public function question_read($projectId, $questionId) {
		return QuestionCommands::readQuestion($projectId, $questionId);
	}
	
	public function question_delete($projectId, $questionIds) {
		return QuestionCommands::deleteQuestions($projectId, $questionIds);
	}
	
	public function question_update_answer($projectId, $questionId, $answer) {
		return QuestionCommands::updateAnswer($projectId, $questionId, $answer, $this->_userId);
	}
	
	public function question_remove_answer($projectId, $questionId, $answerId) {
		return QuestionCommands::removeAnswer($projectId, $questionId, $answerId);
	}
	
	public function question_update_comment($projectId, $questionId, $answerId, $comment) {
		return QuestionCommands::updateComment($projectId, $questionId, $answerId, $comment, $this->_userId);
	}
	
	public function question_remove_comment($projectId, $questionId, $answerId, $commentId) {
		return QuestionCommands::removeComment($projectId, $questionId, $answerId, $commentId);
	}
	
	public function question_comment_dto($projectId, $questionId) {
		return \models\scriptureforge\dto\QuestionCommentDto::encode($projectId, $questionId, $this->_userId);
	}
	
	public function question_list_dto($projectId, $textId) {
		return \models\scriptureforge\dto\QuestionListDto::encode($projectId, $textId, $this->_userId);
	}
	
	public function answer_vote_up($projectId, $questionId, $answerId) {
		return QuestionCommands::voteUp($this->_userId, $projectId, $questionId, $answerId);
	}
	
	public function answer_vote_down($projectId, $questionId, $answerId) {
		return QuestionCommands::voteDown($this->_userId, $projectId, $questionId, $answerId);
	}

	//---------------------------------------------------------------
	// QuestionTemplates API
	//---------------------------------------------------------------

	public function questionTemplate_update($projectId, $model) {
		return QuestionTemplateCommands::updateTemplate($model);
	}

	public function questionTemplate_read($id) {
		return QuestionTemplateCommands::readTemplate($id);
	}

	public function questionTemplate_delete($questionTemplateIds) {
		return QuestionTemplateCommands::deleteQuestionTemplates($questionTemplateIds);
	}

	public function questionTemplate_list() {
		return QuestionTemplateCommands::listTemplates();
	}
	
	
	
	
	
	/*
	 * .____                                                        ___________                         
	 * |    |   _____    ____    ____  __ _______     ____   ____   \_   _____/__________  ____   ____  
	 * |    |   \__  \  /    \  / ___\|  |  \__  \   / ___\_/ __ \   |    __)/  _ \_  __ \/ ___\_/ __ \ 
	 * |    |___ / __ \|   |  \/ /_/  >  |  // __ \_/ /_/  >  ___/   |     \(  <_> )  | \/ /_/  >  ___/ 
	 * |_______ (____  /___|  /\___  /|____/(____  /\___  / \___  >  \___  / \____/|__|  \___  / \___  >
	 *         \/    \/     \//_____/            \//_____/      \/       \/             /_____/      \/ 
	 * 
	 */
	
	//---------------------------------------------------------------
	// LANGUAGEFORGE PROJECT API
	//---------------------------------------------------------------
	
	public function lex_baseViewDto($projectId) {
		return LexBaseViewDto::encode($projectId, $this->_userId);
	}
	
	public function lex_projectDto($projectId) {
		return LexProjectDto::encode($projectId, $this->_userId);
	}

	public function lex_manageUsersDto($projectId) {
		return LexManageUsersDto::encode($projectId, $this->_userId);
	}

	public function lex_dbeDto($projectId, $iEntryStart, $numberOfEntries) {
		return LexDbeDto::encode($projectId, $this->_userId, $iEntryStart, $numberOfEntries);
	}
	
	public function lex_configuration_update($projectId, $config) {
		return LexProjectCommands::updateConfig($projectId, $config);
	}
	
	public function lex_import_lift($projectId, $import) {
		return LexProjectCommands::importLift($projectId, $import);
	}
	
	public function lex_project_update($projectJson) {
		return LexProjectCommands::updateProject($projectJson, $this->_userId);
	}
	
	public function lex_entry_read($projectId, $entryId) {
		return LexEntryCommands::readEntry($projectId, $entryId);
	}
	
	public function lex_entry_update($projectId, $model) {
		return LexEntryCommands::updateEntry($projectId, $model, $this->_userId);
	}
	
	public function lex_entry_remove($projectId, $entryId) {
		return LexEntryCommands::removeEntry($projectId, $entryId);
	}
	
	public function lex_entry_updateComment($projectId, $data) {
		return LexCommentCommands::updateCommentOrReply($projectId, $data, $this->_userId);
	}
	
	
		
	
	
	
	
	
	
	
	
	//---------------------------------------------------------------
	// Private Utility Functions
	//---------------------------------------------------------------

	private static function isAnonymousMethod($methodName) {
		$methods = array(
				'username_exists',
				'user_register',
				'get_captcha_src',
				'user_readForRegistration',
				'user_updateFromRegistration'
		);
		return in_array($methodName, $methods);
	}
	
	public function checkPermissions($methodName, $params) {

		if (!self::isAnonymousMethod($methodName)) {
			if (!$this->_userId) {
				throw new UserNotAuthenticatedException("Your session has timed out.  Please login again.");
			}
			if (!RightsHelper::userCanAccessMethod($this->_userId, $methodName, $params)) {
				throw new UserUnauthorizedException("Insufficient privileges accessing API method '$methodName'");
			}
		}
	}

	public function update_last_activity($newtime = NULL) {
		if (is_null($newtime)) {
			// Default to current time
			$newtime = time();
		}
		$this->_controller->session->set_userdata('last_activity', $newtime);
	}
	
}

?>
