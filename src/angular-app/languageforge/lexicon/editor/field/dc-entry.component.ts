angular.module('palaso.ui.dc.entry', ['palaso.ui.dc.fieldrepeat', 'palaso.ui.dc.sense'])

  // Palaso UI Dictionary Control: Entry
  .directive('dcEntry', ['lexUtils', 'modalService', function (utils, modal) {
    return {
      restrict: 'E',
      templateUrl: '/angular-app/languageforge/lexicon/editor/field/dc-entry.component.html',
      scope: {
        config: '=',
        model: '=',
        control: '='
      },
      controller: ['$scope', '$state', 'lexRightsService',
      function ($scope, $state, rightsService) {
        $scope.$state = $state;
        $scope.contextGuid = '';
        $scope.fieldName = 'entry';

        rightsService.getRights().then(function (rights) {
          $scope.rights = rights;
        });

        $scope.addSense = function ($position) {
          var newSense = {};
          $scope.control.makeValidModelRecursive($scope.config.fields.senses, newSense, 'examples');
          if ($position === 0) {
            $scope.model.senses.unshift(newSense);
          } else {
            $scope.model.senses.push(newSense);
          }

          $scope.control.hideCommentsPanel();
        };

        $scope.deleteSense = function (index) {
          var deletemsg = "Are you sure you want to delete the meaning <b>' " +
            utils.constructor.getMeaning($scope.config, $scope.config.fields.senses, $scope.model.senses[index]) +
            " '</b>";
          modal.showModalSimple('Delete Meaning', deletemsg, 'Cancel', 'Delete Meaning')
            .then(function () {
              $scope.model.senses.splice(index, 1);
              $scope.control.saveCurrentEntry();
              $scope.control.hideCommentsPanel();
            }, angular.noop);
        };

        $scope.deleteEntry = function () {
          $scope.control.deleteEntry($scope.control.currentEntry);
        };

        angular.forEach($scope.control.config.entry.fields, function (field) {
          field.senseLabel = 'Entry';
        });
      }]
    };
  }])

  ;
