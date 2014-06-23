<?php

namespace models\shared\dto;

use models\shared\rights\Domain;
use models\shared\rights\Operation;
use models\shared\rights\ProjectRoles;
use models\ProjectList_UserModel;
use models\ProjectModel;
use models\TextListModel;
use models\UserModel;

class ProjectListDto
{
	/**
	 * @param string $userId
	 * @param string $site
	 * @param boolean $isArchivedList - set true to list archived projects
	 * @return array - the DTO array
	 */
	public static function encode($userId, $site, $isArchivedList = false) {
		$user = new UserModel($userId);
		$canListAllProjects = $user->hasRight(Domain::PROJECTS + Operation::VIEW);

		$projectList = new ProjectList_UserModel($site);
		if ($canListAllProjects) {
			$projectList->readAll();
		} else {
			$projectList->readUserProjects($userId);
		}

		$data = array();
		$data['entries'] = array();
		$count = 0;
		foreach ($projectList->entries as $entry) {
			$project = new ProjectModel($entry['id']);
			if (! ($project->isArchived xor $isArchivedList )) {
				$role = ProjectRoles::NONE;
				if (count($project->users) > 0) {
					if (isset($project->users[$userId]) && isset($project->users[$userId]->role)) {
						$role = $project->users[$userId]->role;
					}
				}
				$entry['role'] = $role;
				$entry['dateModified'] = $project->dateModified->format(\DateTime::RFC2822);
				$data['entries'][] = $entry;
				$count++;
			}
		}
		$data['count'] = $count;
		
		// Default sort list on project names
		usort($data['entries'], function ($a, $b) {
			$sortOn = 'projectname';
			if (array_key_exists($sortOn, $a) &&
				array_key_exists($sortOn, $b)){
				return (strtolower($a[$sortOn]) > strtolower($b[$sortOn])) ? 1 : -1;
			} else {
				return 0;
			}
		});
		return $data;
	}
}

?>
