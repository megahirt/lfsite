<?php 
namespace models\languageforge\lexicon;

use models\ProjectModel;

class LexCommentListModel extends \models\mapper\MapperListModel {

	public static function mapper($databaseName) {
		static $instance = null;
		if (null === $instance) {
			$instance = new \models\mapper\MongoMapper($databaseName, 'lexiconComments');
		}
		return $instance;
	}

	/**
	 * 
	 * @param ProjectModel $projectModel
     * @param int $newerThanTimestamp
	 */
	public function __construct($projectModel, $newerThanTimestamp = null) {
        // sort ascending by creation date
        if (!is_null($newerThanTimestamp)) {
            $startDate = new \MongoDate($newerThanTimestamp);
            parent::__construct( self::mapper($projectModel->databaseName()), array('isDeleted' => false, 'dateModified'=> array('$gte' => $startDate)), array(), array('dateCreated' => 1));
        } else {
		    parent::__construct( self::mapper($projectModel->databaseName()), array('isDeleted' => false), array(), array('dateCreated' => 1));
        }
	}
}

?>