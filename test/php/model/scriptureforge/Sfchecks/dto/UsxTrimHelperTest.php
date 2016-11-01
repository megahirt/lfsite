<?php

use Api\Model\Scriptureforge\Sfchecks\Dto\UsxTrimHelper;
//use PHPUnit\Framework\TestCase;

class UsxTrimHelperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param SimpleXMLElement $xml
     * @param int $chNum
     * @return bool
     * @internal param string $errMsg
     */
    static private function hasChapter($xml, $chNum)
    {
        $result = $xml->xpath("//chapter[@number=$chNum]");

        return (!empty($result));
    }

    /**
     * @param SimpleXMLElement $xml
     * @param int $chNum
     * @param int $vsNum
     * @return bool
     */
    static private function hasVerse($xml, $chNum, $vsNum)
    {
        $nextCh = $chNum + 1;
        // Check that we got, say, John 3:16 and not John 4:16
        $result = $xml->xpath("//verse[@number=$vsNum][preceding::chapter[@number=$chNum]][following::chapter[@number=$nextCh]]");
        // Other possibility: the verse we're looking for was in the last chapter of our fragment
        $result2 = $xml->xpath("//verse[@number=$vsNum][preceding::chapter[@number=$chNum]][not(following::chapter)]");

        return (!empty($result) || !empty($result2));
    }

    public function testTrim_oneVerse()
    {
        $usx = MongoTestEnvironment::usxSample();

        $usxHelper = new UsxTrimHelper($usx, 3, 16, 3, 16);
        $result = $usxHelper->trimUsx();
        $simple = new SimpleXMLElement($result);

        $this->assertFalse(self::hasChapter($simple, 2));
        $this->assertTrue(self::hasChapter($simple, 3));
        $this->assertFalse(self::hasChapter($simple, 4));

        $this->assertFalse(self::hasVerse($simple, 2, 16));
        $this->assertFalse(self::hasVerse($simple, 3, 15));
        $this->assertTrue(self::hasVerse($simple, 3, 16));
        $this->assertFalse(self::hasVerse($simple, 3, 17));
        $this->assertFalse(self::hasVerse($simple, 4, 16));
    }

    public function testTrim_twoVerses()
    {
        $usx = MongoTestEnvironment::usxSample();

        $usxHelper = new UsxTrimHelper($usx, 3, 16, 3, 17);
        $result = $usxHelper->trimUsx();
        $simple = new SimpleXMLElement($result);

        $this->assertFalse(self::hasChapter($simple, 2));
        $this->assertTrue(self::hasChapter($simple, 3));
        $this->assertFalse(self::hasChapter($simple, 4));

        $this->assertFalse(self::hasVerse($simple, 3, 15));
        $this->assertTrue(self::hasVerse($simple, 3, 16));
        $this->assertTrue(self::hasVerse($simple, 3, 17));
        $this->assertFalse(self::hasVerse($simple, 3, 18));
    }

    public function testTrim_threeVerses()
    {
        $usx = MongoTestEnvironment::usxSample();

        $usxHelper = new UsxTrimHelper($usx, 3, 16, 3, 18);
        $result = $usxHelper->trimUsx();
        $simple = new SimpleXMLElement($result);

        $this->assertFalse(self::hasChapter($simple, 2));
        $this->assertTrue(self::hasChapter($simple, 3));
        $this->assertFalse(self::hasChapter($simple, 4));

        $this->assertFalse(self::hasVerse($simple, 3, 15));
        $this->assertTrue(self::hasVerse($simple, 3, 16));
        $this->assertTrue(self::hasVerse($simple, 3, 17));
        $this->assertTrue(self::hasVerse($simple, 3, 18));
        $this->assertFalse(self::hasVerse($simple, 3, 19));
    }

    public function testTrim_acrossChapterBoundary()
    {
        $usx = MongoTestEnvironment::usxSample();

        $usxHelper = new UsxTrimHelper($usx, 3, 35, 4, 2);
        $result = $usxHelper->trimUsx();
        $simple = new SimpleXMLElement($result);

        $this->assertFalse(self::hasChapter($simple, 2));
        $this->assertTrue(self::hasChapter($simple, 3));
        $this->assertTrue(self::hasChapter($simple, 4));
        $this->assertFalse(self::hasChapter($simple, 5));

        // John 3 has 36 verses
        $this->assertFalse(self::hasVerse($simple, 3, 34));
        $this->assertTrue(self::hasVerse($simple, 3, 35));
        $this->assertTrue(self::hasVerse($simple, 3, 36));
        $this->assertFalse(self::hasVerse($simple, 3, 37));
        $this->assertTrue(self::hasVerse($simple, 4, 1));
        $this->assertTrue(self::hasVerse($simple, 4, 2));
        $this->assertFalse(self::hasVerse($simple, 4, 3));
    }

    public function testTrim_zeroEndVerse()
    {
        // End verse of 0 means "to end of chapter"
        $usx = MongoTestEnvironment::usxSample();

        $usxHelper = new UsxTrimHelper($usx, 3, 1, 3, 0);
        $result = $usxHelper->trimUsx();
        $simple = new SimpleXMLElement($result);

        $this->assertFalse(self::hasChapter($simple, 2));
        $this->assertTrue(self::hasChapter($simple, 3));
        $this->assertFalse(self::hasChapter($simple, 4));

        // John 3 has 36 verses
        $this->assertTrue(self::hasVerse($simple, 3, 35));
        $this->assertTrue(self::hasVerse($simple, 3, 36));
        $this->assertFalse(self::hasVerse($simple, 3, 37));
    }

    public function testTrim_zeroEndChapter()
    {
        // End chapter of 0 means "to end of book"
        $usx = MongoTestEnvironment::usxSample();

        $usxHelper = new UsxTrimHelper($usx, 20, 1, 0, 0);
        $result = $usxHelper->trimUsx();
        $simple = new SimpleXMLElement($result);

        $this->assertFalse(self::hasChapter($simple, 19));
        $this->assertTrue(self::hasChapter($simple, 20));
        $this->assertTrue(self::hasChapter($simple, 21));
        $this->assertFalse(self::hasChapter($simple, 22));

        // John 20 has 31 verses
        $this->assertTrue(self::hasVerse($simple, 20, 1));
        $this->assertTrue(self::hasVerse($simple, 20, 30));
        $this->assertTrue(self::hasVerse($simple, 20, 31));
        $this->assertFalse(self::hasVerse($simple, 20, 32));
        // John 21 has 25 verses
        $this->assertTrue(self::hasVerse($simple, 21, 1));
        $this->assertTrue(self::hasVerse($simple, 21, 24));
        $this->assertTrue(self::hasVerse($simple, 21, 25));
        $this->assertFalse(self::hasVerse($simple, 21, 26));
    }

    public function testTrim_zeroStartVerse()
    {
        // Start verse of 0 means "from start of chapter"
        $usx = MongoTestEnvironment::usxSample();

        $usxHelper = new UsxTrimHelper($usx, 3, 0, 3, 16);
        $result = $usxHelper->trimUsx();
        $simple = new SimpleXMLElement($result);

        $this->assertFalse(self::hasChapter($simple, 2));
        $this->assertTrue(self::hasChapter($simple, 3));
        $this->assertFalse(self::hasChapter($simple, 4));

        // John 2 has 25 verses
        $this->assertFalse(self::hasVerse($simple, 2, 25));
        $this->assertTrue(self::hasVerse($simple, 3, 1));
        $this->assertTrue(self::hasVerse($simple, 3, 16));
        $this->assertFalse(self::hasVerse($simple, 3, 17));
        $this->assertFalse(self::hasVerse($simple, 4, 1));
    }

    public function testTrim_zeroStartChapter()
    {
        // Start chapter of 0 means "from start of book"
        $usx = MongoTestEnvironment::usxSample();

        $usxHelper = new UsxTrimHelper($usx, 0, 0, 3, 16);
        $result = $usxHelper->trimUsx();
        $simple = new SimpleXMLElement($result);

        $this->assertTrue(self::hasChapter($simple, 1));
        $this->assertTrue(self::hasChapter($simple, 2));
        $this->assertTrue(self::hasChapter($simple, 3));
        $this->assertFalse(self::hasChapter($simple, 4));

        // John 1 has 51 verses; John 2 has 25 verses
        $this->assertTrue(self::hasVerse($simple, 1, 1));
        $this->assertTrue(self::hasVerse($simple, 1, 51));
        $this->assertTrue(self::hasVerse($simple, 2, 1));
        $this->assertTrue(self::hasVerse($simple, 2, 25));
        $this->assertTrue(self::hasVerse($simple, 3, 1));
        $this->assertTrue(self::hasVerse($simple, 3, 16));
        $this->assertFalse(self::hasVerse($simple, 3, 17));
        $this->assertFalse(self::hasVerse($simple, 4, 1));
    }
}
