'use strict';

angular.module('semdomtrans.comments', ['jsonRpc', 'ui.bootstrap', 'bellows.services',  'ngAnimate', 'palaso.ui.notice', 'semdomtrans.services', 'palaso.ui.sd.term', 'palaso.ui.scroll', 'palaso.ui.comments'])
// DBE controller
.controller('commentsCtrl', ['$scope', '$stateParams', 'sessionService', 'modalService', 'silNoticeService', 'lexCommentService',
function($scope, $stateParams, sessionService, modal, notice, commentService) {
  $scope.control = $scope;

  
   $scope.loadEntryComments = function loadEntryComments() {
      commentService.loadEntryComments($scope.items[$stateParams.position].id);
   }
   
   $scope.refreshDbeData = function refreshDbeData() {
     return $scope.$parent.refreshDbeData();
   }
   

  if ($scope.items.length == 0 && !$scope.loadingDto) {
    $scope.$parent.refreshDbeData(true).then(function() {
       return $scope.loadEntryComments();
     });
    } else {
      $scope.currentEntry = $scope.items[$stateParams.position];
      $scope.loadEntryComments();
    }
  
 
    
    $scope.$watch('items', function(oldVal, newVal) {
      if (oldVal != newVal) {      
          $scope.currentEntry = $scope.items[$stateParams.position];
      }
    });
    $scope.getMeaningForDisplay = function(entry) {
      return undefined;
    }
    $scope.getWordForDisplay = function(entry) {
      return undefined;
    }
  // permissions stuff
    $scope.rights = {
      canEditProject: function canEditProject() {
        return sessionService.hasProjectRight(sessionService.domain.PROJECTS, sessionService.operation.EDIT);
      },
      canEditEntry: function canEditEntry() {
        return sessionService.hasProjectRight(sessionService.domain.ENTRIES, sessionService.operation.EDIT);
      },
      canDeleteEntry: function canDeleteEntry() {
        return sessionService.hasProjectRight(sessionService.domain.ENTRIES, sessionService.operation.DELETE);
      },
      canComment: function canComment() {
        return sessionService.hasProjectRight(sessionService.domain.COMMENTS, sessionService.operation.CREATE);
      },
      canDeleteComment: function canDeleteComment(commentAuthorId) {
        if (sessionService.session.userId == commentAuthorId) {
          return sessionService.hasProjectRight(sessionService.domain.COMMENTS, sessionService.operation.DELETE_OWN);
        } else {
          return sessionService.hasProjectRight(sessionService.domain.COMMENTS, sessionService.operation.DELETE);
        }
      },
      canEditComment: function canEditComment(commentAuthorId) {
        if (sessionService.session.userId == commentAuthorId) {
          return sessionService.hasProjectRight(sessionService.domain.COMMENTS, sessionService.operation.EDIT_OWN);
        } else {
          return false;
        }
      },
      canUpdateCommentStatus: function canUpdateCommentStatus() {
        return sessionService.hasProjectRight(sessionService.domain.COMMENTS, sessionService.operation.EDIT);
      }
    };
}]);
