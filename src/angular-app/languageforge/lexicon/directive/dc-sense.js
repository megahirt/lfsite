'use strict';

angular.module('palaso.ui.dc.sense', ['palaso.ui.dc.fieldrepeat', 'palaso.ui.dc.example',
  'bellows.services', 'lexicon.services'])

// Palaso UI Dictionary Control: Sense
.directive('dcSense', ['lexUtils', 'modalService', function (utils, modal) {
  return {
    restrict: 'E',
    templateUrl: '/angular-app/languageforge/lexicon/directive/dc-sense.html',
    scope: {
      config: '=',
      model: '=',
      index: '=',
      remove: '=',
      control: '='
    },
    controller: ['$scope', '$state', function ($scope, $state) {
      $scope.$state = $state;

      $scope.addExample = function addExample() {
        var newExample = {};
        $scope.control.makeValidModelRecursive($scope.config.fields.examples, newExample);
        $scope.model.examples.push(newExample);
      };

      $scope.deleteExample = function deleteExample(index) {
        var deletemsg = "Are you sure you want to delete the example <b>' " +
          utils.getExample($scope.config.fields.examples, $scope.model.examples[index],
            'sentence') + " '</b>";
        modal.showModalSimple('Delete Example', deletemsg, 'Cancel', 'Delete Example')
          .then(function () {
            $scope.model.examples.splice(index, 1);
            $scope.control.saveCurrentEntry();
          });
      };
    }],

    link: function (scope, element, attrs, controller) {

    }
  };
}]);
