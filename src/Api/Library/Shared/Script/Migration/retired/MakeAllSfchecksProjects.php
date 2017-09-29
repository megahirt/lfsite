<?php

namespace Api\Library\Shared\Script\Migration;

use Api\Model\Scriptureforge\Sfchecks\SfchecksProjectModel;
use Api\Model\Shared\ProjectListModel;
use Api\Model\Shared\ProjectModel;

class MakeAllSfchecksProjects
{
    public function run($mode = 'test')
    {
        $testMode = ($mode == 'test');
        $message = "";

        $projectlist = new ProjectListModel();
        $projectlist->read();
        $projectIds = array_map(function ($e) { return $e['id'];}, $projectlist->entries);

        $projectsChanged = 0;
        foreach ($projectlist->entries as $projectParams) { // foreach existing project
            $projectId = $projectParams['id'];
            $project = new ProjectModel($projectId);
            if ($project->siteName == '') {
                $sfproject = new SfchecksProjectModel($projectId);
                $sfproject->themeName = "jamaicanpsalms";
                $message .= "Changed the " . $sfproject->projectName . " to be a sfchecks/scriptureforge project with jamaican psalms theme\n";
                if (!$testMode) {
                    $sfproject->write();
                }
                $projectsChanged++;
            }
        }
        if ($projectsChanged > 0) {
            $message .= "\n\nChanged $projectsChanged projects to be of sfchecks type under scriptureforge and jamaicanpsalms theme \n\n";
        } else {
            $message .= "\n\nNo projects were found/changed \n\n";
        }

        return $message;
    }
}
