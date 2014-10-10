<?php
use models\languageforge\lexicon\LexEntryListModel;
use models\languageforge\lexicon\LiftImport;
use models\languageforge\lexicon\LiftMergeRule;
use models\languageforge\lexicon\LexEntryModel;
use models\languageforge\lexicon\Sense;
use models\languageforge\lexicon\Example;
use models\mapper\ArrayOf;
use models\languageforge\lexicon\LexiconMultiValueField;

require_once dirname(__FILE__) . '/../../TestConfig.php';
require_once SimpleTestPath . 'autorun.php';
require_once TestPath . 'common/MongoTestEnvironment.php';

class TestLiftImportFlex extends UnitTestCase
{

    public function __construct() {
        $this->environment = new LexiconMongoTestEnvironment();
        $this->environment->clean();
        parent::__construct();
    }

    /**
     * @var LexiconMongoTestEnvironment
     */
    private $environment;

    private static function indexByGuid($entries)
    {
        $index = array();
        foreach ($entries as $entry) {
            $index[$entry['guid']] = $entry;
        }
        return $index;
    }

    /**
     * Cleanup test lift files
     */
    public function tearDown()
    {
        $this->environment->cleanupTestUploadFiles();
        $this->environment->clean();
    }

    const liftAllFlexFields = <<<EOD
<lift producer="SIL.FLEx 8.0.9.41689" version="0.13">
<header>
<ranges>
<range id="dialect" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="etymology" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="grammatical-info" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="lexical-relation" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="note-type" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="paradigm" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="reversal-type" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="semantic-domain-ddp4" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="status" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="users" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="location" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<!-- The following ranges are produced by FieldWorks Language Explorer, and are not part of the LIFT standard. -->
<range id="anthro-code" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="translation-type" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<!-- The parts of speech are duplicated in another range because derivational affixes require a "From" PartOfSpeech as well as a "To" PartOfSpeech. -->
<range id="from-part-of-speech" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="morph-type" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="exception-feature" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="inflection-feature" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="inflection-feature-type" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="do-not-publish-in" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="domain-type" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="sense-type" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
<range id="usage-type" href="file://C:/src/ScriptureForge/sfwebchecks/docs/lift/AllFLExFields/AllFLExFields.lift-ranges"/>
</ranges>
<fields>
<field tag="cv-pattern">
<form lang="en"><text>This records the syllable pattern for a LexPronunciation in FieldWorks.</text></form>
</field>
<field tag="tone">
<form lang="en"><text>This records the tone information for a LexPronunciation in FieldWorks.</text></form>
</field>
<field tag="comment">
<form lang="en"><text>This records a comment (note) in a LexEtymology in FieldWorks.</text></form>
</field>
<field tag="import-residue">
<form lang="en"><text>This records residue left over from importing a standard format file into FieldWorks (or LinguaLinks).</text></form>
</field>
<field tag="literal-meaning">
<form lang="en"><text>This field is used to store a literal meaning of the entry.  Typically, this field is necessary only for a compound or an idiom where the meaning of the whole is different from the sum of its parts.</text></form>
</field>
<field tag="summary-definition">
<form lang="en"><text>A summary definition (located at the entry level in the Entry pane) is a general definition summarizing all the senses of a primary entry. It has no theoretical value; its use is solely pragmatic.</text></form>
</field>
<field tag="scientific-name">
<form lang="en"><text>This field stores the scientific name pertinent to the current sense.</text></form>
</field>
</fields>
</header>
<entry dateCreated="2014-09-25T09:13:41Z" dateModified="2014-09-25T10:10:31Z" id="คาม_0a18bb95-0eb2-422e-bf7e-c1fd90274670" guid="0a18bb95-0eb2-422e-bf7e-c1fd90274670">
<lexical-unit>
<form lang="th"><text>คาม</text></form>
</lexical-unit>
<trait  name="morph-type" value="stem"/>
<citation>
<form lang="th"><text>คาม</text></form>
</citation>
<note type="bibliography">
<form lang="en"><text>A Bibliography</text></form>
</note>
<note>
<form lang="en"><text>A Note</text></form>
</note>
<field type="literal-meaning">
<form lang="en"><text>A Literal Meaning</text></form>
</field>
<note type="restrictions">
<form lang="en"><text>A Restrictions</text></form>
</note>
<field type="summary-definition">
<form lang="en"><text>A Summary Defn</text></form>
</field>
<field type="import-residue">
<form lang="en"><text>A Import Residue</text></form>
</field>
<etymology type="proto" source="A Etymology Source">
<form lang="th"><text>คาม</text></form>
<form lang="en"><text>A Etymology</text></form>
<gloss lang="en"><text>A Etymology Gloss</text></gloss>
<field type="comment">
<form lang="en"><text>A Etymology Comment</text></form>
</field>
</etymology>
<relation type="_component-lexeme" ref="คาม ๒_dc4106ac-13fd-4ae0-a32b-b737f413d515" order="0">
<trait  name="variant-type" value="Dialectal Variant"/>
<field type="summary">
<form lang="en"><text>Secondary relationship</text></form>
</field>
</relation>
<pronunciation>
<form lang="th"><text>คาม</text></form>
<media href="Kalimba.mp3">
</media><field type="cv-pattern">
<form lang="en"><text>A CV Pattern</text></form>
</field>
<field type="tone">
<form lang="en"><text>Mid</text></form>
</field>
</pronunciation>
<sense id="aa7fce82-adf5-42f0-a22f-7b8394d48b86">
<grammatical-info value="Noun">
</grammatical-info>
<gloss lang="en"><text>A Word</text></gloss>
<definition>
<form lang="en"><text>A Word Defn</text></form>
</definition>
<example>
<form lang="th"><text>ใหท่ มี</text></form>
<translation type="Free translation">
<form lang="en"><text>A Translation</text></form>
</translation>
</example>
<trait name ="semantic-domain-ddp4" value="9.1.3.1 Physical, non-physical"/>
<note type="anthropology">
<form lang="en"><text>A Anthropology Note</text></form>
</note>
<note type="bibliography">
<form lang="en"><text>A Sense Biliography</text></form>
</note>
<note type="discourse">
<form lang="en"><text>A Discourse Note</text></form>
</note>
<note type="encyclopedic">
<form lang="en"><text>A <span href="http://angular.github.io/" class="Hyperlink">Encylopdeic</span> Info</text></form>
</note>
<note>
<form lang="en"><text>A General Note</text></form>
</note>
<note type="grammar">
<form lang="en"><text>A Grammar Note</text></form>
</note>
<field type="import-residue">
<form lang="en"><text>A Sense Import Resdue</text></form>
</field>
<note type="phonology">
<form lang="en"><text>A Phonolgy Note</text></form>
</note>
<note type="restrictions">
<form lang="en"><text>A Restrictions</text></form>
</note>
<field type="scientific-name">
<form lang="en"><text>A Scientific Name</text></form>
</field>
<note type="semantics">
<form lang="en"><text>A Semantics Note</text></form>
</note>
<note type="sociolinguistics">
<form lang="en"><text>A Sociolinguistics Note<span href="file://others/Hydrangeas.jpg" class="Hyperlink">C:\ProgramData\SIL\FieldWorks\Projects\AllFLExFields\LinkedFiles\Others\Hydrangeas.jpg</span></text></form>
</note>
<note type="source">
<form lang="en"><text>A Sense Source</text></form>
</note>
<trait  name="anthro-code" value="901"/>
<trait  name="domain-type" value="applied linguistics"/>
<reversal type="en"><form lang="en"><text>A Reversal Entries</text></form>
</reversal>
<trait  name="sense-type" value="primary"/>
<trait  name="status" value="Tentative"/>
<trait  name="usage-type" value="colloquial"/>
<illustration href="Desert.jpg">
<label>
<form lang="th"><text></text></form>
<form lang="en"><text></text></form>
<form lang="fr"><text></text></form>
</label>
</illustration></sense>
</entry>
<entry dateCreated="2014-09-25T09:40:25Z" dateModified="2014-09-25T09:46:34Z" id="คาม ๒_dc4106ac-13fd-4ae0-a32b-b737f413d515" guid="dc4106ac-13fd-4ae0-a32b-b737f413d515">
<lexical-unit>
<form lang="th"><text>คาม ๒</text></form>
</lexical-unit>
<trait  name="morph-type" value="phrase"/>
<relation type="_component-lexeme" ref="aa7fce82-adf5-42f0-a22f-7b8394d48b86" order="0">
<trait name="is-primary" value="true"/>
<trait name="complex-form-type" value=""/>
</relation>
<relation type="_component-lexeme" ref="คาม_0a18bb95-0eb2-422e-bf7e-c1fd90274670" order="1">
<trait name="is-primary" value="true"/>
<trait name="complex-form-type" value=""/>
</relation>
<sense id="9335e15c-7efa-49ad-8a37-42b5b2db8ee3">
<grammatical-info value="Verb">
</grammatical-info>
<gloss lang="en"><text>B Word</text></gloss>
</sense>
</entry>
</lift>
EOD;

