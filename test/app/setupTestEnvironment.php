<?php

require_once('e2eTestConfig.php');

// put the test config into place
system(TestPath . '/app/useTestConfig.sh');

// use commands go here (after the e2eTestConfig)
use models\commands\ProjectCommands;
use models\commands\UserCommands;
use models\commands\TextCommands;
use models\commands\QuestionCommands;
use models\shared\rights\ProjectRoles;
use models\shared\rights\SiteRoles;
use models\scriptureforge\SfProjectModel;
use models\ProjectModel;
use libraries\shared\Website;

// Fake some $_SERVER variables like HTTP_HOST for the sake of the code that needs it
$_SERVER['HTTP_HOST'] = 'scriptureforge.local'; // TODO: Consider parsing protractorConf.js and loading baseUrl from it

// start with a fresh database
$db = \models\mapper\MongoStore::connect(SF_DATABASE);
foreach ($db->listCollections() as $collection) { $collection->drop(); }

$constants = json_decode(file_get_contents(TestPath . '/testConstants.json'), true);

// Also empty out databases for the test projects
$projectNames = array($constants['testProjectName'], $constants['otherProjectName']);
foreach ($projectNames as $name) {
	$projectModel = new ProjectModel();
	$projectModel->projectname = $name;
	$db = \models\mapper\MongoStore::connect($projectModel->databaseName());
	foreach ($db->listCollections() as $collection) { $collection->drop(); }
}

// drop the third database because it is used in a rename test
$projectModel = new ProjectModel();
$projectModel->projectname = $constants['thirdProjectName'];
$db = \models\mapper\MongoStore::dropDB($projectModel->databaseName());

$adminUser = UserCommands::createUser(array(
	'id' => '',
	'name' => $constants['adminName'],
	'email' => $constants['adminEmail'],
	'username' => $constants['adminUsername'],
	'password' => $constants['adminPassword'],
	'active' => true,
	'role' => SiteRoles::SYSTEM_ADMIN
));
$managerUser = UserCommands::createUser(array(
	'id' => '',
	'name' => $constants['managerName'],
	'email' => $constants['managerEmail'],
	'username' => $constants['managerUsername'],
	'password' => $constants['managerPassword'],
	'active' => true,
	'role' => SiteRoles::USER // Should this be Roles::PROJECT_ADMIN? I think not; I think that's set per-project. 2014-05 RM
));
$memberUser = UserCommands::createUser(array(
	'id' => '',
	'name' => $constants['memberName'],
	'email' => $constants['memberEmail'],
	'username' => $constants['memberUsername'],
	'password' => $constants['memberPassword'],
	'active' => true,
	'role' => SiteRoles::USER
));

$testProject = ProjectCommands::createProject(
	$constants['testProjectName'],
	SfProjectModel::SFCHECKS_APP, // TODO: Find out if there's a better constant for this. 2014-05 RM
	$adminUser,
	Website::SCRIPTUREFORGE
);
$testProjectModel = new ProjectModel($testProject);
$testProjectModel->projectCode = $constants['testProjectCode'];
$testProjectModel->write();

$otherProject = ProjectCommands::createProject(
	$constants['otherProjectName'],
	SfProjectModel::SFCHECKS_APP, // TODO: Find out if there's a better constant for this. 2014-05 RM
	$adminUser,
	Website::SCRIPTUREFORGE
);

ProjectCommands::updateUserRole($testProject, array(
	'id' => $managerUser,
	'role' => ProjectRoles::MANAGER
));
ProjectCommands::updateUserRole($testProject, array(
	'id' => $memberUser,
	'role' => ProjectRoles::CONTRIBUTOR
));

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

?>
