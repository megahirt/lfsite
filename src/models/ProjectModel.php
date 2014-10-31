<?php

namespace models;

use libraries\shared\Website;
use models\scriptureforge\RapumaProjectModel;

use models\languageforge\lexicon\LexiconProjectModel;

use models\scriptureforge\SfchecksProjectModel;

use Palaso\Utilities\CodeGuard;

use models\shared\rights\ProjectRoleModel;
use models\mapper\MapOf;
use models\mapper\Id;
use models\sms\SmsSettings;
use models\mapper\IdReference;

class ProjectModel extends \models\mapper\MapperModel
{

    protected $rolesClass;

    public function __construct($id = '')
    {
        $this->id = new Id();
        $this->ownerRef = new IdReference();
        $this->users = new MapOf(function ($data) {
            return new ProjectRoleModel();
        });
        $this->isArchived = false;
        $this->userProperties = new ProjectUserPropertiesSettings();
        $this->allowAudioDownload = true;
        $this->allowInviteAFriend = true;
        $this->interfaceLanguageCode = 'en';
        parent::__construct(ProjectModelMongoMapper::instance(), $id);
    }

    /**
     *
     * @param Website $website
     * @return ProjectModel
     */
    public static function getDefaultProject($website)
    {
        $project = new ProjectModel();
        if ($project->readByProperties(array('projectCode' => $website->defaultProjectCode, 'siteName' => $website->domain))) {
            return ProjectModel::getById($project->id->asString());
        } else {
            return null;
        }
    }

    /**
     * Reads the model from the mongo collection
     * Ensures that the required pick lists exist even if not present in the database
     * @param string $id
     * @see MapperModel::read()
     */
    public function read($id)
    {
        $result = parent::read($id);
        $this->userProperties->ensurePickListsExist();
        return $result;
    }

    /**
     * (non-PHPdoc)
     * @see \models\mapper\MapperModel::databaseName()
     */
    public function databaseName()
    {
        CodeGuard::checkEmptyAndThrow($this->projectCode, 'projectCode');
        $name = strtolower($this->projectCode);
        $name = str_replace(' ', '_', $name);

        return 'sf_' . $name;
    }

    /**
     * Removes this project from the collection.
     * User references to this project are also removed
     */
    public function remove()
    {
        foreach ($this->users as $userId => $roleObj) {
            $user = new UserModel($userId);
            $user->removeProject($this->id->asString());
            $user->write();
        }
        $this->rrmdir($this->getAssetsFolderPath());

        ProjectModelMongoMapper::instance()->drop($this->databaseName());
        ProjectModelMongoMapper::instance()->remove($this->id->asString());
    }

    /**
     * Adds the $userId as a member of this project.
     * @param string $userId
     * @param string $role The system role the user has.
     * @see Roles;
     */
    public function addUser($userId, $role)
    {
        $mapper = ProjectModelMongoMapper::instance();
//        $ProjectModelMongoMapper::mongoID($userId)
        $model = new ProjectRoleModel();
        $model->role = $role;
        $this->users[$userId] = $model;
    }

    /**
     * Removes the $userId from this project.
     * @param string $userId
     */
    public function removeUser($userId)
    {
        unset($this->users[$userId]);
    }

    /**
     *
     * @param string $userId
     * @return bool
     */
    public function userIsMember($userId)
    {
        return key_exists($userId, $this->users);
    }

    public function listUsers()
    {
        $userList = new UserList_ProjectModel($this->id->asString());
        $userList->read();
        for ($i = 0, $l = count($userList->entries); $i < $l; $i++) {
            $userId = $userList->entries[$i]['id'];
            if (!key_exists($userId, $this->users)) {
                $projectId = $this->id->asString();
                //error_log("User $userId is not a member of project $projectId");
                continue;
            }
            $userList->entries[$i]['role'] = $this->users[$userId]->role;
        }
         return $userList;
    }

    /**
     * Returns true if the given $userId has the $right in this project.
     * @param string $userId
     * @param int $right
     * @return bool
     */
    public function hasRight($userId, $right)
    {
        if (!method_exists($this->rolesClass, 'hasRight')) {
            throw new \Exception('hasRight method cannot be called directly from ProjectModel');
        }
        $hasRight = false;
        if (key_exists($userId, $this->users)) {
            $rolesClass = $this->rolesClass;
            $hasRight = $rolesClass::hasRight($this->users[$userId]->role, $right);
        }
        return $hasRight;
    }

