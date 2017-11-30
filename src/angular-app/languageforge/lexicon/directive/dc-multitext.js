'use strict';

angular.module('palaso.ui.dc.multitext', ['palaso.ui.showOverflow', 'palaso.ui.dc.formattedtext',
  'palaso.ui.dc.audio'])

// Dictionary Control Multitext
.directive('dcMultitext', [function () {
  return {
    restrict: 'E',
    templateUrl: '/angular-app/languageforge/lexicon/directive/dc-multitext.html',
    scope: {
      config: '=',
      model: '=',
      control: '=',
      selectField: '&'
    },
    controller: ['$scope', '$state', 'sessionService', 'lexUtils',
    function ($scope, $state, sessionService, lexUtils) {
      $scope.$state = $state;
      $scope.isAudio = lexUtils.constructor.isAudio;

      sessionService.getSession().then(function (session) {
        $scope.inputSystems = session.projectSettings().config.inputSystems;

        $scope.inputSystemDirection = function inputSystemDirection(tag) {
          if (!(tag in $scope.inputSystems)) {
            return 'ltr';
          }

          return ($scope.inputSystems[tag].isRightToLeft) ? 'rtl' : 'ltr';
        };
      });

      $scope.selectInputSystem = function selectInputSystem(tag) {
        $scope.selectField({
          inputSystem: tag
        });
      };

      $scope.modelContainsSpan = function modelContainsSpan(tag) {
        if (angular.isUndefined($scope.model) || !(tag in $scope.model)) {
          return false;
        }

        var languageSpanPattern = /<span.* lang="/;
        return languageSpanPattern.test($scope.model[tag].value);
      };

    }]
  };
}]);
