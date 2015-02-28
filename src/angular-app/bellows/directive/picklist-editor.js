'use strict';

angular.module('palaso.ui.picklistEditor', ['angular-sortable-view'])
.directive('onEnter', function() {
  return function(scope, elem, attrs) {
    elem.bind('keydown keypress', function(evt) {
      if (evt.which == 13) {
        scope.$apply(function() {
          scope.$eval(attrs.onEnter, {thisElement: elem, event: evt});
        });
        evt.preventDefault();
      }
    });
  };
})
// see http://stackoverflow.com/questions/17089090/prevent-input-from-setting-form-dirty-angularjs
.directive('noDirtyCheck', function() {
  // Interacting with input elements having this directive won't cause the
  // form to be marked dirty.
  return {
    restrict: 'A',
    require: 'ngModel',
    link: function(scope, elm, attrs, ctrl) {
      elm.focus(function() {
          ctrl.$pristine = false;
      });
    }  
  }
})
.directive('picklistEditor', function() {
  return {
    restrict: 'AE',
    templateUrl: '/angular-app/bellows/directive/picklist-editor.html',
    scope: {
      items: '=',
      defaultKey: '=?',
      //keyFunc: '&',  // TODO: Figure out how to let the user *optionally* specify a key function. 2014-06 RM
    },
    controller: ['$scope', function($scope) {
      $scope.defaultKeyFunc = function(value) {
        return value.replace(/ /gi, '_');
      }
      $scope.pickAddItem = function() {
        if ($scope.newValue) {
          var keyFunc = $scope.keyFunc || $scope.defaultKeyFunc;
          var key = keyFunc($scope.newValue);
          $scope.items.push({key: key, value: $scope.newValue});
          $scope.newValue = undefined;
        }
      };
      $scope.pickRemoveItem = function(index) {
        $scope.items.splice(index, 1);
      };
      $scope.blur = function(elem) {
        elem.blur();
      };
    }],
  };
});
