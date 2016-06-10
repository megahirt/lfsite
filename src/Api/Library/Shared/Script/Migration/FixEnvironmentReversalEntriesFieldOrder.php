<?php

namespace Api\Library\Shared\Script\Migration;

use Api\Model\Languageforge\Lexicon\LexiconProjectModel;
use Api\Model\Mapper\ArrayOf;
use Api\Model\ProjectListModel;
use Api\Model\ProjectModel;

class FixEnvironmentReversalEntriesFieldOrder
{
    public function run($userId, $mode = 'test')
    {
        $testMode = ($mode != 'run');
        $message = "Remove Environment and Reversal Entries from field order config\n\n";

        // Note: LF projects that don't have config data in mongo will use the default PHP model.
        // In these cases, the migration script won't find these field orders to remove, so nothing gets written out

        $projectlist = new ProjectListModel();
        $projectlist->read();

        foreach ($projectlist->entries as $projectParams) { // foreach existing project
            $projectId = $projectParams['id'];
            $project = new ProjectModel($projectId);
            if ($project->appName == 'lexicon') {
                $project = new LexiconProjectModel($projectId);
                $fieldOrderUpdated = 0;
                $message .= "Inspecting project $project->projectName.\n";

                $this->RemoveFromArray("environments", $project->config->entry->fieldOrder, $message, $fieldOrderUpdated);

                $fieldsArray = $project->config->entry->fields->getArrayCopy();
                if (array_key_exists("senses", $fieldsArray)) {
                    $this->RemoveFromArray("reversalEntries", $fieldsArray["senses"]->fieldOrder, $message, $fieldOrderUpdated);
                    $project->config->entry->fields->exchangeArray($fieldsArray);
                }

                if ($fieldOrderUpdated > 0) {
                    $message .= "\tRemoved: $fieldOrderUpdated field orders\n";

                    if (!$testMode) {
                        $message .= "\tSaving changes to project $project->projectName.\n\n";
                        $project->write();
                    }
                } else {
                    $message .= "\tNo fieldOrders to remove\n";
                }
            }
            unset($project);
        }

        return $message;
    }

    /**
     * RemoveFromArray
     *
     * Remove a specific string from the field order if it exists.  The field order array is replaced at the
     * end of this function
     *
     * @param string $fieldOrderToRemove - string to remove
     * @param ArrayOf &$fieldOrder - field order config
     * @param string &$message - report of migration script
     * @param int &$fieldOrderUpdated - counter of the number of field orders updated
     */
    private function RemoveFromArray($fieldOrderToRemove, &$fieldOrder, &$message, &$fieldOrderUpdated) {
        $fieldOrderArray = $fieldOrder->getArrayCopy();
        $key = array_search($fieldOrderToRemove, $fieldOrderArray);
        if (($key != null) && ($key != false)) {
            //$message .= "\tRemoving field order \"$fieldOrderToRemove\"\n";
            unset($fieldOrderArray[$key]);
            $fieldOrder->exchangeArray(array_values($fieldOrderArray));
            $fieldOrderUpdated++;
        }
    }
}
