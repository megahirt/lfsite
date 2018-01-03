<?php

namespace Api\Model\Languageforge\Translate\Dto;

use Api\Model\Languageforge\Translate\TranslateProjectModel;
use Api\Model\Shared\Mapper\JsonEncoder;
use Api\Model\Shared\UserModel;

class TranslateProjectDtoEncoder extends JsonEncoder
{
    public function encodeIdReference(&$key, $model)
    {
        if ($key == 'ownerRef') {
            $user = new UserModel();
            if ($user->readIfExists($model->asString())) {
                return [
                    'id' => $user->id->asString(),
                    'username' => $user->username
                ];
            } else {
                return '';
            }
        } else {
            return $model->asString();
        }
    }

    public static function encode($model): array
    {
        $encoder = new TranslateProjectDtoEncoder();
        $data = $encoder->_encode($model);
        if (method_exists($model, 'getPrivateProperties')) {
            $privateProperties = (array) $model->getPrivateProperties();
            foreach ($privateProperties as $prop) {
                unset($data[$prop]);
            }
        }

        return $data;
    }
}

class TranslateProjectDto
{
    /**
     * @param string $projectId
     * @param string $userId
     * @returns array - the DTO array
     */
    public static function encode($projectId, $userId)
    {
        $project = new TranslateProjectModel($projectId);
        $projectDto = TranslateProjectDtoEncoder::encode($project);
        if (array_key_exists($userId, $projectDto['config']['usersPreferences'])) {
            $projectDto['config']['userPreferences'] = $projectDto['config']['usersPreferences'][$userId];
        }
        unset($projectDto['config']['usersPreferences']);

        $data = [];
        $data['project'] = [];
        $data['project']['interfaceLanguageCode'] = $projectDto['interfaceLanguageCode'];
        $data['project']['ownerRef'] = $projectDto['ownerRef'];
        $data['project']['projectCode'] = $projectDto['projectCode'];
        $data['project']['featured'] = $projectDto['featured'];
        $data['project']['config'] = $projectDto['config'];

        return $data;
    }
}
