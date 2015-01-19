<?php

namespace models\languageforge\semdomtrans;


use models\shared\rights\ProjectRoles;

class SemDomTransRoles extends ProjectRoles {
	
	const OBSERVER = 'observer';
	const OBSERVER_WITH_COMMENT = 'observer_with_comment';
	
	public static function init()
	{
		// Observer
		$rights = array();
		$rights[] = Domain::PROJECTS + Operation::VIEW;
		$rights[] = Domain::ENTRIES + Operation::VIEW;
		$rights[] = Domain::COMMENTS + Operation::VIEW;
		self::$_rights[self::OBSERVER] = $rights;
	
		// Observer with comment
		$rights = self::$_rights[self::OBSERVER];
		$rights[] = Domain::COMMENTS + Operation::CREATE;
		$rights[] = Domain::COMMENTS + Operation::DELETE_OWN;
		$rights[] = Domain::COMMENTS + Operation::EDIT_OWN;
		self::$_rights[self::OBSERVER_WITH_COMMENT] = $rights;
	
		// Contributor
		$rights = self::$_rights[self::OBSERVER_WITH_COMMENT];
		$rights[] = Domain::ENTRIES + Operation::EDIT;
		$rights[] = Domain::ENTRIES + Operation::CREATE;
		$rights[] = Domain::ENTRIES + Operation::DELETE;
		self::$_rights[self::CONTRIBUTOR] = $rights;
	
		// Manager (everything a Contributor has... plus the following)
		$rights = self::$_rights[self::CONTRIBUTOR];
		$rights[] = Domain::COMMENTS + Operation::EDIT;
		$rights[] = Domain::COMMENTS + Operation::DELETE;
		$rights[] = Domain::PROJECTS + Operation::EDIT;
		$rights[] = Domain::USERS + Operation::CREATE;
		$rights[] = Domain::USERS + Operation::EDIT;
		$rights[] = Domain::USERS + Operation::DELETE;
		$rights[] = Domain::USERS + Operation::VIEW;
		self::grantAllOnDomain($rights, Domain::ENTRIES);
		self::$_rights[self::MANAGER] = $rights;
	}
	

    private static $_rights;
    public static function hasRight($role, $right) { return self::_hasRight(self::$_rights, $role, $right); }
    public static function getRightsArray($role) { return self::_getRightsArray(self::$_rights, $role); }


}
SemDomTransRoles::init();