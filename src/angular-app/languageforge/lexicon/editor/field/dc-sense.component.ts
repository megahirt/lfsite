'use strict';

angular.module('palaso.ui.dc.sense', ['palaso.ui.dc.fieldrepeat', 'palaso.ui.dc.example'])

// Palaso UI Dictionary Control: Sense
.directive('dcSense', ['lexUtils', 'modalService', function (utils, modal) {
  return {
    restrict: 'E',
    templateUrl: '/angular-app/languageforge/lexicon/editor/field/dc-sense.component.html',
    scope: {
      config: '=',
      model: '=',
      index: '=',
      remove: '=',
      control: '='
    },
    controller: ['$scope', '$state', function ($scope, $state) {
      $scope.$state = $state;
      $scope.contextGuid = 'sense#' + $scope.model.guid;

      $scope.addExample = function addExample() {
        var newExample = {};
        $scope.control.makeValidModelRecursive($scope.config.fields.examples, newExample);
        $scope.model.examples.push(newExample);
        $scope.control.hideCommentsPanel();
      };

      $scope.deleteExample = function deleteExample(index) {
        var deletemsg = "Are you sure you want to delete the example <b>' " +
          utils.constructor.getExample($scope.config.fields.examples, $scope.model.examples[index],
            'sentence') + " '</b>";
        modal.showModalSimple('Delete Example', deletemsg, 'Cancel', 'Delete Example')
          .then(function () {
            $scope.model.examples.splice(index, 1);
            $scope.control.hideCommentsPanel();
          }, angular.noop);
      };

      angular.forEach($scope.control.config.entry.fields.senses.fields, function (field) {
        if (!angular.isDefined(field.senseLabel)) {
          field.senseLabel = [];
          field.senseLabel[-1] = 'Meaning';
        }

        field.senseLabel[$scope.index] = 'Meaning ' + ($scope.index + 1);
      });
    }]
  };
}]);
