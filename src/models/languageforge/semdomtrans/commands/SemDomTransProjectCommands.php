<?php

namespace models\languageforge\semdomtrans\commands;

use libraries\shared\Website;

use Palaso\Utilities\CodeGuard;
use libraries\scriptureforge\sfchecks\Email;
use models\ProjectModel;
use models\ProjectSettingsModel;
use models\UserModel;
use models\shared\dto\ManageUsersDto;
use models\mapper\Id;
use models\mapper\JsonDecoder;
use models\mapper\JsonEncoder;
use models\shared\rights\Domain;
use models\languageforge\semdomtrans\SemDomTransItemListModel;
use models\shared\rights\ProjectRoles;
use models\sms\SmsSettings;
use models\languageforge\semdomtrans\SemDomTransItemModel;
use models\languageforge\SemDomTransProjectModel;
use models\languageforge\semdomtrans\SemDomTransTranslatedForm;
use models\ProjectListModel;
use models\languageforge\LfProjectModel;
use models\commands\ProjectCommands;
use models\languageforge\semdomtrans\SemDomTransQuestion;
use Palaso\Utilities\FileUtilities;
use libraries\languageforge\semdomtrans\SemDomXMLExporter;

class SemDomTransProjectCommands
{
    /**
     * Gets list of currently open projects for a user (excluding those he is part of are has submitted a join request for)
     * @param string $userId
     * @return multitype:\models\languageforge\SemDomTransProjectModel
     */
    public static function getOpenSemdomProjects($userId) {
        $projects = new ProjectListModel();
        $projects->read();
        $semdomProjects = [];
        foreach($projects->entries as $p) {
            $project = new ProjectModel($p["id"]);
            if ($project->appName == LfProjectModel::SEMDOMTRANS_APP 
                 && !array_key_exists($userId, $project->users)
                 && !array_key_exists($userId, $project->userJoinRequests)) {
                     
                $sp = new SemDomTransProjectModel($p["id"]);
                if ($sp->languageIsoCode != "en") {
                        $semdomProjects[] = $sp;
                }
            }
        }

        return $semdomProjects;
    }
   
    /**
     * exports all projects to a zip file - currently not working
     */
    public static function exportProjects() {
        $zip = new ZipArchive();
        $filename =  $path = APPPATH . "resources/languageforge/semdomtrans/GoogleTranslateHarvester/exportedProjects.zip";
        
        if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
            exit("cannot open <$filename>\n");
        }
        else {
            $projects = SemDomTransProjectCommands::getOpenSemdomProjects($userId);
            foreach($projects->entries as $p) {
                $e = new SemDomXMLExporter($p, false, ($p->isSourceLanguage && $p->languageIsoCode != 'en'), !$p->isSourceLanguage);
                $e->run();
                $zip->addFile($p->$xmlFilePath , basename($p->$xmlFilePath));
            }
            $zip->close();
        }
    }
    
    /**
     * Checks whether the language code has an associated google translate file that can be used
     * for prepopulating translation
     * @param string $languageIsoCode
     * @return boolean
     */
    public static function doesGoogleTranslateDataExist($languageIsoCode) {
        $path = APPPATH . "resources/languageforge/semdomtrans/GoogleTranslateHarvester/semdom-google-translate-$languageIsoCode.txt.gz";
        return file_exists($path);
    }
    
    /**
     * Determines if project with given langauge code exists
     * @param string $languageCode
     * @return boolean
     */
    public static function checkProjectExists($languageCode) {
        $project = new SemDomTransProjectModel();
        $project->readByCode($languageCode);
        if (Id::isEmpty($project->id)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Creates a semdomtrans project and prefills it (using Google Translate data if appropriate flag is set)
     * @param string $languageCode
     * @param string $languageName
     * @param bool $useGoogleTranslateData
     * @param string $userId
     * @param Website $website
     * @param int $semdomVersion
     * @return string
     */
    public static function createProject($languageCode, $languageName, $useGoogleTranslateData, $userId, $website, $semdomVersion = SemDomTransProjectModel::SEMDOM_VERSION) {

        $projectCode = SemDomTransProjectModel::projectCode($languageCode, $semdomVersion);
        $projectName = SemDomTransProjectModel::projectName($languageCode, $languageName, $semdomVersion);
        $projectID =  ProjectCommands::createProject($projectName, $projectCode, LfProjectModel::SEMDOMTRANS_APP, $userId, $website);

        $project = new SemDomTransProjectModel($projectID);
        $project->languageIsoCode = $languageCode;
        $project->isSourceLanguage = false;
        $project->semdomVersion = $semdomVersion;

        // by default all created projects have English as their source.  A future feature would allow creating projects off of other source languages
        $englishProject = SemDomTransProjectModel::getEnglishProject($semdomVersion);
        $project->sourceLanguageProjectId->id = $englishProject->id->asString();
        
        // prefill project with semdom items
        $project->preFillFromSourceLanguage($useGoogleTranslateData);
        return $project->write();
    }

}
