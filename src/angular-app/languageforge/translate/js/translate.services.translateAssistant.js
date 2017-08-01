'use strict';

angular.module('translate.services')
  .service('translateAssistant', [function () {
    var engine;
    var session;

    // SIL.Machine.Translation.TranslationEngine.ctor(baseUrl, projectId)
    this.initialise = function initialise(projectId) {
      engine = new SIL.Machine.Translation.TranslationEngine(location.origin + '/machine',
        projectId);
    };

    // SIL.Machine.Translation.TranslationEngine.translateInteractively(sourceSegment,
    //    confidenceThreshold, onFinished)
    this.translateInteractively = function translateInteractively(sourceSegment,
                                                                  confidenceThreshold, callback) {
      if (angular.isUndefined(engine)) return;

      engine.translateInteractively(sourceSegment, confidenceThreshold, function (newSession) {
        if (newSession) {
          session = newSession;
        }

        (callback || angular.noop)();
      });
    };

    // SIL.Machine.Translation.TranslationEngine.train(onStatusUpdate, onFinished)
    this.train = function train(onStatusUpdate, onFinished) {
      if (angular.isUndefined(engine)) return;

      engine.train(onStatusUpdate, onFinished);
    };

    // SIL.Machine.Translation.TranslationEngine.listenForTrainingStatus(onStatusUpdate, onFinished)
    this.listenForTrainingStatus = function listenForTrainingStatus(onStatusUpdate, onFinished) {
      if (angular.isUndefined(engine)) return;

      engine.listenForTrainingStatus(onStatusUpdate, onFinished);
    };

    // SIL.Machine.Translation.InteractiveTranslationSession.updatePrefix(prefix)
    this.updatePrefix = function updatePrefix(prefix) {
      if (angular.isUndefined(engine) || angular.isUndefined(session)) return;

      // returns suggestions
      return session.updatePrefix(prefix);
    };

    this.getCurrentSuggestion = function getCurrentSuggestion() {
      if (angular.isUndefined(engine) || angular.isUndefined(session)) return;

      return session.getCurrentSuggestion();
    };

    // SIL.Machine.Translation.InteractiveTranslationSession.approve(onFinished)
    this.learnSegment = function learnSegment(callback) {
      if (angular.isUndefined(engine) || angular.isUndefined(session)) return;

      session.approve(callback);
    };

  }])

  ;
