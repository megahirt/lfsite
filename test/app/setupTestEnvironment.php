<?php

require_once('e2eTestConfig.php');

// put the test config into place
system(TestPath . '/useTestConfig.sh');

// use commands go here (after the e2eTestConfig)
use models\commands\ProjectCommands;
use models\commands\UserCommands;
use models\commands\TextCommands;
use models\commands\QuestionCommands;
use models\commands\QuestionTemplateCommands;
use models\shared\rights\ProjectRoles;
use models\shared\rights\SiteRoles;
use models\shared\rights\SystemRoles;
use models\scriptureforge\SfProjectModel;
use models\languageforge\LfProjectModel;
use models\ProjectModel;
use models\languageforge\lexicon\LexiconProjectModel;
use models\languageforge\lexicon\commands\LexEntryCommands;
use models\languageforge\lexicon\config\LexiconConfigObj;
use libraries\shared\Website;

$constants = json_decode(file_get_contents(TestPath . '/testConstants.json'), true);

// Fake some $_SERVER variables like HTTP_HOST for the sake of the code that needs it
$_SERVER['HTTP_HOST'] = $constants['siteHostname'];
$website = Website::get($constants['siteHostname']);

// start with a fresh database
$db = \models\mapper\MongoStore::connect(SF_DATABASE);
foreach ($db->listCollections() as $collection) { $collection->drop(); }

// Also empty out databases for the test projects
$projectArrays = array(
	$constants['testProjectName']  => $constants['testProjectCode'], 
	$constants['otherProjectName'] => $constants['otherProjectCode']);

foreach ($projectArrays as $projectName => $projectCode) {
	$projectModel = new ProjectModel();
	$projectModel->projectName = $projectName;
	$projectModel->projectCode = $projectCode;
	$db = \models\mapper\MongoStore::connect($projectModel->databaseName());
	foreach ($db->listCollections() as $collection) { $collection->drop(); }
}

// drop the third database because it is used in a rename test
$projectModel = new ProjectModel();
$projectModel->projectName = $constants['thirdProjectName'];
$projectModel->projectCode = $constants['thirdProjectCode'];
$db = \models\mapper\MongoStore::dropDB($projectModel->databaseName());

$adminUser = UserCommands::createUser(array(
	'id' => '',
	'name' => $constants['adminName'],
	'email' => $constants['adminEmail'],
	'username' => $constants['adminUsername'],
	'password' => $constants['adminPassword'],
	'active' => true,
	'role' => SystemRoles::SYSTEM_ADMIN),
	$website
);
$managerUser = UserCommands::createUser(array(
	'id' => '',
	'name' => $constants['managerName'],
	'email' => $constants['managerEmail'],
	'username' => $constants['managerUsername'],
	'password' => $constants['managerPassword'],
	'active' => true,
	'role' => SystemRoles::USER),
	$website
);
$memberUser = UserCommands::createUser(array(
	'id' => '',
	'name' => $constants['memberName'],
	'email' => $constants['memberEmail'],
	'username' => $constants['memberUsername'],
	'password' => $constants['memberPassword'],
	'active' => true,
	'role' => SystemRoles::USER),
	$website
);

if ($constants['siteType'] == 'scriptureforge') {
	$projectType = SfProjectModel::SFCHECKS_APP;
} else if ($constants['siteType'] == 'languageforge') {
	$projectType = LfProjectModel::LEXICON_APP;
}
$testProject = ProjectCommands::createProject(
	$constants['testProjectName'],
	$constants['testProjectCode'],
	$projectType,
	$adminUser,
	$website
);
$testProjectModel = new ProjectModel($testProject);
$testProjectModel->projectCode = $constants['testProjectCode'];
$testProjectModel->allowInviteAFriend = $constants['testProjectAllowInvites'];
$testProjectModel->write();

$otherProject = ProjectCommands::createProject(
	$constants['otherProjectName'],
	$constants['otherProjectCode'],
	$projectType,
	$managerUser,
	$website
);
$otherProjectModel = new ProjectModel($otherProject);
$otherProjectModel->projectCode = $constants['otherProjectCode'];
$otherProjectModel->allowInviteAFriend = $constants['otherProjectAllowInvites'];
$otherProjectModel->write();

ProjectCommands::updateUserRole($testProject, $managerUser, ProjectRoles::MANAGER);
ProjectCommands::updateUserRole($testProject, $memberUser, ProjectRoles::CONTRIBUTOR);
ProjectCommands::updateUserRole($otherProject, $adminUser, ProjectRoles::MANAGER);

if ($constants['siteType'] == 'scriptureforge') {
	$text1 = TextCommands::updateText($testProject, array(
		'id' => '',
		'title' => $constants['testText1Title'],
		'content' => $constants['testText1Content']
	));
	$text2 = TextCommands::updateText($testProject, array(
		'id' => '',
		'title' => $constants['testText2Title'],
		'content' => $constants['testText2Content']
	));

	$question1 = QuestionCommands::updateQuestion($testProject, array(
		'id' => '',
		'textRef' => $text1,
		'title' => $constants['testText1Question1Title'],
		'description' => $constants['testText1Question1Content']
	));
	$question2 = QuestionCommands::updateQuestion($testProject, array(
		'id' => '',
		'textRef' => $text1,
		'title' => $constants['testText1Question2Title'],
		'description' => $constants['testText1Question2Content']
	));

	$template1 = QuestionTemplateCommands::updateTemplate($testProject, array(
		'id' => '',
		'title' => 'first template',
		'description' => 'not particularly interesting'
			));

	$template2 = QuestionTemplateCommands::updateTemplate($testProject, array(
		'id' => '',
		'title' => 'second template',
		'description' => 'not entirely interesting'
			));

	$answer1 = QuestionCommands::updateAnswer($testProject, $question1, array(
		'id' => '', 
		'content' => $constants['testText1Question1Answer']),
		$managerUser);
	$answer1Id = array_keys($answer1)[0];
	$answer2 = QuestionCommands::updateAnswer($testProject, $question2, array(
		'id' => '', 
		'content' => $constants['testText1Question2Answer']),
		$managerUser);
	$answer2Id = array_keys($answer2)[0];

	$comment1 = QuestionCommands::updateComment($testProject, $question1, $answer1Id, array(
		'id' => '', 
		'content' => $constants['testText1Question1Answer1Comment']),
		$managerUser);
	$comment2 = QuestionCommands::updateComment($testProject, $question2, $answer2Id, array(
		'id' => '', 
		'content' => $constants['testText1Question2Answer2Comment']),
		$managerUser);
} else if ($constants['siteType'] == 'languageforge') {
	// Set up LanguageForge E2E test envrionment here
    $testProjectModel = new LexiconProjectModel($testProject);
    $testProjectModel->addInputSystem("th-fonipa", "thipa", "Thai IPA");
    $testProjectModel->config->entry->fields[LexiconConfigObj::LEXEME]->inputSystems[] = 'th-fonipa';
    $testProjectModel->write();

	$entry1 = LexEntryCommands::updateEntry($testProject,
		array(
			'id' => '',
			'lexeme' => $constants['testEntry1']['lexeme'],
			'senses' => $constants['testEntry1']['senses']
		), $managerUser);
	$entry2 = LexEntryCommands::updateEntry($testProject,
		array(
			'id' => '',
			'lexeme' => $constants['testEntry2']['lexeme'],
			'senses' => $constants['testEntry2']['senses']
		), $managerUser);
}

?>
