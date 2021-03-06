<?php

namespace Api\Model\Shared;

use Api\Model\Shared\Mapper\MapperListModel;

class ProjectListModel extends MapperListModel
{
    public function __construct()
    {
        parent::__construct(
            ProjectModelMongoMapper::instance(),
            array(),
            array('projectName', 'language', 'projectCode', 'siteName', 'appName')
        );
    }
}
