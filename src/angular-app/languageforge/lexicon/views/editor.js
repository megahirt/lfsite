'use strict';

angular.module('lexicon.editor', ['ui.router', 'ui.bootstrap', 'bellows.services',
  'palaso.ui.dc.entry', 'palaso.ui.comments', 'palaso.ui.showOverflow', 'truncate',
  'palaso.ui.scroll', 'palaso.ui.notice', 'lexicon.services'])
  .config(['$stateProvider', function ($stateProvider) {

    // State machine from ui.router
    $stateProvider
      .state('editor', {

        // Need quotes around Javascript keywords like 'abstract' so YUI compressor won't complain
        'abstract': true, // jscs:ignore
        url: '/editor',
        templateUrl: '/angular-app/languageforge/lexicon/views/editor-abstract.html',
        controller: 'EditorCtrl'
      })
      .state('editor.list', {
        url: '/list',
        templateUrl: '/angular-app/languageforge/lexicon/views/editor-list.html',
        controller: 'EditorListCtrl'
      })
      .state('editor.entry', {
        url: '/entry/{entryId:[0-9a-z_]{6,24}}',
        templateUrl: '/angular-app/languageforge/lexicon/views/editor-entry.html',
        controller: 'EditorEntryCtrl'
      })
      .state('editor.comments', {
        url: '/entry/{entryId:[0-9a-z_]{6,24}}/comments',
        templateUrl: '/angular-app/languageforge/lexicon/views/editor-comments.html',
        controller: 'EditorCommentsCtrl'
      })
      ;
  }])
  .controller('EditorCtrl', ['$scope', 'userService', 'sessionService', 'lexEntryApiService', '$q',
    '$state', '$window', '$interval', '$filter', 'lexLinkService', 'lexUtils', 'lexRightsService',
    'silNoticeService', '$rootScope', '$location', 'lexConfigService', 'lexCommentService',
    'lexEditorDataService', 'lexProjectService', 'lexSendReceive', 'modalService',
  function ($scope, userService, sessionService, lexService, $q,
            $state, $window, $interval, $filter, linkService, utils, rightsService,
            notice, $rootScope, $location, lexConfig, commentService,
            editorService, lexProjectService, sendReceive, modal) {

    var pristineEntry = {};
    var warnOfUnsavedEditsId;

    $scope.$state = $state;
    $scope.lastSavedDate = new Date();
    $scope.currentEntry = {};
    $scope.commentService = commentService;
    $scope.editorService = editorService;
    $scope.configService = lexConfig;
    $scope.entries = editorService.entries;
    $scope.visibleEntries = editorService.visibleEntries;
    $scope.filteredEntries = editorService.filteredEntries;
    $scope.entryListModifiers = editorService.entryListModifiers;
    $scope.sortEntries = editorService.sortEntries;
    $scope.filterEntries = editorService.filterEntries;

    $scope.show = {
      more: editorService.showMoreEntries,
      emptyFields: false,
      entryListModifiers: false
    };

    // hack to pass down the parent scope down into all child directives (i.e. entry, sense, etc)
    $scope.control = $scope;

    lexConfig.refresh().then(function (config) {
      $scope.config = config;

      $scope.$watch('config', function () {
        setSortAndFilterOptionsFromConfig();
      });

      $scope.currentEntryIsDirty = function currentEntryIsDirty() {
        if (!$scope.entryLoaded()) return false;

        return !angular.equals($scope.currentEntry, pristineEntry);
      };

      function entryIsNew(entry) {
        return (entry.id && entry.id.indexOf('_new_') === 0);
      }

      /*
      // for test purposes only
      $scope.getIds = function() {
        var ids = [];
        angular.forEach($scope.visibleEntries, function(e) { ids.push(e.id); })
        return ids;
      };
      */

      // status is tri-state: unsaved, saving, saved
      var saveStatus = 'unsaved';

      $scope.saveNotice = function saveNotice() {
        switch (saveStatus) {
          case 'saving':
            return 'Saving';
          case 'saved':
            return 'Saved';
          default:
            return '';
        }
      };

      $scope.saveButtonTitle = function saveButtonTitle() {
        if ($scope.currentEntryIsDirty()) {
          return 'Save Entry';
        } else if (entryIsNew($scope.currentEntry)) {
          return 'Entry unchanged';
        } else {
          return 'Entry saved';
        }
      };

      function resetEntryLists(id, pristineEntry) {
        var entryIndex = editorService.getIndexInList(id, $scope.entries);
        var entry = prepCustomFieldsForUpdate(pristineEntry);
        if (angular.isDefined(entryIndex)) {
          $scope.entries[entryIndex] = entry;
          $scope.currentEntry = pristineEntry;
        }

        var visibleEntryIndex = editorService.getIndexInList(id, $scope.visibleEntries);
        if (angular.isDefined(visibleEntryIndex)) {
          $scope.visibleEntries[visibleEntryIndex] = entry;
        }
      }

      function warnOfUnsavedEdits(entry) {
        warnOfUnsavedEditsId = notice.push(notice.WARN, 'A synchronize has been started by ' +
          'another user. When the synchronize has finished, please check your recent edits in ' +
          'entry "' +
          $scope.getWordForDisplay(entry) + '".');
      }

      rightsService.getRights().then(function (rights) {
        $scope.rights = rights;

        $scope.saveCurrentEntry = function saveCurrentEntry(doSetEntry, successCallback,
                                                            failCallback) {
          var isNewEntry = false;
          var newEntryTempId;
          if (angular.isUndefined(doSetEntry)) {
            // doSetEntry is mainly used for when the save button is pressed,
            // that is when the user is saving the current entry and is NOT going to a
            // different entry (as is the case with editing another entry
            doSetEntry = false;
          }

          if ($scope.currentEntryIsDirty() && $scope.rights.canEditEntry()) {
            cancelAutoSaveTimer();
            sendReceive.setStateUnsynced();
            saveStatus = 'saving';
            $scope.currentEntry = normalizeStrings($scope.currentEntry);
            var entryToSave = angular.copy($scope.currentEntry);
            if (entryIsNew(entryToSave)) {
              isNewEntry = true;
              newEntryTempId = entryToSave.id;
              entryToSave.id = ''; // send empty id to indicate "create new"
            }

            return $q.all({
              entry: lexService.update(prepEntryForUpdate(entryToSave)),
              isSR: sendReceive.isSendReceiveProject()
            }).then(function (data) {
              var entry = data.entry.data;
              if (!entry && data.isSR) {
                warnOfUnsavedEdits(entryToSave);
                sendReceive.startSyncStatusTimer();
              }

              if (!entry) {
                resetEntryLists($scope.currentEntry.id, angular.copy(pristineEntry));
              }

              if (isNewEntry) {
                // note: we have to reset the show window, because we don't know
                // where the new entry will show up in the list
                // we can solve this problem by implementing a sliding "scroll
                // window" that only shows a few entries at a time (say 30?)
                editorService.showInitialEntries();
              }

              /*
              * Reviewed CP 2014-08: It seems that currently the setCurrentEntry
              * will never do anything. Currently it has the side effect of causing
              * the focus to be lost. Given that we save the entire model We will
              * never get data returned other than what we just caused to be saved.
              *
              * One day we hope to send deltas which will fix this problem and give
              * a better real time experience.
              */

              /* Reviewed CJH 2015-03: setCurrentEntry is useful in the case when the entry being
              * saved is a new entry. In this case the new entry is replaced entirely by the one
              * returned from the server (with a proper id, etc).
              * I'm currently unclear on whether the doSetEntry parameter is still necessary
              */

              if (entry) {
                pristineEntry = angular.copy(entryToSave);
                $scope.lastSavedDate = new Date();
              }

              // refresh data will add the new entry to the entries list
              editorService.refreshEditorData().then(function () {
                if (entry && isNewEntry) {
                  setCurrentEntry($scope.entries[editorService.getIndexInList(entry.id,
                    $scope.entries)]);
                  editorService.removeEntryFromLists(newEntryTempId);
                  if (doSetEntry) {
                    $state.go('.', { entryId: entry.id }, { notify: false });
                    scrollListToEntry(entry.id, 'top');
                  }
                }
              });

              saveStatus = 'saved';
              (successCallback || angular.noop)(data.result);
            }).catch(function (reason) {
              saveStatus = 'unsaved';
              (failCallback || angular.noop)(reason);
            });
          } else {
            (successCallback || angular.noop)();
          }
        };

        function normalizeStrings(obj) {
          return JSON.parse(JSON.stringify(obj).normalize());
        }

        // conditionally register watch
        if ($scope.rights.canEditEntry()) {
          $scope.$watch('currentEntry', function (newValue) {
            if (newValue !== undefined) {
              cancelAutoSaveTimer();
              if ($scope.currentEntryIsDirty()) {
                startAutoSaveTimer();
              }
            }
          }, true);
        }
      });

      function prepEntryForUpdate(entry) {
        var entryForUpdate = recursiveRemoveProperties(angular.copy(entry), ['guid', 'mercurialSha',
          'authorInfo', 'dateCreated', 'dateModified', 'liftId', '$$hashKey']);
        entryForUpdate = prepCustomFieldsForUpdate(entryForUpdate);
        return entryForUpdate;
      }

      $scope.getCompactListItemForDisplay = editorService.getSortableValue;

      $scope.getWordForDisplay = function getWordForDisplay(entry) {
        var lexeme = utils.getLexeme($scope.config.entry, entry);
        if (!lexeme) {
          return '[Empty]';
        }

        return lexeme;
      };

      $scope.lexemeAlign = function lexemeAlign(listEntry) {
        if ($scope.config && $scope.config.entry && listEntry.lexeme) {
          var inputSystem = $scope.config.entry.fields.lexeme.inputSystems[0];
          return ($scope.config.inputSystems[inputSystem].isRightToLeft) ? 'right' : 'left';
        } else {
          return 'left';
        }
      };

      $scope.getMeaningForDisplay = function getMeaningForDisplay(entry) {
        var meaning = '';
        if (entry.senses && entry.senses[0]) {
          meaning = utils.getMeaning($scope.config.entry.fields.senses, entry.senses[0]);
        }

        if (!meaning) {
          return '[Empty]';
        }

        return meaning;
      };

      $scope.definitionOrGlossAlign = function definitionOrGlossAlign(listEntry) {
        if ($scope.config && $scope.config.entry && $scope.config.entry.fields.senses) {
          var inputSystem;
          if (listEntry.definition) {
            inputSystem = $scope.config.entry.fields.senses.fields.definition.inputSystems[0];
            return ($scope.config.inputSystems[inputSystem].isRightToLeft) ? 'right' : 'left';
          } else if (listEntry.gloss) {
            inputSystem = $scope.config.entry.fields.senses.fields.gloss.inputSystems[0];
            return ($scope.config.inputSystems[inputSystem].isRightToLeft) ? 'right' : 'left';
          }
        } else {
          return 'left';
        }
      };

      $scope.navigateToLiftImport = function navigateToLiftImport() {
        $location.path('/importExport');
      };

      function _scrollDivToId(containerId, divId, posOffset) {
        var $div = $(divId);
        var $containerDiv = $(containerId);
        var foundDiv = false;
        var offsetTop;
        if (angular.isUndefined(posOffset)) {
          posOffset = 0;
        }

        // todo: refactor this spaghetti logic
        if ($div && $containerDiv) {
          if (angular.isUndefined($div.offsetTop)) {
            if (angular.isDefined($div[0])) {
              $div = $div[0];
              foundDiv = true;
            } else {
              console.log('Error: unable to scroll to div with div id ' + divId);
            }
          }

          if (foundDiv) {
            if (angular.isUndefined($div.offsetTop)) {

              offsetTop = $div.offset().top - posOffset;
            } else {
              offsetTop = $div.offsetTop - posOffset;
            }

            if (offsetTop < 0)
              offsetTop = 0;
            $containerDiv.scrollTop(offsetTop);
          }
        }
      }

      function scrollListToEntry(id, position) {
        var posOffset = (position === 'top') ? 274 : 487;
        var entryDivId = '#entryId_' + id;
        var listDivId = '#compactEntryListContainer';
        var index;

        // make sure the item is visible in the list
        // todo implement lazy "up" scrolling to make this more efficient

        // only expand the "show window" if we know that the entry is actually in
        // the entry list - a safe guard
        if (angular.isDefined(editorService.getIndexInList(id, $scope.filteredEntries))) {
          while ($scope.visibleEntries.length < $scope.filteredEntries.length) {
            index = editorService.getIndexInList(id, $scope.visibleEntries);
            if (angular.isDefined(index)) {
              break;
            }

            editorService.showMoreEntries();
          }
        } else {
          console.warn('Error: tried to scroll to an entry that is not in the entry list!');
        }

        // note: ':visible' is a JQuery invention that means 'it takes up space on
        // the page'.
        // It may actually not be visible at the moment because it may down inside a
        // scrolling div or scrolled off the view of the page
        if ($(listDivId).is(':visible') && $(entryDivId).is(':visible')) {
          _scrollDivToId(listDivId, entryDivId, posOffset);
        } else {
          // wait then try to scroll
          $interval(function () {
            _scrollDivToId(listDivId, entryDivId, posOffset);
          }, 200, 1);
        }
      }

      $scope.editEntryAndScroll = function editEntryAndScroll(id) {
        $scope.editEntry(id);
        scrollListToEntry(id, 'middle');
      };

      function setCurrentEntry(entry) {
        entry = entry || {};

        // align custom fields into model
        entry = alignCustomFieldsInData(entry);

        // auto-make a valid model but stop at the examples array
        entry = $scope.makeValidModelRecursive($scope.config.entry, entry, 'examples');

        $scope.currentEntry = entry;
        pristineEntry = angular.copy(entry);
        saveStatus = 'unsaved';
      }

      function alignCustomFieldsInData(data) {
        if (angular.isDefined(data.customFields)) {
          angular.forEach(data.customFields, function (item, key) {
            data[key] = item;
          });
        }

        if (angular.isDefined(data.senses)) {
          angular.forEach(data.senses, function (sense) {
            alignCustomFieldsInData(sense);
          });
        }

        if (angular.isDefined(data.examples)) {
          angular.forEach(data.examples, function (example) {
            alignCustomFieldsInData(example);
          });
        }

        return data;
      }

      function prepCustomFieldsForUpdate(data) {
        data.customFields = {};
        angular.forEach(data, function (item, key) {
          if (/^customField_/.test(key)) {
            data.customFields[key] = item;
          }

          if (key === 'senses' || key === 'examples') {
            data[key] = prepCustomFieldsForUpdate(item);
          }
        });

        return data;

      }

      $scope.editEntry = function editEntry(id) {
        if ($scope.currentEntry.id !== id) {
          $scope.saveCurrentEntry();
          setCurrentEntry($scope.entries[editorService.getIndexInList(id, $scope.entries)]);
          commentService.loadEntryComments(id);
        }

        if ($state.is('editor.entry')) {
          $state.go('.', { entryId: id }, { notify: false });
        } else {
          $state.go('editor.entry', { entryId: id });
        }
      };

      $scope.newEntry = function newEntry() {
        $scope.saveCurrentEntry(false, function () {
          var d = new Date();
          var uniqueId = '_new_' + d.getSeconds() + d.getMilliseconds();
          var newEntry = {
            id: uniqueId
          };
          setCurrentEntry(newEntry);
          commentService.loadEntryComments(newEntry.id);
          editorService.addEntryToEntryList(newEntry);
          editorService.showInitialEntries().then(function () {
            scrollListToEntry(newEntry.id, 'top');
          });

          if ($state.is('editor.entry')) {
            $state.go('.', { entryId: newEntry.id }, { notify: false });
          } else {
            $state.go('editor.entry', { entryId: newEntry.id });
          }
        });
      };

      $scope.entryLoaded = function entryLoaded() {
        return angular.isDefined($scope.currentEntry.id);
      };

      $scope.returnToList = function returnToList() {
        $scope.saveCurrentEntry();
        setCurrentEntry();
        $state.go('editor.list');
      };

      $scope.makeValidModelRecursive = function makeValidModelRecursive(config, data, stopAtNodes) {
        if (!data) data = {};

        if (angular.isString(stopAtNodes)) {
          var node = stopAtNodes;
          stopAtNodes = [];
          stopAtNodes.push(node);
        } else if (!angular.isArray(stopAtNodes)) {
          stopAtNodes = [];
        }

        switch (config.type) {
          case 'fields':
            angular.forEach(config.fieldOrder, function (fieldName) {
              if (angular.isUndefined(data[fieldName])) {
                if (config.fields[fieldName].type === 'fields' ||
                    config.fields[fieldName].type === 'pictures') {
                  data[fieldName] = [];
                } else {
                  data[fieldName] = {};
                }
              }

              // only recurse if the field is not in our node stop list or if it contains data
              if (stopAtNodes.indexOf(fieldName) === -1 || data[fieldName].length !== 0) {
                if (config.fields[fieldName].type === 'fields') {
                  if (data[fieldName].length === 0) {
                    data[fieldName].push({});
                  }

                  for (var i = 0; i < data[fieldName].length; i++) {
                    data[fieldName][i] =
                      $scope.makeValidModelRecursive(config.fields[fieldName],
                        data[fieldName][i], stopAtNodes);
                  }
                } else {
                  data[fieldName] = $scope.makeValidModelRecursive(config.fields[fieldName],
                    data[fieldName], stopAtNodes);
                }
              }
            });

            break;
          case 'multitext':

            // when a multitext is completely empty for a field, and sent down the
            // wire, it will come as a [] because of the way
            // that the PHP JSON default encode works. We change this to be {} for
            // an empty multitext
            if (angular.isArray(data)) {
              data = {};
            }

            angular.forEach(config.inputSystems, function (ws) {
              if (angular.isUndefined(data[ws])) {
                data[ws] = {
                  value: ''
                };
              }
            });

            break;
          case 'optionlist':
            if (angular.isUndefined(data.value) || data.value === null) {
              data.value = '';
              if (angular.isDefined($scope.config.optionlists) &&
                  angular.isDefined(config.listCode) &&
                  (config.listCode in $scope.config.optionlists) &&
                  angular.isDefined($scope.config.optionlists[config.listCode].defaultItemKey)) {
                data.value = $scope.config.optionlists[config.listCode].defaultItemKey;
              }
            }

            break;
          case 'multioptionlist':
            if (angular.isUndefined(data.values)) {
              data.values = [];
            }

            break;
          case 'pictures':
            var captionConfig = angular.copy(config);
            captionConfig.type = 'multitext';
            if (angular.isUndefined(data)) {
              data = [];
            }

            angular.forEach(data, function (picture) {
              if (angular.isUndefined(picture.caption)) {
                picture.caption = {};
              }

              picture.caption = $scope.makeValidModelRecursive(captionConfig, picture.caption);
            });

            break;
          case 'multiparagraph':
            if (angular.isUndefined(data.type)) {
              data.type = 'multiparagraph';
            }

            if (angular.isUndefined(data.paragraphsHtml)) {
              data.paragraphsHtml = '';
            }

            break;
        }

        // console.log('end data: ', data);
        return data;
      };

      $scope.deleteEntry = function deleteEntry(entry) {
        var deletemsg = 'Are you sure you want to delete the entry <b>\' ' +
          utils.getLexeme($scope.config.entry, entry) + ' \'</b>';

        // var deletemsg = $filter('translate')("Are you sure you want to delete '{lexeme}'?",
        // {lexeme:utils.getLexeme($scope.config.entry, entry)});
        modal.showModalSimple('Delete Entry', deletemsg, 'Cancel', 'Delete Entry').then(
          function () {
            var iShowList = editorService.getIndexInList(entry.id, $scope.visibleEntries);
            editorService.removeEntryFromLists(entry.id);
            if ($scope.entries.length > 0) {
              if (iShowList !== 0)
                iShowList--;
              setCurrentEntry($scope.visibleEntries[iShowList]);
              $state.go('.', { entryId: $scope.visibleEntries[iShowList].id }, { notify: false });
            } else {
              $scope.returnToList();
            }

            if (!entryIsNew(entry)) {
              sendReceive.setStateUnsynced();
              lexService.remove(entry.id, function () {
                editorService.refreshEditorData();
              });
            }
          });
      };

      $scope.getCompactItemListOverlay = function getCompactItemListOverlay(entry) {
        var title;
        var subtitle;
        title = $scope.getWordForDisplay(entry);
        subtitle = $scope.getMeaningForDisplay(entry);
        if (title.length > 19 || subtitle.length > 25) {
          return title + '         ' + subtitle;
        } else {
          return '';
        }
      };

      function evaluateState(skipLoadingEditorData) {
        function goToState() {
          // if entry not found goto first visible entry
          var entryId = $state.params.entryId;
          if (angular.isUndefined(editorService.getIndexInList(entryId, $scope.entries))) {
            entryId = '';
            if (angular.isDefined($scope.visibleEntries[0])) {
              entryId = $scope.visibleEntries[0].id;
            }
          }

          if ($state.is('editor.comments')) {
            $scope.editEntryAndScroll(entryId);
            $scope.showComments();
          }

          if ($state.is('editor.entry')) {
            $scope.editEntryAndScroll(entryId);
          }
        }

        if (skipLoadingEditorData) {
          goToState();
        } else {
          editorService.loadEditorData().then(goToState);
        }
      }

      // watch for when data has been loaded completely, then evaluate state
      $scope.$watch('finishedLoading', function (newVal) {
        if (newVal) {
          evaluateState(true);
        }
      });

      // Comments View
      $scope.showComments = function showComments() {
        $scope.saveCurrentEntry(true);
        $state.go('editor.comments', { entryId: $scope.currentEntry.id });
      };

      sendReceive.setPollUpdateSuccessCallback(pollUpdateSuccess);
      sendReceive.setSyncProjectStatusSuccessCallback(syncProjectStatusSuccess);

      function pollUpdateSuccess() {
        if ($scope.currentEntryIsDirty()) {
          if (sendReceive.isInProgress()) {
            cancelAutoSaveTimer();
            warnOfUnsavedEdits($scope.currentEntry);
            resetEntryLists($scope.currentEntry.id, angular.copy(pristineEntry));
          }
        } else {
          setCurrentEntry($scope.entries[editorService.getIndexInList($scope.currentEntry.id,
            $scope.entries)]);
        }
      }

      function syncProjectStatusSuccess() {
        editorService.refreshEditorData().then(function () {
          setCurrentEntry($scope.entries[editorService.getIndexInList($scope.currentEntry.id,
            $scope.entries)]);
          sessionService.getSession(true).then(lexConfig.refresh);
          notice.removeById(warnOfUnsavedEditsId);
        });
      }

      var autoSaveTimer;
      function startAutoSaveTimer() {
        if (angular.isDefined(autoSaveTimer)) {
          return;
        }

        autoSaveTimer = $interval(function () {
          $scope.saveCurrentEntry(true);
        }, 5000, 1);
      }

      function cancelAutoSaveTimer() {
        if (angular.isDefined(autoSaveTimer)) {
          $interval.cancel(autoSaveTimer);
          autoSaveTimer = undefined;
        }
      }

      $scope.resetEntryListFilter = function () {
        $scope.entryListModifiers.filterBy = '';
        $scope.filterEntries(true);
      };

      function setSortAndFilterOptionsFromConfig() {
        var sortOptions = [];
        var filterOptions = [];
        angular.forEach($scope.config.entry.fieldOrder, function (entryFieldKey) {
          var entryField = $scope.config.entry.fields[entryFieldKey];

          // TODO: do I need to check if user can see field (view settings).
          // Is this handled somewhere else? - cjh 2017-07-20
          if (entryField.hideIfEmpty) return;
          if (entryFieldKey === 'senses') {
            angular.forEach($scope.config.entry.fields.senses.fieldOrder, function (senseFieldKey) {
              var senseField = $scope.config.entry.fields.senses.fields[senseFieldKey];
              if (senseField.hideIfEmpty || senseField.type === 'fields') return;
              sortOptions.push({ label: senseField.label, value: senseFieldKey });
              if (senseField.type === 'multitext') {
                angular.forEach(senseField.inputSystems, function (ws) {
                  filterOptions.push({ label: senseField.label + ' [' + ws + ']', level: 'sense',
                    value: senseFieldKey, type: 'multitext', inputSystem: ws,
                    key: senseFieldKey + '-' + ws });
                });
              } else {
                filterOptions.push({ label: senseField.label, level: 'sense', value: senseFieldKey,
                  type: senseField.type, key: senseFieldKey });
              }
            });
          } else {
            sortOptions.push({ label: entryField.label, value: entryFieldKey });
            if (entryField.type === 'multitext') {
              angular.forEach(entryField.inputSystems, function (ws) {
                filterOptions.push({ label: entryField.label + ' [' + ws + ']', level: 'entry',
                  value: entryFieldKey, type: 'multitext', inputSystem: ws,
                  key: entryFieldKey + '-' + ws });
              });
            } else {
              filterOptions.push({ label: entryField.label, level: 'entry', value: entryFieldKey,
                type: entryField.type, key: entryFieldKey });
            }
          }
        });

        filterOptions.push({ label: 'Comments', value: 'comments', type: 'comments',
          key: 'comments' });
        filterOptions.push({ label: 'Example Sentences', value: 'exampleSentences',
          type: 'exampleSentences', key: 'exampleSentences' });
        filterOptions.push({ label: 'Pictures', value: 'pictures', type: 'pictures',
          key: 'pictures' });
        var hasAudioInputSystem = false;
        angular.forEach($scope.config.inputSystems, function (inputSystem) {
          if (utils.isAudio(inputSystem.tag)) {
            hasAudioInputSystem = true;
          }
        });

        if (hasAudioInputSystem) {
          filterOptions.push({ label: 'Audio', value: 'audio', type: 'audio', key: 'audio' });
        }

        $scope.entryListModifiers.sortOptions.length = 0;
        $scope.entryListModifiers.filterOptions.length = 0;
        Array.prototype.push.apply($scope.entryListModifiers.sortOptions, sortOptions);
        Array.prototype.push.apply($scope.entryListModifiers.filterOptions, filterOptions);
      }

      $scope.$on('$destroy', function () {
        cancelAutoSaveTimer();
        $scope.saveCurrentEntry();
      });

      $scope.$on('$locationChangeStart', function (event, next, current) {
        if (~current.indexOf('#!/editor/list') && ~next.indexOf('#!/editor/list') &&
          ~next.indexOf('#!/editor/entry')
        ) {
          cancelAutoSaveTimer();
          $scope.saveCurrentEntry();
        }
      });

      function recursiveRemoveProperties(startAt, properties) {
        angular.forEach(startAt, function (value, key) {
          var deleted = false;
          angular.forEach(properties, function (propName) {
            // console.log ("key = " + key + " && propName = " + propName);
            if (!deleted && key === propName) {
              // console.log("deleted " + key + " (" + startAt[key] + ")");
              delete startAt[key];
              deleted = true;
            }
          });

          if (!deleted && angular.isObject(value)) {
            recursiveRemoveProperties(startAt[key], properties);
          }
        });

        return startAt;
      }

      // search typeahead
      $scope.typeahead = {
        term: '',
        searchResults: [],
        limit: 50,
        matchCountCaption: ''
      };

      $scope.typeahead.searchEntries = function searchEntries(query) {

        // Concatenate to get prioritized list of exact matches, then non-exact.
        // TODO: would be better to search for gloss.  DDW 2016-06-22
        var results =
            $filter('filter')($scope.entries, { lexeme: query }, true).concat(
            $filter('filter')($scope.entries, { senses: query }, true),
            $filter('filter')($scope.entries, { lexeme: query }),
            $filter('filter')($scope.entries, { senses: query }),
            $filter('filter')($scope.entries, query));

        // Set function to return unique results
        // TODO Set is not available until ES2015
        $scope.typeahead.searchResults = Array.from(new Set(results));
        $scope.typeahead.matchCountCaption = '';
        var numMatches = $scope.typeahead.searchResults.length;
        if (numMatches > $scope.typeahead.limit) {
          $scope.typeahead.matchCountCaption =
            $scope.typeahead.limit + ' of ' + numMatches + ' matches';
        } else if (numMatches > 1) {
          $scope.typeahead.matchCountCaption = numMatches + ' matches';
        } else if (numMatches === 1) {
          $scope.typeahead.matchCountCaption = numMatches + ' match';
        }
      };

      $scope.typeahead.searchSelect = function searchSelect(entry) {
        $scope.typeahead.searchItemSelected = '';
        $scope.typeahead.searchResults = [];
        if (entry.id) {
          $scope.editEntryAndScroll(entry.id);
        }
      };
    });

  }])
  .controller('EditorListCtrl', ['$scope', 'lexProjectService',
    function ($scope, lexProjectService) {
      lexProjectService.setBreadcrumbs('editor/list', 'List');
    }
  ])
  .controller('EditorEntryCtrl', ['$scope', 'lexProjectService',
    function ($scope, lexProjectService) {
      lexProjectService.setBreadcrumbs('editor/entry', 'Edit');
    }
  ])
  .controller('EditorCommentsCtrl', ['$scope', 'lexProjectService',
    function ($scope, lexProjectService) {
      lexProjectService.setBreadcrumbs('editor/entry', 'Comments');
    }
  ])

  ;
