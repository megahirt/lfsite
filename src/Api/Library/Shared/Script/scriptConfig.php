<?php

$rootPath = realpath(__DIR__ . '/../../../../..');

define('TestPath', $rootPath . '/test/');
define('APPPATH', $rootPath . '/src/');

require_once APPPATH . 'vendor/autoload.php';

define('SF_DATABASE', 'scriptureforge');
define('BCRYPT_COST', 7);
define('MONGODB_CONN', 'mongodb://localhost:27017');
