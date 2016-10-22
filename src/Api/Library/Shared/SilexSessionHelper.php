<?php

namespace Api\Library\Shared;

use Api\Model\Shared\UserModel;
use Silex\Application;
use Site\Model\UserWithId;

class SilexSessionHelper
{
    public static function getUserId(Application $app) {
        $userId = '';
        $silexUser = $app['security.token_storage']->getToken()->getUser();
        if (is_object($silexUser) && get_class($silexUser) == 'Site\Model\UserWithId') {
            /** @var UserWithId $silexUser */
            $userId = $silexUser->getUserId();
        }
        return $userId;
    }

    public static function getProjectId(Application $app, Website $website) {
        $projectId = $app['session']->get('projectId');
        if (!$projectId) {
            $userId = self::getUserId($app);
            $user = new UserModel($userId);
            $projectId = $user->getCurrentProjectId($website);
        }
        return $projectId;
    }
}
