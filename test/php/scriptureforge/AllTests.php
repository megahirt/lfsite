<?php
require_once __DIR__ . '/../TestConfig.php';
require_once SimpleTestPath . 'autorun.php';

class AllScriptureForgeTests extends TestSuite
{
    public function __construct()
    {
        parent::__construct();
        $this->addFile(TestPath . 'scriptureforge/commands/AllTests.php');
        $this->addFile(TestPath . 'scriptureforge/dto/AllTests.php');
    }

}
