'use strict';
angular.module('palaso.ui.comments')

// Palaso UI Dictionary Control: Comments

  .directive('commentsRightPanel', [function () {
    return {
      restrict: 'E',
      templateUrl: '/angular-app/bellows/directive/' + bootstrapVersion +
        '/palaso.ui.comments.comments-right-panel.html',
      scope: {
        entry: '=',
        control: '=',
        newComment: '='
      },
      controller: ['$scope', '$filter', 'lexCommentService', 'sessionService',
      function ($scope, $filter, commentService, ss) {

        /*  $scope.newComment has the following initial structure
         {
         id: '',
         content: '',
         regarding: {}
         };
         */
        $scope.initializeNewComment = function initializeNewComment() {
          $scope.newComment =  {
            id: '',
            content: '',
            entryRef: $scope.entry.id,
            regarding: {
              meaning: $scope.control.getMeaningForDisplay($scope.entry),
              word: $scope.control.getWordForDisplay($scope.entry)
            }
          };
        };

        $scope.currentEntryCommentsFiltered = commentService.comments.items.currentEntryFiltered;

        $scope.numberOfComments = function numberOfComments() {
          return commentService.comments.counts.currentEntry.total;
        };

        $scope.commentFilter = {
          text: '',
          status: 'all',
          byText: function byText(comment) {
            // Convert entire comment object to a big string and search for filter.
            // Note: This has a slight side effect of ID and avatar information
            // matching the filter.
            return (JSON.stringify(comment).normalize().toLowerCase()
              .indexOf($scope.commentFilter.text.normalize().toLowerCase()) !== -1);
          },

          byStatus: function byStatus(comment) {
            if (angular.isDefined(comment)) {
              if ($scope.commentFilter.status === 'all') {
                return true;
              } else if ($scope.commentFilter.status === 'todo') {
                if (comment.status === 'todo') {
                  return true;
                }
              } else { // show unresolved comments
                if (comment.status !== 'resolved') {
                  return true;
                }
              }
            }

            return false;
          }
        };

        ss.getSession().then(function(session) {
          $scope.rights = {
            canComment: function canComment() {
              if (session.project().isArchived) return false;
              return session.hasProjectRight(ss.domain.COMMENTS,
                ss.operation.CREATE);
            },

            canDeleteComment: function canDeleteComment(commentAuthorId) {
              if (session.project().isArchived) return false;
              if (session.userId() === commentAuthorId) {
                return session.hasProjectRight(ss.domain.COMMENTS,
                  ss.operation.DELETE_OWN);
              } else {
                return session.hasProjectRight(ss.domain.COMMENTS,
                  ss.operation.DELETE);
              }
            },

            canEditComment: function canEditComment(commentAuthorId) {
              if (session.project().isArchived) return false;
              if (session.userId() === commentAuthorId) {
                return session.hasProjectRight(ss.domain.COMMENTS,
                  ss.operation.EDIT_OWN);
              } else {
                return false;
              }
            },

            canUpdateCommentStatus: function canUpdateCommentStatus() {
              if (session.project().isArchived) return false;
              return session.hasProjectRight(ss.domain.COMMENTS,
                ss.operation.EDIT);
            }
          };
        });

        commentService.refreshFilteredComments($scope.commentFilter);

        $scope.loadComments = function loadComments() {
          commentService.loadEntryComments($scope.entry.id);
          commentService.refreshFilteredComments($scope.commentFilter);
        };

        $scope.postNewComment = function postNewComment() {
          commentService.update($scope.newComment, function (result) {
            if (result.ok) {
              $scope.control.editorService.refreshEditorData().then(function () {
                $scope.loadComments();
                $scope.initializeNewComment();
              });
            }
          });

          commentService.refreshFilteredComments($scope.commentFilter); // for instant feedback
        };

        $scope.plusOneComment = function plusOneComment(commentId) {
          commentService.plusOne(commentId, function (result) {
            if (result.ok) {
              $scope.control.editorService.refreshEditorData().then(function () {
                $scope.loadComments();
              });
            }
          });
        };

        $scope.canPlusOneComment = function canPlusOneComment(commentId) {
          return !(angular.isDefined(commentService.comments.counts.userPlusOne) &&
              angular.isDefined(commentService.comments.counts.userPlusOne[commentId]));
        };

        $scope.getNewCommentPlaceholderText = function getNewCommentPlaceholderText() {
          var label;
          if (commentService.comments.items.currentEntry.length === 0) {
            label = $filter('translate')('Your comment goes here.  Be the first to share!');
          } else if (commentService.comments.items.currentEntry.length < 3) {
            label = $filter('translate')('Start a conversation.  Enter your comment here.');
          } else {
            label = $filter('translate')('Join the discussion and type your comment here.');
          }

          return label;
        };

        $scope.$watch('entry', function (newVal) {
          if (newVal) {
            $scope.loadComments();
            $scope.initializeNewComment();
          }
        });

        $scope.$watch('commentFilter.text', function (newVal, oldVal) {
          if (newVal !== oldVal) {
            commentService.refreshFilteredComments($scope.commentFilter);
          }

        });

        $scope.$watch('commentFilter.status', function (newVal, oldVal) {
          if (newVal !== oldVal) {
            commentService.refreshFilteredComments($scope.commentFilter);
          }

        });

      }],

      link: function (scope, element, attrs, controller) {
      }
    };
  }])

;