    /**
     * Returns the rights array for the $userId role.
     * @param string $userId
     * @return array
     */
    public function getRightsArray($userId)
    {
        if (!method_exists($this->rolesClass, 'getRightsArray')) {
            throw new \Exception('getRightsArray method cannot be called directly from ProjectModel');
        }
        CodeGuard::checkTypeAndThrow($userId, 'string');
        if (!key_exists($userId, $this->users)) {
            $result = array();
        } else {
            $role = $this->users[$userId]->role;
            $rolesClass = $this->rolesClass;
            $result = $rolesClass::getRightsArray($role);
        }
        return $result;
    }

    /**
     * Returns the "public" settings of this project (the ones that everyone
     * is allowed to see, with no security concerns)
     * Base classes should expand on this to add more settings
     * @param string $userId
     * @return array
     */
    public function getPublicSettings($userId)
    {
        $settings = array(
            "allowInviteAFriend" => $this->allowInviteAFriend,
        );
        return $settings;
    }

    /**
     *
     * @param string $projectId
     * @return appropriate project model for the type
     */
    public static function getById($projectId)
    {
        $m = new ProjectModel($projectId);
        switch ($m->appName) {
            case 'sfchecks':
                return new SfchecksProjectModel($projectId);
            case 'rapuma':
                return new RapumaProjectModel($projectId);
            case 'lexicon':
                return new LexiconProjectModel($projectId);
            default:
                return new ProjectModel($projectId);
        }
    }

    /**
     * @return string Relative path of the projects assets folder
     */
    public function getAssetsPath()
    {
        return 'assets/' . $this->appName. '/' . $this->databaseName();
    }

    /**
     * @return string Full path of the projects assets folder
     */
    public function getAssetsFolderPath()
    {
        return APPPATH . $this->getAssetsPath();
    }

    /**
     * @return Website
     */
    public function website()
    {
        return Website::get($this->siteName);
    }

    public function initializeNewProject()
    {
        // this method should be overridden by child classes
    }

    /**
     * @var Id
     */
    public $id;

    /**
     * ID of the user that created the project
     * @var IdReference
     */
    public $ownerRef;

    /**
     * @var string
     */
    public $projectName;

    /**
     * Web app interface language code
     * @var string
     */
    public $interfaceLanguageCode;

    /**
     * @var string
     */
    // TODO move this to a subclass cjh 2014-02
    public $language;

    /**
     * @var MapOf<ProjectRoleModel>
     */
    public $users;

    /**
     * A string representing exactly this project from external sources. Typically some part of the URL.
     * @var string
     */
    public $projectCode;

    /**
     * Flag to indicated if this project is featured on the website
     * @var boolean
     */
    public $featured;

    /**
     * Flag to indicate if this project allows users to download audio files
     * @var boolean
     */
    public $allowAudioDownload;

    /**
     * Flag to indicate if this project allows users to invite a friend
     * @var boolean
     */
    public $allowInviteAFriend;

    /**
     * Flag to indicate if this project is archived
     * @var boolean
     */
    public $isArchived;

    /**
     * @var ProjectUserPropertiesSettings
     */
    public $userProperties;

    /**
     * Specifies which site this project belongs to.  e.g. scriptureforge || languageforge  cf. Website class
     * @var string
     */
    public $siteName;

    /**
     *  specifies the angular app this project is associated with e.g. sfchecks || lexicon  (note: these apps are site specific)
     * @var string
     */
    public $appName;

    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") $this->rrmdir($dir."/".$object); else unlink($dir."/".$object);
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}

/**
 * This class is separate from the ProjectModel to protect the smsSettings and emailSettings which are managed
 * by the site administrator only.
 */
class ProjectSettingsModel extends ProjectModel
{
    public function __construct($id = '')
    {
        $this->smsSettings = new SmsSettings();
        $this->emailSettings = new EmailSettings();
        parent::__construct($id);
    }

    /**
     * @var SmsSettings
     */
    public $smsSettings;

    /**
     * @var EmailSettings
     */
    public $emailSettings;

}
