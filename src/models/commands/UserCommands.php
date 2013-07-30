<?php

namespace models\commands;

use models\mapper\Id;

class UserCommands
{
	
	/**
	 * @param array $userIds
	 * @return int Total number of users removed.
	 */
	public static function deleteUsers($userIds) {
		$count = 0;
		foreach ($userIds as $userId) {
			$userModel = new \models\UserModel(new Id($userId));
			$userModel->remove();
			$count++;
		}
		return $count;
	}
	
}

?>