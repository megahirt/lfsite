<?php
namespace models\languageforge\lexicon;

use models\shared\rights\ProjectRoles;

use models\shared\rights\Operation;
use models\shared\rights\Domain;
use models\shared\rights\RolesBase;

class LexiconRoles extends ProjectRoles {
	
	const OBSERVER = 'observer';
	const OBSERVER_WITH_COMMENT = 'observer_with_comment';
	const CONTRIBUTOR = 'contributor';
	
	public static function init() {
		parent::init();
		
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
		
		// Manager
		$rights = array();
		self::grantAllOnDomain($rights[self::MANAGER], Domain::PROJECTS);
		self::grantAllOnDomain($rights[self::MANAGER], Domain::ENTRIES);
		self::grantAllOnDomain($rights[self::MANAGER], Domain::COMMENTS);
		self::$_rights[self::MANAGER] = $rights;
	}
	
}
LexiconRoles::init();

?>