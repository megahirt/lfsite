<?php

namespace models\languageforge\lexicon;

use models\languageforge\lexicon\config\LexiconConfigObj;
use models\languageforge\lexicon\config\LexiconOptionListItem;
use models\mapper\ArrayOf;
use Palaso\Utilities\FileUtilities;

class LiftImport
{

    /**
     *
     * @var LiftImportStats
     */
    public $stats;

    /**
     *
     * @var string
     */
    public $liftFilePath;

    /**
     *
     * @var ImportErrorReport
     */
    private $report;

    /**
     *
     * @var LiftImportNodeError
     */
    private $liftImportNodeError;

    /**
     *
     * @var LiftDecoder
     */
    private $liftDecoder;

    public static function get()
    {
        static $instance = null;
        if ($instance == null) {
            $instance = new LiftImport();
        }
        return $instance;
    }

    /**
     * @param string $liftFilePath
     * @param LexiconProjectModel $projectModel
     * @param LiftMergeRule $mergeRule
     * @param boolean $skipSameModTime
     * @param boolean $deleteMatchingEntry
     * @return \models\languageforge\lexicon\LiftImport
     */
    public function merge($liftFilePath, $projectModel, $mergeRule = LiftMergeRule::CREATE_DUPLICATES, $skipSameModTime = true, $deleteMatchingEntry = false)
    {
        ini_set('max_execution_time', 90); // Sufficient time to import webster.  TODO Make this async CP 2014-10
//         self::validate($xml);    // TODO Fix. The XML Reader validator doesn't work with <optional> in the RelaxNG schema. IJH 2014-03

        $entryList = new LexEntryListModel($projectModel);
        $entryList->read();
        $hasExistingData = $entryList->count != 0;

        // No data yet? (number of entries == 0)
        if (! $hasExistingData) {
            // save and clear input systems
            $savedInputSystems = $projectModel->inputSystems->getArrayCopy();
            $projectModel->inputSystems->exchangeArray(array());

            // clear entry field input systems config if there are no entries (only use imported input systems)
            $projectModel->config->entry->fields[LexiconConfigObj::LEXEME]->inputSystems = new ArrayOf();
            $projectModel->config->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::DEFINITION]->inputSystems = new ArrayOf();
            $projectModel->config->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::GLOSS]->inputSystems = new ArrayOf();
            $projectModel->config->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->fields[LexiconConfigObj::EXAMPLE_SENTENCE]->inputSystems = new ArrayOf();
            $projectModel->config->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->fields[LexiconConfigObj::EXAMPLE_TRANSLATION]->inputSystems = new ArrayOf();
        }

        $reader = new \XMLReader();
        $reader->open($liftFilePath);

        $this->liftDecoder = new LiftDecoder($projectModel);
        $this->stats = new LiftImportStats($entryList->count);
        $this->report = new ImportErrorReport();
        $this->liftImportNodeError = new LiftImportNodeError(LiftImportNodeError::FILE, basename($liftFilePath));
        $liftRangeDecoder = new LiftRangeDecoder($projectModel);
        $liftRangeFiles = array(); // Keys: filenames. Values: parsed files.
        $liftRanges = array(); // Keys: @id attributes of <range> elements. Values: parsed <range> elements.
        $liftFolderPath = dirname($liftFilePath);

        while ($reader->read()) {

            // Read LIFT ranges in the header of the LIFT file
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->localName == 'range') {
                $node = $reader->expand();
                $rangeId = $node->attributes->getNamedItem('id')->textContent;
                $rangeHref = $node->attributes->getNamedItem('href')->textContent;
                $hrefPath = parse_url($rangeHref, PHP_URL_PATH);
                $rangeFilename = basename($hrefPath);
                $rangeImportNodeError = new LiftRangeImportNodeError(LiftRangeImportNodeError::FILE, $rangeFilename);
                if (! array_key_exists($rangeFilename, $liftRangeFiles)) {
                    // Haven't parsed the .lift-ranges file yet. We'll assume it is alongside the .lift file.
                    $rangeFilePath = $liftFolderPath . "/" . $rangeFilename;
                    if (file_exists($rangeFilePath)) {
                        $sxeNode = simplexml_load_file($rangeFilePath);
                        $parsedRanges = $liftRangeDecoder->decode($sxeNode);
                        $liftRanges = array_merge($liftRanges, $parsedRanges);
                        $liftRangeFiles[] = $rangeFilename;
                    } else {
                        // Range file was NOT found in alongside the .lift file
                        $rangeImportNodeError->addRangeFileNotFound(basename($liftFilePath));
                    }
                }

                // pull out the referenced range
                if (isset($liftRanges[$rangeId])) {
                    $range = $liftRanges[$rangeId];
                } else {
                    $range = null;
                    if (file_exists($rangeFilePath)) {
                        // Range was NOT found in referenced .lift-ranges file after parsing it
                        $rangeImportNodeError->addRangeNotFound($rangeId);
                    }
                }

                // Range elements defined in LIFT file override any values defined in .lift-ranges file.
                if ($node->hasChildNodes()) {
                    $rangeNode = self::domNode_to_sxeNode($node);
                    $range = $liftRangeDecoder->readRange($rangeNode, $range);
                    $liftRanges[$rangeId] = $range;
                }

                $this->liftImportNodeError->addSubnodeError($rangeImportNodeError);
            }

            // Read the custom 'fields' spec in the header of the LIFT file
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->localName == 'fields') {
                $isInFieldsSectionOfLift = true;
                $this->liftDecoder->liftFields = array();
                while ($isInFieldsSectionOfLift && $reader->read()) {
                    if ($reader->nodeType == \XMLReader::ELEMENT && $reader->localName == 'field') {
                        $node = $reader->expand();
                        $sxeNode = self::domNode_to_sxeNode($node);
                        $LiftFieldTag = (string) $sxeNode['tag'];
                        $liftField = array();
                        foreach ($sxeNode as $element) {
                            if ($element->getName() === 'form') {
                        	    $inputSystemTag = (string) $element['lang'];
                        	    $liftField[$inputSystemTag] = (string) $element->text;
                            }
                        }
                        $this->liftDecoder->liftFields[$LiftFieldTag] = $liftField;
                    } elseif ($reader->nodeType == \XMLReader::END_ELEMENT && $reader->localName == 'fields') {
                        $isInFieldsSectionOfLift = false;
                    }
                }
            }

            // Read an entry node
            if ($reader->nodeType == \XMLReader::ELEMENT && $reader->localName == 'entry') {
                $this->stats->importEntries++;
                $node = $reader->expand();
                $sxeNode = self::domNode_to_sxeNode($node);

                $guid = $reader->getAttribute('guid');
                $existingEntry = $entryList->searchEntriesFor('guid', $guid);
                if ($existingEntry) {
                    $entry = new LexEntryModel($projectModel, $existingEntry['id']);
                    $dateModified = $reader->getAttribute('dateModified');
                    if (self::differentModTime($dateModified, $entry->authorInfo->modifiedDate) || ! $skipSameModTime) {
                        if ($mergeRule == LiftMergeRule::CREATE_DUPLICATES) {
                            $entry = new LexEntryModel($projectModel);
                            $this->readEntryWithErrorReport($sxeNode, $entry, $mergeRule);
                            $entry->guid = '';
                            $entry->write();
                            $this->stats->entriesDuplicated++;
                        } else {
                            if (isset($sxeNode->{'lexical-unit'})) {
                                $this->readEntryWithErrorReport($sxeNode, $entry, $mergeRule);
                                $entry->write();
                                $this->stats->entriesMerged++;
                            } elseif (isset($sxeNode->attributes()->dateDeleted) && $deleteMatchingEntry) {
                                LexEntryModel::remove($projectModel, $existingEntry['id']);
                                $this->stats->entriesDeleted++;
                            }
                        }
                    } else {
                        // skip because same mod time and skip enabled
                        if (! isset($sxeNode->{'lexical-unit'}) && isset($sxeNode->attributes()->dateDeleted) && $deleteMatchingEntry) {
                            LexEntryModel::remove($projectModel, $existingEntry['id']);
                            $this->stats->entriesDeleted++;
                        }
                    }
                } else {
                    if (isset($sxeNode->{'lexical-unit'})) {
                        $entry = new LexEntryModel($projectModel);
                        $this->readEntryWithErrorReport($sxeNode, $entry, $mergeRule);
                        $entry->write();
                        $this->stats->newEntries++;
                    }
                }
            }
        }

        $reader->close();

        // put back saved input systems if none found in the imported data
        if (! $hasExistingData && $projectModel->inputSystems->count() <= 0) {
            $projectModel->inputSystems->exchangeArray($savedInputSystems);
        }

        // add lift ranges
        if ($mergeRule != LiftMergeRule::IMPORT_LOSES) {
            foreach ($liftRanges as $liftRangeCode => $liftRange) {
                // add everything except semantic domains
                if (strpos($liftRangeCode, 'semantic-domain') === false) {
                    self::rangeToOptionList($projectModel, $liftRangeCode, LexiconConfigObj::flexOptionlistName($liftRangeCode), $liftRange);
                }
            }
        }

        $this->report->nodeErrors[] = $this->liftImportNodeError;
        if ($this->report->hasError()) {
            error_log($this->report->toString());
        }

        return $this;
    }

    /**
     * @param string $importDateModified
     * @param DateTime $entryDateModified
     * @return boolean
     */
    private static function differentModTime($importDateModified, $entryDateModified)
    {
        $dateModified = new \DateTime($importDateModified);

        return ($dateModified->getTimestamp() != $entryDateModified->getTimestamp());
    }

    /**
     * Read LIFT entry with error reporting
     *
     * @param SimpleXMLElement $sxeNode
     * @param LexEntryModel $entry
     * @param LiftMergeRule $mergeRule
     */
    private function readEntryWithErrorReport($sxeNode, $entry, $mergeRule = LiftMergeRule::CREATE_DUPLICATES) {
        try {
            $this->liftDecoder->readEntry($sxeNode, $entry, $mergeRule);
            $this->liftImportNodeError->addSubnodeError($this->liftDecoder->getImportNodeError());
        } catch (Exception $e) {
            $this->liftImportNodeError->addSubnodeError($this->liftDecoder->getImportNodeError());
            $this->report->nodeErrors[] = $this->liftImportNodeError;
            if ($this->report->hasError()) {
                error_log($this->report->toString());
            }
            throw new \Exception($e);
        }
    }

    /**
     * Get LIFT import error report
     *
     * @return \models\languageforge\lexicon\ImportErrorReport
     */
    public function getReport()
    {
        return  $this->report;
    }

    /**
     * Convert a LIFT range to an option list of the right code
     * Usage example: rangeToOptionList($projectModel, 'grammatical-info', 'Part of Speech', $liftRanges['grammatical-info'])
     * @param LexiconProjectModel $projectModel
     * @param string $optionListCode
     * @param string $optionListName
     * @param LiftRange $liftRange
     * @param string $interfaceLang
     */
    public static function rangeToOptionList($projectModel, $optionListCode, $optionListName, $liftRange, $interfaceLang = 'en')
    {
        $optionList = new LexOptionListModel($projectModel);
        $optionList->readByProperty('code', $optionListCode);
        $optionList->code = $optionListCode;
        $optionList->name = $optionListName;
        $optionList->canDelete = false;

        // start with an empty list
        $optionList->items->exchangeArray(array());

        foreach ($liftRange->rangeElements as $id => $elem) {
            $label = $elem->label[$interfaceLang]->value;
            if (isset($elem->abbrev)) {
                $abbrev = $elem->abbrev[$interfaceLang]->value;
            } else {
                $abbrev = null;
            }
            $optionListItem = new LexiconOptionListItem($label, $id);
            $optionListItem->abbreviation = $abbrev;
            $optionList->items->append($optionListItem);

        }
        $optionList->write();
    }

    /**
     *
     * @param string $zipFilePath
     * @param LexiconProjectModel $projectModel
     * @param LiftMergeRule $mergeRule
     * @param boolean $skipSameModTime
     * @param boolean $deleteMatchingEntry
     * @throws \Exception
     * @return \models\languageforge\lexicon\LiftImport
     */
    public function importZip($zipFilePath, $projectModel, $mergeRule = LiftMergeRule::IMPORT_WINS, $skipSameModTime = false, $deleteMatchingEntry = false)
    {
        $assetsFolderPath = $projectModel->getAssetsFolderPath();
        $extractFolderPath = $assetsFolderPath . '/initialUpload_' . mt_rand();
        $this->report = new ImportErrorReport();
        $zipNodeError = new ZipImportNodeError(ZipImportNodeError::FILE, basename($zipFilePath));
        try {
            self::extractZip($zipFilePath, $extractFolderPath);

            // Now find the .lift file in the uploaded zip
            $dirIter = new \RecursiveDirectoryIterator($extractFolderPath);
            $iterIter = new \RecursiveIteratorIterator($dirIter);
            $liftIter = new \RegexIterator($iterIter, '/\.lift$/', \RegexIterator::MATCH);
            $liftFilePaths = array();
            foreach ($liftIter as $file) {
                $liftFilePaths[] = $file->getPathname();
            }
            if (empty($liftFilePaths)) {
                throw new \Exception("Uploaded file does not contain any LIFT data");
            }
            if (count($liftFilePaths) > 1) {
                foreach (array_slice($liftFilePaths, 1) as $filename) {
                    $zipNodeError->addUnhandledLiftFile(basename($filename));
                }
            }

            // Import subfolders
            foreach (glob($extractFolderPath . '/*', GLOB_ONLYDIR) as $folderPath) {
                $folderName = basename($folderPath);
                switch ($folderName) {
                	case 'pictures':
                	case 'audio':
                	case 'others':
                	case 'WritingSystems':
                	    $assetsPath = $assetsFolderPath . "/" . $folderName;
                	    if (file_exists($folderPath) && is_dir($folderPath)) {
                            FileUtilities::copyDirTree($folderPath, $assetsPath);
                        }
                        break;
                	default:
                	    $zipNodeError->addUnhandledSubfolder($folderName);
                }
            }

            // Import first .lift file (only).
            $this->liftFilePath = $liftFilePaths[0];
            $this->merge($this->liftFilePath, $projectModel, $mergeRule, $skipSameModTime, $deleteMatchingEntry);

            if ($zipNodeError->hasError()) {
                error_log($zipNodeError->toString() . "\n");
            }
            foreach ($this->report->nodeErrors as $subnodeError) {
                $zipNodeError->addSubnodeError($subnodeError);
            }
            $this->report = new ImportErrorReport();
            $this->report->nodeErrors[] = $zipNodeError;
        } catch (Exception $e) {
            if ($zipNodeError->hasError()) {
                error_log($zipNodeError->toString() . "\n");
            }
            foreach ($this->report->nodeErrors as $subnodeError) {
                $zipNodeError->addSubnodeError($subnodeError);
            }
            $this->report = new ImportErrorReport();
            $this->report->nodeErrors[] = $zipNodeError;
            throw new \Exception($e);
        }

        return $this;
    }

    /**
     * @param string $zipFilePath
     * @param string $destDir
     * @throws \Exception
     */
    public static function extractZip($zipFilePath, $destDir) {
        // Use absolute path for archive file
        $realpathResult = realpath($zipFilePath);
        if ($realpathResult) {
            $zipFilePath = $realpathResult;
        } else {
            throw new \Exception("Error receiving uploaded file");
        }
        if (!file_exists($realpathResult)) {
            throw new \Exception("Error file '$zipFilePath' does not exist.");
        }

        $basename = basename($zipFilePath);
        $pathinfo = pathinfo($basename);
        $extension_1 = isset($pathinfo['extension']) ? $pathinfo['extension'] : 'NOEXT';
        // Handle .tar.gz, .tar.bz2, etc. by checking if there's another extension "inside" the first one
        $basename_without_ext = $pathinfo['filename'];
        $pathinfo = pathinfo($basename_without_ext);
        $extension_2 = isset($pathinfo['extension']) ? $pathinfo['extension'] : 'NOEXT';
        // $extension_2 will be 'tar' if the file was a .tar.gz, .tar.bz2, etc.
        if ($extension_2 == "tar") {
            // We don't handle tarball formats... yet.
            throw new \Exception("Sorry, the ." . $extension_2 . "." . $extension_1 . " format isn't allowed");
        }
        switch ($extension_1) {
            case "zip":
                $cmd = 'unzip ' . escapeshellarg($zipFilePath) . " -d " . escapeshellarg($destDir);
                break;
            case "zipx":
            case "7z":
                $cmd = '7z x ' . escapeshellarg($zipFilePath) . " -o" . escapeshellarg($destDir);
                break;
            default:
                throw new \Exception("Sorry, the ." . $extension_1 . " format isn't allowed");
                break;
        }

        FileUtilities::createAllFolders($destDir);
        $destFilesBeforeUnpacking = scandir($destDir);

        // ensure non-roman filesnames are returned
        $cmd = 'LANG="en_US.UTF-8" ' . $cmd;
        $output = array();
        $retcode = 0;
        exec($cmd, $output, $retcode);
        if ($retcode) {
            if (($retcode != 1) || ($retcode == 1 && strstr(end($output), 'failed setting times/attribs') == false)) {
                throw new \Exception("Uncompressing archive file failed: " . print_r($output, true));
            }
        }

        // If the .zip contained just one top-level folder with all contents below that folder,
        // "promote" the contents up one level so that $destDir contains all the .zip's contents.
        $destFilesAfterUnpacking = scandir($destDir);
        if (count($destFilesAfterUnpacking) == count($destFilesBeforeUnpacking)+1) {
            $diff = array_values(array_diff($destFilesAfterUnpacking, $destFilesBeforeUnpacking));
            $zipTopLevel = $diff[0];
            if (is_dir($destDir . "/" . $zipTopLevel)) {
                FileUtilities::promoteDirContents($destDir . "/" . $zipTopLevel);
            }
        }

    }

    /**
     * Convert a DOMNode to an SXE node -- simplexml_import_node() won't actually work
     *
     * @param DOMNode $node
     * @return SimpleXMLElement
     */
    private static function domNode_to_sxeNode($node)
    {
        $dom = new \DomDocument();
        $n = $dom->importNode($node, true);
        $sxeNode = simplexml_import_dom($n);
        $dom->appendChild($n);
        return $sxeNode;
    }

    /**
     * validate the lift data
     * @param string $xml
     * @throws \Exception
     * @return boolean
     */
    public static function validate($xml)
    {
        $reader = new \XMLReader();
        $reader->XML($xml);

        // validate LIFT
        set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontext) {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }

            $validationErrorIndex = strpos($errstr, 'XMLReader::next(): ');
            if ($validationErrorIndex !== false) {
                $errMsg = substr($errstr, $validationErrorIndex + 19);
                throw new \Exception("Sorry, the selected LIFT file is invalid: $errMsg");
            } else {
                return true;    // use the default handler
            }
        });
        $reader->setRelaxNGSchema(APPPATH . "vendor/lift/lift-0.13.rng");
        while ($reader->next()) {}    // read the entire file to validate all
        restore_error_handler();

        return true;
    }
}
