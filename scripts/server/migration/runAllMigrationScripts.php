<?php

require_once('../../scriptsConfig.php');

(php_sapi_name() == 'cli') or die('this script must be run on the command-line');

class RunAllMigrationScripts {

    public static function run() {
        $user = new \Api\Model\UserModel();
        $user->readByUserName('chris');
        $userId = $user->id->asString();
        $scriptNames = array("FixAvatarRefs", "FixEnvironmentReversalEntriesFieldOrder", "FixLexViewSettings",
            "FixProjectSiteNameIntegrity", "FixSiteRolesIntegrity", "ImportEnglishSemDomProject");

        foreach ($scriptNames as $scriptName) {
            print("\n\n\n***RUNNING $scriptName\n");
            $className = "\\Api\\Library\\Shared\\Script\\Migration\\$scriptName";
            $class = new $className;
            print($class->run($userId, 'run'));

        }
        print("Skipping ImportOtherLanguageSemDomProjects\n");

        $ctr = 1;
        while ($ctr < 6) {
            print("\n\n\n***Running FixSemanticDomainKey\n");
            $class = new \Api\Library\Shared\Script\Migration\FixSemanticDomainKey;
            print $class->run($userId, 'run');
            $ctr++;
        }
    }
}

RunAllMigrationScripts::run();