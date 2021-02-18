<?php

namespace Api\Model\Shared\Command;

use Api\Service\Ldapi;

class LdapiCommands
{
    const USERS_BASE_URL = 'users';
    const SEARCHUSERS_BASE_URL = 'searchUsers';
    const PROJECTS_BASE_URL = 'projects';
    const SEARCHPROJECTS_BASE_URL = 'searchProjects';
    const ROLES_BASE_URL = 'roles';

    const URL_PART_GET_ALL = '';
    const URL_PART_POST_ONE = '';
    const URL_PART_GET_PROJECTS = 'projects';

    const URL_VERIFY_PASSWORD = 'verify-password';

    public static function getAllUsers($languageDepotUsername) {
        return Ldapi::call($languageDepotUsername, 'get', self::USERS_BASE_URL . self::URL_PART_GET_ALL);
    }

    public static function searchUsers($languageDepotUsername, string $searchText) {
        return Ldapi::call($languageDepotUsername, 'get', self::SEARCHUSERS_BASE_URL . '/' . $searchText);
    }

    public static function getAllProjects($languageDepotUsername) {
        return Ldapi::call($languageDepotUsername, 'get', self::PROJECTS_BASE_URL . self::URL_PART_GET_ALL);
    }

    public static function getUser($languageDepotUsername, string $username) {
        return Ldapi::call($languageDepotUsername, 'get', self::USERS_BASE_URL . '/' . $username);
    }

    public static function updateUser($languageDepotUsername, string $username, Array $userdetails) {
        return Ldapi::call($languageDepotUsername, 'put', self::USERS_BASE_URL . '/' . $username, $userdetails);
    }

    public static function checkUserPassword($languageDepotUsername, string $username, string $password) {
        $loginData = ['username' => $username, 'password' => $password];
        return Ldapi::call($languageDepotUsername, 'post', self::URL_VERIFY_PASSWORD, $loginData);
    }

    public static function getProject($languageDepotUsername, string $projectCode) {
        return Ldapi::call($languageDepotUsername, 'get', self::PROJECTS_BASE_URL . '/' . $projectCode);
    }

    public static function getProjectsForUser($languageDepotUsername, string $username) {
        return Ldapi::call($languageDepotUsername, 'get', self::USERS_BASE_URL . '/' . $username . '/' . self::URL_PART_GET_PROJECTS);
    }

    public static function updateUserRoleInProject($languageDepotUsername, string $projectCode, string $username, string $role) {
        $addRequest = ['username' => $username, 'role' => $role];
        $apiParams = ['add' => $addRequest];
        return Ldapi::call($languageDepotUsername, 'patch', self::PROJECTS_BASE_URL . '/' . $projectCode, $apiParams);
    }

    public static function removeUserFromProject($languageDepotUsername, string $projectCode, string $username) {
        $apiParams = ['removeUser' => $username];
        return Ldapi::call($languageDepotUsername, 'patch', self::PROJECTS_BASE_URL . '/' . $projectCode, $apiParams);
    }

    public static function isUserManagerOfProject($languageDepotUsername, string $username, string $projectCode) {
        return Ldapi::call($languageDepotUsername, 'get', self::USERS_BASE_URL . '/' . $username . '/isManagerOfProject/' . $projectCode);
    }

    public static function getAllRoles($languageDepotUsername) {
        return Ldapi::call($languageDepotUsername, 'get', self::ROLES_BASE_URL . self::URL_PART_GET_ALL);
    }
}

?>
