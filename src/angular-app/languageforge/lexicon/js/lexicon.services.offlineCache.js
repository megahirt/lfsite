'use strict';

angular.module('lexicon.services')
/**
 * implements an offline cache storage system
 */
  .factory('lexiconOfflineCache', ['$window', '$q', 'sessionService', 'offlineCache',
    'offlineCacheUtils',
  function ($window, $q, sessionService, offlineCache, offlineCacheUtils) {
    var projectId = sessionService.session.project.id;

    function getAllEntries() {
      return offlineCache.getAllFromStore('entries', projectId);
    }

    function deleteEntry(id) {
      return offlineCache.deleteObjectInStore('entries', id);
    }

    /**
     * @param entries - array
     * @returns {promise}
     */
    function updateEntries(entries) {
      return offlineCache.setObjectsInStore('entries', projectId, entries);
    }

    return {
      getAllEntries: getAllEntries,
      getProjectData: offlineCacheUtils.getProjectData,
      updateEntries: updateEntries,
      updateProjectData: offlineCacheUtils.updateProjectData,
      deleteEntry: deleteEntry,
      canCache: offlineCache.canCache
    };
  }]);