    public function testLiftImportMerge_FlexAllFields_HasAllFields()
    {
        $liftFilePath = $this->environment->createTestLiftFile(self::liftAllFlexFields, 'LiftAllFlexFields.lift');
        $project = $this->environment->createProject(SF_TESTPROJECT, SF_TESTPROJECTCODE);
        $mergeRule = LiftMergeRule::IMPORT_WINS;
        $skipSameModTime = false;

        LiftImport::merge($liftFilePath, $project, $mergeRule, $skipSameModTime);

        $entryList = new LexEntryListModel($project);
        $entryList->read();

        $entries = $entryList->entries;
        $this->assertEqual($entryList->count, 2);
        $index = self::indexByGuid($entries);

        $entry0 = new LexEntryModel($project, $index['0a18bb95-0eb2-422e-bf7e-c1fd90274670']['id']);
        $entry1 = new LexEntryModel($project, $index['dc4106ac-13fd-4ae0-a32b-b737f413d515']['id']);

        $this->assertEqual($entry0->guid, '0a18bb95-0eb2-422e-bf7e-c1fd90274670');
        $this->assertEqual($entry0->lexeme['th'], 'คาม');
        $this->assertEqual($entry0->citationForm['th'], 'คาม');
        $this->assertEqual($entry0->etymology['th'], 'คาม');
        $this->assertEqual($entry0->etymology['en'], 'A Etymology');
        $this->assertEqual($entry0->etymologyGloss['en'], 'A Etymology Gloss');
        $this->assertEqual($entry0->etymologyComment['en'], 'A Etymology Comment');
        $this->assertEqual($entry0->pronunciation['th'], 'คาม');
        $this->assertEqual($entry0->morphologyType, 'stem');
        $this->assertEqual($entry0->literalMeaning['en'], 'A Literal Meaning');

        /* @var $sense00 Sense */
        $sense00 = $entry0->senses[0];

        $this->assertEqual($sense00->partOfSpeech->value, 'Noun');
        $this->assertEqual($sense00->gloss['en']->value, 'A Word');
        $this->assertEqual($sense00->definition['en']->value, 'A Word Defn');

        /* @var $example000 Example */
        $example000 = $sense00->examples[0];
//         var_dump($example000);
        $this->assertEqual($example000->sentence['th'], 'ใหท่ มี');
        $this->assertEqual($example000->translation['en']->value, 'A Translation');

        $expected = LexiconMultiValueField::createFromArray(array('9.1.3.1 Physical, non-physical'));
        $this->assertEqual($sense00->semanticDomain, $expected);

        $expected = LexiconMultiValueField::createFromArray(array('901'));
        $this->assertEqual($sense00->anthropologyCategories, $expected);

//         $this->assertEqual($example000->, $second)

//         $this->assertEqual($sense00->scientificName, 'Noun');

//         var_dump($entry0->senses);

//         $this->assertEqual($entry0)



        /*
        $this->assertEqual($entry0['guid'], "dd15cbc4-9085-4d66-af3d-8428f078a7da");
        $this->assertEqual($entry0['lexeme']['th-fonipa']['value'], "chùuchìi mǔu krɔ̂ɔp");
        $this->assertEqual($entry0['lexeme']['th']['value'], "ฉู่ฉี่หมูกรอบ");
        $this->assertEqual(count($entry0['senses']), 1);
        $this->assertEqual($entry0['senses'][0]['definition']['en']['value'], "incorrect definition");
        $this->assertEqual($entry0['senses'][0]['gloss']['en']['value'], "incorrect gloss");
        $this->assertEqual($entry0['senses'][0]['gloss']['th']['value'], "th incorrect gloss");
        $this->assertEqual($entry0['senses'][0]['partOfSpeech']['value'], "Adjective");
        $this->assertEqual($entry0['senses'][0]['semanticDomain']['values'][0], "5.2 Food");
        $this->assertEqual($entry0['senses'][0]['semanticDomain']['values'][1], "1 Universe, creation");
        $this->assertEqual($entry0['senses'][0]['examples'][0]['sentence']['th-fonipa']['value'], "sentence 1");
        $this->assertEqual($entry0['senses'][0]['examples'][0]['translation']['en']['value'], "translation 1");
        $this->assertEqual($entry0['senses'][0]['examples'][1]['sentence']['th-fonipa']['value'], "sentence 2");
        $this->assertEqual($entry0['senses'][0]['examples'][1]['translation']['en']['value'], "translation 2");
        $this->assertEqual($entry1['guid'], "05473cb0-4165-4923-8d81-02f8b8ed3f26");
        $this->assertEqual($entry1['lexeme']['th-fonipa']['value'], "khâaw kài thɔ̀ɔt");
        $this->assertEqual($entry1['lexeme']['th']['value'], "ข้าวไก่ทอด");
        */
    }

}
