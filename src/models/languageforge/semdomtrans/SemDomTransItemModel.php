<?php

namespace models\languageforge\semdomtrans;

use models\mapper\MapOf;

use models\languageforge\SemDomTransProjectModel;
use models\mapper\ArrayOf;
use models\ProjectModel;

class SemDomTransItemModel extends \models\mapper\MapperModel
{
    public static function mapper($databaseName)
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new \models\mapper\MongoMapper($databaseName, 'semDomTransItems');
        }

        return $instance;
    }

    /**
     * @param ProjectModel $projectModel
     * @param string       $id
     */
    public function __construct($projectModel, $id = '')
    {
        $this->id = new Id();
        $this->key = "";
        $this->name = new SemDomTransTranslatedForm();
        $this->description = new SemDomTransTranslatedForm();
        $this->searchKeys = new ArrayOf(function ($data) {
        	return new SemDomTransTranslatedForm();
        });
        
        $this->questions = new ArrayOf(function ($data) {
        	return new SemDomTransTranslatedForm();
        });
        
        $databaseName = $projectModel->databaseName();
        parent::__construct(self::mapper($databaseName), $id);
    }

    /**
     * @var Id
     */
    public $id;
    
    /**
     * @var string
     */
    public $key;
    
    /**
     * @var SemDomTransTranslatedForm
     */
    public $name;
    
    /**
     * @var SemDomTransTranslatedForm
     */
    public $description;
    
    /**
     * @var ArrayOf(SemDomTransTranslatedForm)
     */
    public $searchKeys;
    
    /**
     * @var ArrayOf(SemDomTransTranslatedForm)
     */
    public $questions;
    
 }
