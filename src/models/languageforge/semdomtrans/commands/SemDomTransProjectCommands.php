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

class SemDomTransProjectCommands
{
    
    public static function createProject($languageIsoCode) {
        $version = SemDomTransProjectModel::SEMDOMVERSION;
        $projectCode = "semdom-$languageIsoCode-$version";
        $projectID = ProjectCommands::createProject($projectName, $projectCode, LfProjectModel::SEMDOMTRANS_APP, $this->_userId, $this->_website);
        
        $project = new SemDomTransProjectModel($projectID);
        $project->languageIsoCode = $languageIsoCode;
        $project->semdomVersion = $version;
        $project->write();
        
        SemDomTransProjectCommands::preFillProject($projectID);
        
        return $project->id;
    }
    
    public static function getOpenSemdomProjects($userId) {
        $projects = new ProjectListModel();
        $projects->read();
        $semdomProjects = [];
        foreach($projects->entries as $p) {
            $project = new ProjectModel($p["id"]);
            if ($project->appName == LfProjectModel::SEMDOMTRANS_APP
                && !array_key_exists($userId, $project->users)
                && !array_key_exists($userId, $project->userJoinRequests))
                { 
                    $sp = new SemDomTransProjectModel($p["id"]);
                    if ($sp->languageIsoCode != "en") {
                        $semdomProjects[] = $sp;
                }
            }
        }

        return $semdomProjects;
    }
    
    
    public static function preFillProject($projectId, $version = SemDomTransProjectModel::SEMDOMVERSION) {
        $projectModel = new SemDomTransProjectModel($projectId);
        $englishProject = new SemDomTransProjectModel();
        $englishProject->projectCode = "semdom-en-$version";
        $englishProject->readByProperty("projectCode", $englishProject->projectCode); 

        $projectModel->sourceLanguageProjectId = $englishProject->id->asString();
        $projectModel->write();

        $xmlFilePath = $englishProject->xmlFilePath;
        $newXmlFilePath = $projectModel->getAssetsFolderPath() . '/' . basename($xmlFilePath);
        FileUtilities::createAllFolders($projectModel->getAssetsFolderPath());
        copy($xmlFilePath, $newXmlFilePath);
        $projectModel->xmlFilePath = $newXmlFilePath;
        $projectModel->write();

        $englishItems = new SemDomTransItemListModel($englishProject);
        $englishItems->read();
        foreach ($englishItems->entries as $item) {
            $newItem = new SemDomTransItemModel($projectModel);
            $newItem->key = $item['key'];
            foreach ($item['questions'] as $q) {
                $newq = new SemDomTransQuestion();
                $newItem->questions[] = $newq;
            }
            foreach ($item['searchKeys'] as $sk) {
                $newsk = new SemDomTransTranslatedForm();
                $newItem->searchKeys[] = $newsk;
            }
            $newItem->xmlGuid = $item['xmlGuid'];
            $newItem->write();
        }

        return $projectModel;
    }

    public static function checkProjectExists($languageCode, $semdomVersion) {
        $project = new SemDomTransProjectModel();
        $project->readByProperties(array("languageIsoCode" => $languageCode, "semdomVersion" => $semdomVersion));
        if (Id::isEmpty($project->id)) {
            return true;
        } else {
            return false;
        }
    }
}
