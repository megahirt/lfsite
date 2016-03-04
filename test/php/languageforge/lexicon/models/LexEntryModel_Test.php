<?php
use Api\Model\Languageforge\Lexicon\LexEntryModel;

require_once __DIR__ . '/../../../TestConfig.php';
require_once SimpleTestPath . 'autorun.php';
require_once TestPhpPath . 'common/MongoTestEnvironment.php';

class TestLexEntryModel extends UnitTestCase
{

    public function testGetProperties_ConstructsCorrectTypes()
    {
        $e = new MongoTestEnvironment();
        $project = $e->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $entry = new LexEntryModel($project);

        $this->assertIsA($entry->senses, 'Api\Model\Mapper\ArrayOf');
        $this->assertIsA($entry->customFields, 'Api\Model\Mapper\MapOf');
        $this->assertIsA($entry->authorInfo, 'Api\Model\Languageforge\Lexicon\AuthorInfo');
        $this->assertIsA($entry->lexeme, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->pronunciation, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->cvPattern, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->citationForm, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->etymology, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->etymologyGloss, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->etymologyComment, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->etymologySource, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->note, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->literalMeaning, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->entryBibliography, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->entryRestrictions, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->summaryDefinition, 'Api\Model\Languageforge\Lexicon\MultiText');
        // Removed import residue. Its no longer a field we import CP 2014-10
        // $this->assertIsA($entry->entryImportResidue, 'models\languageforge\lexicon\MultiText');
        $this->assertIsA($entry->tone, 'Api\Model\Languageforge\Lexicon\MultiText');
        $this->assertIsA($entry->environments, 'Api\Model\Languageforge\Lexicon\LexiconMultiValueField');
        $this->assertIsA($entry->location, 'Api\Model\Languageforge\Lexicon\LexiconField');
    }
}
