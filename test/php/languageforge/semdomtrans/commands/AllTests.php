<?php

require_once __DIR__ . '/../../../TestConfig.php';
require_once SimpleTestPath . 'autorun.php';

class AllSemDomTransCommandsTests extends TestSuite
{
    public function __construct()
    {
        parent::__construct();
        $this->addFile(TestPhpPath . 'languageforge/semdomtrans/commands/SemDomTransItemCommands_Test.php');
        $this->addFile(TestPhpPath . 'languageforge/semdomtrans/commands/SemDomTransProjectCommands_Test.php');
    }
}
