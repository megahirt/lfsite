<?php

namespace models\languageforge\semdomtrans\dto;

use models\languageforge\semdomtrans\SemDomTransItemListModel;

use models\languageforge\semdomtrans\SemDomTransItemModel;

use models\languageforge\SemDomTransProjectModel;

use models\mapper\JsonEncoder;
use models\languageforge\lexicon\LexCommentListModel;
use models\languageforge\lexicon\dto\LexDbeDtoCommentsEncoder;

class SemDomTransEditDto
{
    public static function encode($projectId, $userId, $lastFetchTime = null)
    {
        $data = array();
        $project = new SemDomTransProjectModel($projectId);
        if ($project->sourceLanguageProjectId == null) {
       		$sourceProject = new SemDomTransProjectModel();
       		$sourceProject->projectCode="semdom-en-$project->semdomVersion";
       		$sourceProject->readByProperty("projectCode", $sourceProject->projectCode);
        } else {
        	$sourceProject = new SemDomTransProjectModel($project->sourceLanguageProjectId);
        }
        $items = new SemDomTransItemListModel($project, $lastFetchTime);
        $items->read();
        $targetItems = $items->entries;
        //print_r($targetItems);
        
        $sourceItemsModel = new SemDomTransItemListModel($sourceProject, $lastFetchTime);
        $sourceItemsModel->read();
        $sourceItems = $sourceItemsModel->entries;
        //print_r($sourceItems);
        $sourceItemsByKey = array();
        foreach ($sourceItems as $item) {
        	$sourceItemsByKey[$item['key']] = $item;
        }
        
        // suplement the target language data with source language values
        
        $sourceLanguageIsIncomplete = false;
        foreach ($targetItems as $i => $item) {
        	foreach ($item as $outerProp => $outerValue) {
				if (is_array($outerValue) && key_exists('translation', $outerValue)) {
        			if ($sourceItemsByKey[$item['key']][$outerProp]['translation'] != '') {
			        	$targetItems[$i][$outerProp]['source'] = $sourceItemsByKey[$item['key']][$outerProp]['translation'];
        			} else {
       					$sourceLanguageIsIncomplete = true;
        			}
        		};
        		if ($outerProp == 'searchKeys') {
        			foreach ($outerValue as $innerProp => $innerValue) {
        				if (array_key_exists($innerProp, $sourceItemsByKey[$item['key']][$outerProp]))
        				{
	        				if ($sourceItemsByKey[$item['key']][$outerProp][$innerProp]['translation'] != '') {
					        	$targetItems[$i][$outerProp][$innerProp]['source'] = $sourceItemsByKey[$item['key']][$outerProp][$innerProp]['translation'];
	        				} else {
	       						$sourceLanguageIsIncomplete = true;
	        				}
        				}
        			}
        		} else if ($outerProp == 'questions') {
        			foreach ($outerValue as $innerProp => $innerValue) {
        				if (array_key_exists($innerProp, $sourceItemsByKey[$item['key']][$outerProp])) {
	        				if ($sourceItemsByKey[$item['key']][$outerProp][$innerProp]['question']['translation'] != '') {
					        	$targetItems[$i][$outerProp][$innerProp]['question']['source'] = $sourceItemsByKey[$item['key']][$outerProp][$innerProp]['question']['translation'];
	        				} else {
	       						$sourceLanguageIsIncomplete = true;
	        				}
	        				
	        				if ($sourceItemsByKey[$item['key']][$outerProp][$innerProp]['terms']['translation'] != '') {
	        					$targetItems[$i][$outerProp][$innerProp]['terms']['source'] = $sourceItemsByKey[$item['key']][$outerProp][$innerProp]['terms']['translation'];
	        				} else {
	        					$sourceLanguageIsIncomplete = true;
	        				}
        				}
        				else {
        					$sourceLanguageIsIncomplete = true;
        				}
        			}
        		}
        	}
        }
        $data['sourceLanguageIsIncomplete'] = $sourceLanguageIsIncomplete;
        
        $commentsModel = new LexCommentListModel($project, $lastFetchTime);
        $commentsModel->readAsModels();
        $encodedModels = LexDbeDtoCommentsEncoder::encode($commentsModel);
        $data['comments'] = $encodedModels["entries"];

        if (!is_null($lastFetchTime)) {
        	/* TODO: implement deleted Items list model
            $deletedEntriesModel = new LexDeletedEntryListModel($project, $lastFetchTime);
            $deletedEntriesModel->read();
            $data['deletedEntryIds'] = array_map(function ($e) {return $e['id']; }, $deletedEntriesModel->entries);
            */

            $deletedCommentsModel = new LexDeletedCommentListModel($project, $lastFetchTime);
            $deletedCommentsModel->read();
            $data['deletedCommentIds'] = array_map(function ($c) {return $c['id']; }, $deletedCommentsModel->entries);
        }

        /* TODO: how should we sort the resulting items
        usort($entries, function ($a, $b) use ($lexemeInputSystems) {
            $lexeme1 = $a[LexiconConfigObj::LEXEME];
            $lexeme1Value = '';
            foreach ($lexemeInputSystems as $ws) {
                if (array_key_exists($ws, $lexeme1) && $lexeme1[$ws]['value'] != '') {
                    $lexeme1Value = $lexeme1[$ws]['value'];
                    // '\P{xx} matches all characters without the Unicode property XX. L is the Unicode property "letter".
                    $lexeme1Value = preg_replace('/^\P{L}+/', '', $lexeme1Value); // Strip non-letter characters from front of word for sorting
                    break;
                }
            }
            $lexeme2 = $b[LexiconConfigObj::LEXEME];
            $lexeme2Value = '';
            foreach ($lexemeInputSystems as $ws) {
                if (array_key_exists($ws, $lexeme2) && $lexeme2[$ws]['value'] != '') {
                    $lexeme2Value = $lexeme2[$ws]['value'];
                    $lexeme2Value = preg_replace('/^\P{L}+/', '', $lexeme2Value); // Strip non-letter characters from front of word for sorting
                    break;
                }
            }

            return (strtolower($lexeme1Value) > strtolower($lexeme2Value)) ? 1 : -1;
        });
        */

        $data['items'] = $targetItems;

        $data['timeOnServer'] = time(); // future use for offline syncing

        return $data;
    }
}
