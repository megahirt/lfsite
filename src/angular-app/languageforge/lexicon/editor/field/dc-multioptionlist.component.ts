'use strict';

angular.module('palaso.ui.dc.multioptionlist', [])

// Palaso UI Multioptionlist
.directive('dcMultioptionlist', [function () {
  return {
    restrict: 'E',
    templateUrl: '/angular-app/languageforge/lexicon/editor/field/dc-multioptionlist.component.html',
    scope: {
      config: '=',
      model: '=',
      control: '=',
      items: '=',
      selectField: '&',
      fieldName: '='
    },
    controller: ['$scope', '$state', 'lexRightsService', function ($scope, $state, rightsService) {
      $scope.$state = $state;
      $scope.isAdding = false;
      $scope.valueToBeDeleted = '';
      $scope.contextGuid = $scope.$parent.contextGuid;

      rightsService.getRights().then(function(rights) {
        $scope.rights = rights;

        $scope.showDeleteButton = function showDeleteButton(valueToBeDeleted, value) {
          if (angular.isDefined($scope.items) && $state.is('editor.entry') && rights.canEditEntry()) {
            return valueToBeDeleted == value;
          }

          return false;
        };
      });

      $scope.getDisplayName = function getDisplayName(value) {
        var displayName = value;
        if (angular.isDefined($scope.items)) {
          for (var i = 0; i < $scope.items.length; i++) {
            if ($scope.items[i].key == value) {
              displayName = $scope.items[i].value;
              break;
            }
          }
        }

        return displayName;
      };

      $scope.orderItemsByListOrder = function orderItemsByListOrder(value) {
        if (angular.isDefined($scope.items)) {
          return $scope.items.map(function (i) {return i.value;}).indexOf(value);
        }

        return -1;
      };

      $scope.filterSelectedItems = function filterSelectedItems(item) {
        return $scope.model.values.indexOf(item.value) == -1;
      };

      $scope.showAddButton = function showAddButton() {
        return angular.isDefined($scope.items) && !$scope.isAdding &&
          $scope.model.values.length < $scope.items.length;
      };

      $scope.addValue = function addValue() {
        if (angular.isDefined($scope.newValue)) {
          $scope.model.values.push($scope.newValue);
        }

        $scope.newValue = '';
        $scope.isAdding = false;
      };

      $scope.deleteValue = function deleteValue(value) {
        var index = $scope.model.values.indexOf(value);
        $scope.model.values.splice(index, 1);
      };

      $scope.selectValue = function selectValue(value) {
        $scope.selectField({
          inputSystem: '',
          multioptionValue: $scope.getDisplayName(value)
        });
      };

    }]
  };
}]);
