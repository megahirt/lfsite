'use strict';
angular.module('palaso.ui.comments')

  // Palaso UI Dictionary Control: Comments
  .directive('currentEntryCommentCount', [function () {
    return {
      restrict: 'E',
      templateUrl:
        '/angular-app/bellows/directive/palaso.ui.comments.current-entry-comment-count.html',
      controller: ['$scope', 'lexCommentService', function ($scope, commentService) {
        $scope.count = commentService.comments.counts.currentEntry;
      }]
    };
  }])

;
