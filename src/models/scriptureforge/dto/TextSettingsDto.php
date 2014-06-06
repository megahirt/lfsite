<?php

namespace models\scriptureforge\dto;

use models\scriptureforge\SfchecksProjectModel;

use models\shared\dto\RightsHelper;

use models\UserModel;
use models\TextModel;
use models\ProjectModel;
use models\mapper\JsonEncoder;

class TextSettingsDto
{
	/**
	 * @param string $projectId
	 * @param string $userId
	 * @returns array - the DTO array
	 */
	public static function encode($projectId, $textId, $userId) {
		$userModel = new UserModel($userId);
		$projectModel = new SfchecksProjectModel($projectId);
		$textModel = new TextModel($projectModel, $textId);

		$list = $projectModel->listUsers();
		$data = array();
		$data['text'] = JsonEncoder::encode($textModel);
		$data['rights'] = RightsHelper::encode($userModel, $projectModel);
		$data['bcs'] = BreadCrumbHelper::encode('settings', $projectModel, $textModel, null);
		return $data;
	}
}

?>
