'use strict';

angular.module('palaso.ui.tagging', ['ui.bootstrap'])

  .directive('viewTags', [function () {
    return {
      template:
        '<div class="tag-list">' +
          '<ul>' +
            '<li ng-repeat="tag in tags" ng-mouseenter="show = true && rightsDelete()" ' +
                'ng-mouseleave="show = false">' +
              '{{tag}} <i class="fa fa-trash closeicon" ng-click="removeTag($index)"></i>' +
            '</li>' +
          '</ul>' +
        '</div>',
      restrict: 'EA',
      replace: true,
      scope: {
        tags: '=',
        onDelete: '&',
        rightsDelete: '&'
      },
      controller: ['$scope', function ($scope) {
        $scope.removeTag = function (tagIndex) {
          $scope.tags.splice(tagIndex, 1);
          ($scope.onDelete || angular.noop)();
        };
      }]
    };
  }])

  .directive('addTags', [function () {
    return {
      template: '<input type="text" class="form-control" name="inputtagstring" ' +
        'data-ng-model="inputtagstring">',
      restrict: 'EA',
      replace: true,
      scope: {
        tags: '='
      },
      controller: ['$scope', function ($scope) {
        // As the user types into the text input, the comma-separated
        // list of tags will be turned into an actual list and stored
        // in $scope.inputtaglist. Submitting the form will send those
        // tags to the client by setting $scope.tags.
        $scope.inputtagstring = '';
      }],

      link: function (scope) {
        scope.$watch('inputtagstring', function (inputtagstring) {
          if (inputtagstring) {
            var taglist = inputtagstring.split(',');
            for (var i = 0; i < taglist.length; i++) {
              taglist[i] = taglist[i].trim();

              // Guard against empty-string tags (i.e., "")
              if (!taglist[i]) {
                taglist.splice(i, 1);
                i--; // Stay on this index for next loop iteration
              }
            }

            scope.tags = taglist;
          }
        });
      }
    };
  }])

;
