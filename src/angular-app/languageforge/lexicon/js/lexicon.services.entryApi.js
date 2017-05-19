'use strict';

angular.module('lexicon.services')

// Lexicon Entry Service
.service('lexEntryApiService', ['jsonRpc', 'sessionService', function (jsonRpc, ss) {

  var project = ss.session.project;
  jsonRpc.connect({
    url: '/api/sf',
    projectId: project ? project.id : undefined
  });

  this.update = function updateEntry(entry, callback) {
    jsonRpc.call('lex_entry_update', [entry], callback);
  };

  this.remove = function (id, callback) {
    jsonRpc.call('lex_entry_remove', [id], callback);
  };

  this.dbeDtoFull = function dbeDtoFull(browserId, offset, callback) {
    jsonRpc.call('lex_dbeDtoFull', [browserId, offset], callback);
  };

  this.dbeDtoUpdatesOnly = function dbeDtoUpdatesOnly(browserId, timestamp, callback) {
    if (timestamp) {
      jsonRpc.call('lex_dbeDtoUpdatesOnly', [browserId, timestamp], callback);
    } else {
      jsonRpc.call('lex_dbeDtoUpdatesOnly', [browserId], callback);
    }
  };

}])

;
