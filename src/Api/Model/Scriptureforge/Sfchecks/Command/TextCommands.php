<?php

namespace Api\Model\Scriptureforge\Sfchecks\Command;

use Api\Model\Scriptureforge\Sfchecks\Dto\UsxTrimHelper;
use Api\Model\Scriptureforge\Sfchecks\TextModel;
use Api\Model\Shared\Command\ActivityCommands;
use Api\Model\Shared\Command\ProjectCommands;
use Api\Model\Shared\Mapper\JsonDecoder;
use Api\Model\Shared\Mapper\JsonEncoder;
use Api\Model\Shared\ProjectModel;

class TextCommands
{
    private static function makeValidRange(&$object)
    {
        if (isset($object['startCh'])) {
            $sc = (int) $object['startCh'];
        } else {
            $sc = 0;
        }
        $object['startCh'] = $sc;
        if (isset($object['startVs'])) {
            $sv = (int) $object['startVs'];
        } else {
            $sv = 0;
        }
        $object['startVs'] = $sv;
        if (isset($object['endCh'])) {
            $ec = (int) $object['endCh'];
        } else {
            $ec = 0;
        }
        $object['endCh'] = $ec;
        if (isset($object['endVs'])) {
            $ev = (int) $object['endVs'];
        } else {
            $ev = 0;
        }
        $object['endVs'] = $ev;
    }

    private static function hasRange($object)
    {
        return ((int) $object['startCh'] || (int) $object['startVs'] || (int) $object['endCh'] || (int) $object['endVs']);
    }

	/**
	 * @param string $projectId
	 * @param array $object (json encoded)
	 * @param $userId
	 *
	 * @return string Id of text updated/added
	 * @throws \Api\Library\Shared\Palaso\Exception\ResourceNotAvailableException
	 */
    public static function updateText($projectId, $object, $userId)
    {
        $projectModel = new ProjectModel($projectId);
        ProjectCommands::checkIfArchivedAndThrow($projectModel);
        $textId = $object['id'] ?? '';
        $isNewText = ($textId == '');
        $textModel = new TextModel($projectModel, $textId);
        JsonDecoder::decode($textModel, $object);
        TextCommands::makeValidRange($object);
        if (TextCommands::hasRange($object)) {
            $usxTrimHelper = new UsxTrimHelper(
                $textModel->content,
                $object['startCh'],
                $object['startVs'],
                $object['endCh'],
                $object['endVs']
            );
            $textModel->content = $usxTrimHelper->trimUsx();
        }
        $textId = $textModel->write();
        if ($isNewText) {
            ActivityCommands::addText($projectModel, $textId, $textModel, $userId);
        }

        return $textId;
    }

    /**
     * @param string $projectId
     * @param string $textId
     * @return array
     */
    public static function readText($projectId, $textId)
    {
        $projectModel = new ProjectModel($projectId);
        $textModel = new TextModel($projectModel, $textId);

        return JsonEncoder::encode($textModel);
    }

    /**
     * @param string $projectId
     * @param array $textIds
     * @return int Total number of texts archived.
     */
    public static function archiveTexts($projectId, $textIds)
    {
        $project = new ProjectModel($projectId);
        $count = 0;
        foreach ($textIds as $textId) {
            $text = new TextModel($project, $textId);
            $text->isArchived = true;
            $text->write();
            $count++;
        }

        return $count;
    }

    /**
     * @param string $projectId
     * @param array $textIds
     * @return int Total number of texts published.
     */
    public static function publishTexts($projectId, $textIds)
    {
        $project = new ProjectModel($projectId);
        $count = 0;
        foreach ($textIds as $textId) {
            $text = new TextModel($project, $textId);
            $text->isArchived = false;
            $text->write();
            $count++;
        }

        return $count;
    }

    /**
     * @param string $projectId
     * @param array $textIds
     * @return int Total number of texts removed.
     */
    public static function deleteTexts($projectId, $textIds)
    {
        $projectModel = new ProjectModel($projectId);
        $count = 0;
        foreach ($textIds as $textId) {
            TextModel::remove($projectModel->databaseName(), $textId);
            $count++;
        }

        return $count;
    }
}
