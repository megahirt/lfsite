<?php

namespace libraries\shared\scripts\migration;

use libraries\languageforge\semdomtrans\SemDomXMLImporter;
use models\languageforge\SemDomTransProjectModel;
use models\ProjectModel;

class ImportEnglishSemDomProject
{
    public function run($userId, $mode = 'test')
    {
        $testMode = ($mode != 'run');
        $message = "Import English Semantic Domain Project\n\n";
        $projectCode = SemDomTransProjectModel::projectCode('en');
        $englishXmlFilePath = APPPATH . 'resources/languageforge/semdomtrans/SemDom_en.xml';

        $englishProject = new SemDomTransProjectModel();
        $englishProject->readByCode('en');

        if ($englishProject->id->asString() != "") {
            $message .= "Note: English Project already exists.\n";
            $message .= "Deleting English Project...\n";
            $message .= "If other semdomtrans projects already exist their source project reference id will be messed up!...\n";
            if (!$testMode) {
                $englishProject->remove();
            }
        }

        $projectModel = new SemDomTransProjectModel();
        $projectModel->projectCode = $projectCode;
        $projectModel->projectName = "English (en) Semantic Domain Project";
        $projectModel->languageIsoCode = 'en';
        $projectModel->isSourceLanguage = true;
        $projectModel->semdomVersion = SemDomTransProjectModel::SEMDOM_VERSION;
        $projectModel->ownerRef->id = $userId;

        if (!$testMode) {
            $projectModel->importFromFile($englishXmlFilePath, true);
        }
        $message .= "Import complete!\n";

        return $message;
    }
}
