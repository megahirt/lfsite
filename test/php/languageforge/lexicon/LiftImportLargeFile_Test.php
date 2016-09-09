<?php

use Api\Model\Languageforge\Lexicon\LiftImport;
use Api\Model\Languageforge\Lexicon\LiftMergeRule;

require_once __DIR__ . '/../../TestConfig.php';
require_once SimpleTestPath . 'autorun.php';
require_once TestPhpPath . 'common/MongoTestEnvironment.php';

class TestLiftImportInfo
{
    public $points;

    public function __construct()
    {
        $this->points = array();
        $this->add('base');
    }

    public function add($name)
    {
        $mem = memory_get_peak_usage(true);
        $current = memory_get_usage();
        $point = array('name' => $name, 'mem' => $mem, 'current' => $current);
        $this->points[] = $point;
        $this->displayPoint($point);
    }

    public function display()
    {
        foreach ($this->points as $point) {
            $this->displayPoint($point);
        }
    }

    public function displayPoint($point)
    {
        echo $point['name'] . ' pk '. $point['mem'] / 1024 . 'K cur '  . $point['current'] / 1024 . 'K<br/>';
    }

}

class TestLiftImportLargeFile extends UnitTestCase
{
    public function testLiftImportMerge_LargeFile_NoException()
    {
        global $testInfo;
        $testInfo = new TestLiftImportInfo();

        $e = new LexiconMongoTestEnvironment();
        $e->clean();

        $project = $e->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
//         $liftFilePath = '/home/cambell/src/Forge/TestData/Gilaki/Gilaki.lift';
//        $liftFilePath = '/home/cambell/src/Forge/TestData/Webster/Webster.lift';
//        $liftFilePath = '/home/ira/TestData/test-langprojih-flex/test-langprojih-flex.lift';
        $liftFilePath = '/home/ira/TestData/test-rwr-flex/test-rwr-flex.lift';
        $mergeRule =  LiftMergeRule::IMPORT_WINS;
        $skipSameModTime = false;

        $time1 = new DateTime();
        LiftImport::get()->merge($liftFilePath, $project, $mergeRule, $skipSameModTime);
        $time2 = new DateTime();
        $elapsed = $time2->diff($time1);
        echo $elapsed->format('%I:%S') . '<br/>';

        $testInfo->add('post merge');
    }

}
