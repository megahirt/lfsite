'use strict';

angular.module('bellows.services')

// Lexicon Entry Service
.factory('editorDataService', ['$q', 'sessionService', 'editorOfflineCache', 'commentsOfflineCache',
  'silNoticeService', 'lexCommentService',
function ($q, sessionService, cache, commentsCache,
          notice, commentService) {
  var entries = [];
  var visibleEntries = [];
  var entryListModifiers = {sortBy: {label: "Word", value: "lexeme"}, sortOptions: [], filterBy: "", filterOptions: [], sortDirection: "forward", filterType: "isNotEmpty"};
  var browserInstanceId = Math.floor(Math.random() * 1000000);
  var api = undefined;

  var showInitialEntries = function showInitialEntries() {
    return sortAndFilterEntries().then(function() {
      visibleEntries.length = 0; // clear out the array
      Array.prototype.push.apply(visibleEntries, entries.slice(0, 50));
    });
  };

  var showMoreEntries = function showMoreEntries() {
    var increment = 50;
    if (visibleEntries.length < entries.length) {
      var currentLength = visibleEntries.length;
      visibleEntries.length = 0;
      Array.prototype.push.apply(visibleEntries, entries.slice(0, currentLength + increment));
    }
  };

  var registerEntryApi = function registerEntryApi(a) {
    api = a;
  };

  /**
   * Called when loading the controller
   * @return promise
   */
  var loadEditorData = function loadEditorData(lexiconScope) {
    var deferred = $q.defer();
    if (entries.length == 0) { // first page load
      if (cache.canCache()) {
        notice.setLoading('Loading Dictionary');
        loadDataFromOfflineCache().then(function (projectObj) {
          if (projectObj.isComplete) {
            showInitialEntries().then(function() {
              lexiconScope.finishedLoading = true;
              notice.cancelLoading();
              refreshEditorData(projectObj.timestamp).then(function (result) {
                deferred.resolve(result);
              });
            });

          } else {
            entries = [];
            console.log('Editor: cached data was found to be incomplete. Full download started...');
            notice.setLoading('Downloading Full Dictionary.');
            notice.setPercentComplete(0);
            doFullRefresh().then(function (result) {
              deferred.resolve(result);
              notice.setPercentComplete(100);
              notice.cancelLoading();
            });
          }

        }, function () {
          // no data found in cache
          console.log('Editor: no data found in cache. Full download started...');
          notice.setLoading('Downloading Full Dictionary.');
          notice.setPercentComplete(0);
          doFullRefresh().then(function (result) {
            deferred.resolve(result);
            notice.setPercentComplete(100);
            notice.cancelLoading();
          });
        });
      } else {
        console.log('Editor: caching not enabled. Full download started...');
        notice.setLoading('Downloading Full Dictionary.');
        notice.setPercentComplete(0);
        doFullRefresh().then(function (result) {
          deferred.resolve(result);
          notice.setPercentComplete(100);
          notice.cancelLoading();
        });
      }
    } else {
      // intentionally not showing any loading message here
      refreshEditorData().then(function (result) {
        deferred.resolve(result);
      });
    }

    return deferred.promise;
  };

  function doFullRefresh(offset) {
    offset = offset || 0;
    var deferred = $q.defer();
    api.dbeDtoFull(browserInstanceId, offset, function (result) {
      if (!result.ok) {
        notice.cancelLoading();
        deferred.reject(result);
        return;
      }

      var newOffset = offset + result.data.itemCount;
      var totalCount = result.data.itemTotalCount;
      notice.setPercentComplete(parseInt(newOffset * 100 / totalCount));
      processEditorDto(result, false).then(function () {
        if (offset == 0) {
          showInitialEntries();
        }

        if (newOffset < totalCount) {
          doFullRefresh(newOffset).then(function () {
            deferred.resolve(result);
          });
        } else {
          deferred.resolve(result);
        }
      });
    });

    return deferred.promise;
  }

  /**
   * Call this after every action that requires a pull from the server
   * @param timestamp
   * @return promise
   */
  var refreshEditorData = function refreshEditorData(timestamp) {
    var deferred = $q.defer();

    // get data from the server
    if (Offline.state == 'up') {
      api.dbeDtoUpdatesOnly(browserInstanceId, timestamp, function (result) {
        processEditorDto(result, true).then(function (result) {
          if (result.data.itemCount > 0) {
            console.log("Editor: processed " + result.data.itemCount + " entries from server.");
          }
          deferred.resolve(result);
        });
      });
    } else {
      return $q.when();
    }

    return deferred.promise;
  };

  var addEntryToEntryList = function addEntryToEntryList(entry) {
    entries.unshift(entry);
  };

  var removeEntryFromLists = function removeEntryFromLists(id) {
    var iFullList = getIndexInList(id, entries);
    if (angular.isDefined(iFullList)) {
      entries.splice(iFullList, 1);
    }

    var iShowList = getIndexInList(id, visibleEntries);
    if (angular.isDefined(iShowList)) {
      visibleEntries.splice(iShowList, 1);
    }

    return cache.deleteEntry(id);
  };

  /**
   * Persists the Lexical data in the offline cache store
   */
  function storeDataInOfflineCache(data, isComplete) {
    var deferred = $q.defer();
    if (data.timeOnServer && cache.canCache()) {
      cache.updateProjectData(data.timeOnServer, data.commentsUserPlusOne, isComplete)
        .then(function () {
          cache.updateEntries(data.entries).then(function () {
            commentsCache.updateComments(data.comments).then(function () {
              deferred.resolve();
            });
          });
        });
    } else {
      deferred.reject();
    }

    return deferred.promise;
  }

  /**
   *
   * @returns {promise} which resolves to an project object containing the epoch cache timestamp
   */
  function loadDataFromOfflineCache() {
    var startTime = performance.now();
    var deferred = $q.defer();
    var endTime;
    var numOfEntries;
    cache.getAllEntries().then(function (result) {
      Array.prototype.push.apply(entries, result); // proper way to extend the array
      numOfEntries = result.length;

      if (result.length > 0) {
        commentsCache.getAllComments().then(function (result) {
          Array.prototype.push.apply(commentService.comments.items.all, result);

          cache.getProjectData().then(function (result) {
            commentService.comments.counts.userPlusOne = result.commentsUserPlusOne;
            endTime = performance.now();
            console.log('Editor: Loaded ' + numOfEntries + ' entries from cache in ' +
              ((endTime - startTime) / 1000).toFixed(2) + ' seconds');
            deferred.resolve(result);

          }, function () { deferred.reject(); });
        }, function () { deferred.reject(); });
      } else {
        // we got zero entries
        deferred.reject();
      }

    }, function () { deferred.reject(); });

    return deferred.promise;
  }

  function processEditorDto(result, updateOnly) {
    var deferred = $q.defer();
    var isLastRequest = true;
    if (result.ok) {
      commentService.comments.counts.userPlusOne = result.data.commentsUserPlusOne;
      if (!updateOnly) {
        Array.prototype.push.apply(entries, result.data.entries); // proper way to extend the array
        commentService.comments.items.all.push
          .apply(commentService.comments.items.all, result.data.comments);
      } else {

        // splice updates into entry lists
        angular.forEach(result.data.entries, function (entry) {
          var i;

          // splice into entries
          i = getIndexInList(entry.id, entries);
          if (angular.isDefined(i)) {
            entries[i] = entry;
          } else {
            addEntryToEntryList(entry);
          }

          // splice into visibleEntries
          i = getIndexInList(entry.id, visibleEntries);
          if (angular.isDefined(i)) {
            visibleEntries[i] = entry;
          }
        });

        // splice comment updates into comments list
        angular.forEach(result.data.comments, function (comment) {
          var i = getIndexInList(comment.id, commentService.comments.items.all);
          if (angular.isDefined(i)) {
            commentService.comments.items.all[i] = comment;
          } else {
            commentService.comments.items.all.push(comment);
          }
        });

        // remove deleted entries according to deleted ids
        angular.forEach(result.data.deletedEntryIds, removeEntryFromLists);

        angular.forEach(result.data.deletedCommentIds, commentService.removeCommentFromLists);

        // only sort and filter the list if there have been changes to entries (or deleted entries)
        if (result.data.entries.length > 0 || result.data.deletedEntryIds.length > 0) {
          sortAndFilterEntries();
        }
      }

      if (result.data.itemCount &&
          result.data.itemCount + result.data.offset < result.data.itemTotalCount) {
        isLastRequest = false;
      }

      storeDataInOfflineCache(result.data, isLastRequest);

      commentService.updateGlobalCommentCounts();
      deferred.resolve(result);
      return deferred.promise;
    }
  }

  function getIndexInList(id, list) {
    var index = undefined;
    for (var i = 0; i < list.length; i++) {
      var e = list[i];
      if (e.id == id) {
        index = i;
        break;
      }
    }

    return index;
  }

  function getIndexInEntries(id) {
    return getIndexInList(id, entries);
  }

  function getIndexInVisibleEntries(id) {
    return getIndexInList(id, visibleEntries);
  }

  function _sortList(list, field) {
    return sessionService.getSession().then(function(session) {
      var config = session.projectSettings().config;
      var collator = Intl.Collator(_getInputSystemForSort(config));

      // temporary mapped array
      var mapped = list.map(function(entry, i) {
        return {index: i, value: getSortableValue(config, entry)};
      });

      mapped.sort(function(a, b) {
        if (entryListModifiers.sortDirection == 'reverse') {
          return collator.compare(a.value, b.value) * -1;
        } else {
          return collator.compare(a.value, b.value);
        }
      });

      var result = mapped.map(function(el) {
        return list[el.index];
      });


      return result;
    });
  }

  function sortEntries() {
    console.warn(' sort entries! ');
    var startTime = performance.now();
    return _sortList(entries).then(function(sortedEntries) {
      // the length = 0 followed by Array.push.apply is a method of replacing the contents of
      // an array without creating a new array thereby keeping original references
      // to the array
      entries.length = 0;
      Array.prototype.push.apply(entries, sortedEntries);
      return _sortList(visibleEntries).then(function(sortedVisibleEntries) {
        visibleEntries.length = 0;
        Array.prototype.push.apply(visibleEntries, sortedVisibleEntries);
        console.log('Sorted entries in ' +
          ((performance.now() - startTime) / 1000).toFixed(2) + ' seconds');
      });
    });
  }

  function filterEntries() {
    var deferred = $q.defer();
    console.warn(' filter entries! ');
    deferred.resolve(true);
    return deferred.promise;
  }

  function sortAndFilterEntries() {
    return sortEntries().then(function() {
      return filterEntries();
    });
  }

  function _getOptionListItem(optionlist, key) {
    var itemToReturn = {value: ""};
    angular.forEach(optionlist.items, function (item) {
      if (item.key == key) {
        itemToReturn = item;
      }
    });
    return itemToReturn;
  };

  function _getInputSystemForSort(config) {
    var field, inputSystem = 'en', fieldKey = entryListModifiers.sortBy.value;
    if (fieldKey in config.entry.fields) {
      field = config.entry.fields[fieldKey];
    } else if (fieldKey in config.entry.fields.senses.fields) {
      field = config.entry.fields.senses.fields[fieldKey];
    }
    if (field && field.type == 'multitext') {
      inputSystem = field.inputSystems[0];
    }
    return inputSystem;
  }

  function getSortableValue(config, entry) {
    var field, dataNode, sortableValue = '', fieldKey = entryListModifiers.sortBy.value;
    if (fieldKey in config.entry.fields && fieldKey in entry) {
      field = config.entry.fields[fieldKey];
      dataNode = entry[fieldKey];
    } else if (fieldKey in config.entry.fields.senses.fields && fieldKey in entry.senses[0]) {
      field = config.entry.fields.senses.fields[fieldKey];
      dataNode = entry.senses[0][fieldKey];
    }
    if (field) {
      if (field.type == 'multitext' && field.inputSystems[0] in dataNode) {
        sortableValue = dataNode[field.inputSystems[0]].value;
      } else if (field.type == 'optionlist') {
        if (config.optionlists) {
          // something weird here with config.optionlists not being set consistently when this is called - cjh 2017-07
          sortableValue = _getOptionListItem(config.optionlists[field.listCode], dataNode.value).value;
        } else {
          sortableValue = dataNode.value;
        }
      } else if (field.type == 'multioptionlist' && dataNode.values.length > 0) {
        if (field.listCode == 'semdom') {
          sortableValue = semanticDomains_en[dataNode.values[0]].name;
        } else {
          sortableValue = _getOptionListItem(config.optionlists[field.listCode], dataNode.values[0]).value;
        }
      }
    }
    if (!sortableValue) {
      return '[Empty]';
    }
    return sortableValue;
  };


  //noinspection JSUnusedLocalSymbols
  /**
   * A function useful for debugging (prints out to the console the lexeme values)
   * @param list
   */
  function printLexemesInList(list) {
    sessionService.getSession().then(function(session) {
      var config = session.projectSettings().config;
      var ws = config.entry.fields.lexeme.inputSystems[1];
      var arr = [];
      for (var i = 0; i < list.length; i++) {
        if (angular.isDefined(list[i].lexeme[ws])) {
          arr.push(list[i].lexeme[ws].value);
        }
      }

      console.log(arr);
    })
  }

  return {
    loadDataFromOfflineCache: loadDataFromOfflineCache,
    storeDataInOfflineCache: storeDataInOfflineCache,
    processEditorDto: processEditorDto,
    registerEntryApi: registerEntryApi,
    loadEditorData: loadEditorData,
    refreshEditorData: refreshEditorData,
    removeEntryFromLists: removeEntryFromLists,
    addEntryToEntryList: addEntryToEntryList,
    getIndexInEntries: getIndexInEntries,
    getIndexInVisibleEntries: getIndexInVisibleEntries,
    entries: entries,
    visibleEntries: visibleEntries,
    showInitialEntries: showInitialEntries,
    showMoreEntries: showMoreEntries,
    sortEntries: sortEntries,
    filterEntries: filterEntries,
    entryListModifiers: entryListModifiers,
    getSortableValue: getSortableValue
  };

}]);
