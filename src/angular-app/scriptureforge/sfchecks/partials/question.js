'use strict';

angular.module('sfchecks.question', ['ui.bootstrap', 'coreModule',
  'sfchecks.services', 'ngRoute', 'palaso.ui.listview', 'palaso.ui.jqte',
  'palaso.ui.selection', 'palaso.ui.tagging', 'palaso.ui.notice'])
  .controller('QuestionCtrl', ['$scope', '$routeParams', 'questionService', 'sessionService',
    'utilService', 'breadcrumbService', 'silNoticeService', 'linkService', 'modalService',
  function ($scope, $routeParams, questionService, ss,
            util, breadcrumbService, notice, linkService, modalService) {
    var Q_TITLE_LIMIT = 30;
    $scope.getAvatarUrl = util.constructor.getAvatarUrl;
    $scope.finishedLoading = false;
    $scope.state = 'stop';

    var questionId = $routeParams.questionId;

    $scope.votes = {};
    $scope.unreadComments = [];
    $scope.unreadAnswers = [];
    $scope.myResponses = [];

    $scope.unreadResponseCount = function () {
      return $scope.unreadComments.length + $scope.unreadAnswers.length;
    };

    $scope.isUnreadComment = function (id) {
      return ($.inArray(id, $scope.unreadComments) > -1 || $.inArray(id, $scope.myResponses) > -1);
    };

    $scope.isUnreadAnswer = function (id) {
      return ($.inArray(id, $scope.unreadAnswers) > -1 || $.inArray(id, $scope.myResponses) > -1);
    };

    questionService.read(questionId, function (result) {
      if (result.ok) {
        $scope.project = result.data.project;
        $scope.text = result.data.text;
        if ($scope.text.audioFileName !== '') {
          $scope.audioPlayUrl = '/assets/sfchecks/' + $scope.project.slug + '/' + $scope.text.id +
            '_' + $scope.text.audioFileName;
          $scope.audioDownloadUrl = '/download' + $scope.audioPlayUrl;
        }

        $scope.question = result.data.question;
        $scope.votes = result.data.votes;
        $scope.unreadComments = result.data.unreadComments;
        $scope.unreadAnswers = result.data.unreadAnswers;

        // console.log(result.data);

        // Breadcrumb
        breadcrumbService.set('top', [{
          href: '/app/projects',
          label: 'My Projects'
        }, {
          href: linkService.project(),
          label: $scope.project.projectName
        }, {
          href: linkService.text($routeParams.textId),
          label: $scope.text.title
        }, {
          href: linkService.question($routeParams.textId, $routeParams.questionId),
          label: $scope.question.title
        }]);

        // Keep track of answer count so we can show or hide "There are no
        // answers" as appropriate
        $scope.question.answerCount = Object.keys($scope.question.answers).length;
        $scope.rights = result.data.rights;
        $scope.finishedLoading = true;
      }
    });

    ss.getSession().then(function (session) {

      // Rights: Answers
      $scope.rightsEditResponse = function (userId) {
        if (session.project().isArchived) return false;
        return session.hasRight($scope.rights, ss.domain.ANSWERS, ss.operation.EDIT) ||
          ((userId === session.userId()) &&
          session.hasRight($scope.rights, ss.domain.ANSWERS, ss.operation.EDIT_OWN));
      };

      $scope.rightsDeleteResponse = function (userId) {
        if (session.project().isArchived) return false;
        return session.hasRight($scope.rights, ss.domain.ANSWERS, ss.operation.DELETE) ||
          ((userId === session.userId()) &&
          session.hasRight($scope.rights, ss.domain.ANSWERS, ss.operation.DELETE_OWN));
      };

      // Rights: Question
      $scope.rightsCloseQuestion = function () {
        if (session.project().isArchived) return false;
        return session.hasRight($scope.rights, ss.domain.QUESTIONS, ss.operation.EDIT);
      };

      $scope.rightsEditQuestion = function () {
        if (session.project().isArchived) return false;
        return session.hasRight($scope.rights, ss.domain.QUESTIONS, ss.operation.EDIT);
      };

      // Rights: Tags
      $scope.rightsCreateTag = function () {
        if (session.project().isArchived) return false;
        return session.hasRight($scope.rights, ss.domain.TAGS, ss.operation.CREATE);
      };

      $scope.rightsDeleteTag = function () {
        if (session.project().isArchived) return false;
        return session.hasRight($scope.rights, ss.domain.TAGS, ss.operation.DELETE);
      };

      // Rights: Export
      $scope.rightsExport = function () {
        return session.hasRight($scope.rights, ss.domain.TEXTS, ss.operation.EDIT);
      };

    });

    $scope.workflowStates = [{
      state: 'open',
      label: 'Open'
    }, {
      state: 'review',
      label: 'In Review'
    }, {
      state: 'closed',
      label: 'Closed'
    }];

    $scope.questionIsClosed = function () {
      if ($scope.question) {
        return ($scope.question.workflowState === 'closed');
      }
    };

    $scope.editQuestionCollapsed = true;
    $scope.showQuestionEditor = function () {
      $scope.editQuestionCollapsed = false;
    };

    $scope.hideQuestionEditor = function () {
      $scope.editQuestionCollapsed = true;
    };

    $scope.toggleQuestionEditor = function () {
      $scope.editQuestionCollapsed = !$scope.editQuestionCollapsed;
    };

    $scope.$watch('editQuestionCollapsed', function (newval) {
      if (newval) {
        return;
      }

      // Question editor not collapsed? Then set up initial values
      $scope.editedQuestion = {
        id: $scope.question.id,
        title: $scope.question.title,
        description: $scope.question.description,
        workflowState: $scope.question.workflowState

        // Do we need to copy the other values? Let's check:
        // dateCreated: $scope.question.dateCreated,
        // textRef: $scope.question.textRef,
        // answers: $scope.question.answers,
        // answerCount: $scope.question.answerCount,
      };
    });

    $scope.questionTitleCalculated = '';
    $scope.$watch('question.title', function () {
      if ($scope.question) {
        $scope.questionTitleCalculated = questionService.util
          .calculateTitle($scope.question.title, $scope.question.description, Q_TITLE_LIMIT);
        breadcrumbService.updateCrumb('top', 3, {
          label: $scope.questionTitleCalculated
        });
      }
    });

    $scope.$watch('question.description', function () {
      if ($scope.question) {
        $scope.questionTitleCalculated = questionService.util
          .calculateTitle($scope.question.title, $scope.question.description, Q_TITLE_LIMIT);
        breadcrumbService.updateCrumb('top', 3, {
          label: $scope.questionTitleCalculated
        });
      }
    });

    $scope.updateQuestion = function (newQuestion) {
      questionService.update(newQuestion, function (result) {
        if (result.ok) {
          notice.push(notice.SUCCESS, 'The question was updated successfully');
          questionService.read(newQuestion.id, function (result) {
            if (result.ok) {
              $scope.question = result.data.question;

              // Recalculate answer count since the DB doesn't store it
              $scope.question.answerCount = Object.keys($scope.question.answers).length;
            }
          });
        }
      });
    };

    $scope.openEditors = {
      answerId: null,
      commentId: null
    };

    $scope.showAnswerEditor = function (answerId) {
      $scope.openEditors.answerId = answerId;
    };

    $scope.hideAnswerEditor = function () {
      $scope.openEditors.answerId = null;
    };

    $scope.$watch('openEditors.answerId', function (newval) {
      if (newval === null || newval === undefined) {
        // Skip; we're being called during initialization
        return;
      }

      // Set up the values needed by the new editor
      var answer = $scope.question.answers[newval];
      if (angular.isUndefined(answer)) {
        // console.log('Failed to find', newval, 'in', $scope.question.answers);
        return;
      }

      $scope.editedAnswer = {
        id: newval,
        comments: {},
        content: answer.content,

        // dateEdited: Date.now(), // Commented out for now because the model
        // wasn't happy with a Javascript date. TODO: Figure out what format I
        // should be passing this in. RM 2013-08
        score: answer.score,
        textHighlight: answer.textHighlight,
        userRef: answer.userRef
      };
      for (var id in answer.comments) {
        if (answer.comments.hasOwnProperty(id)) {
          var strippedComment = {};
          var comment = answer.comments[id];
          strippedComment.id = comment.id;
          strippedComment.content = comment.content;
          strippedComment.dateCreated = comment.dateCreated;
          strippedComment.dateEdited = comment.dateEdited;
          strippedComment.userRef = comment.userRef.userid;
          $scope.editedAnswer.comments[id] = strippedComment;
        }
      }
    });

    $scope.answerEditorVisible = function (answerId) {
      return (answerId === $scope.openEditors.answerId);
    };

    $scope.showCommentEditor = function (commentId) {
      $scope.openEditors.commentId = commentId;
    };

    $scope.hideCommentEditor = function () {
      $scope.openEditors.commentId = null;
    };

    $scope.$watch('openEditors.commentId', function (newval) {
      if (newval === null || newval === undefined) {
        // Skip; we're being called during initialization
        return;
      }

      // We're in the question-level scope, and we need to find a
      // specific commentId without knowing which answer it belongs
      // to, because all we have to work with is the new value of
      // the commentId (the old value won't help us).
      var comment = undefined;
      searchLoop: for (var aid in $scope.question.answers) {
        if ($scope.question.answers.hasOwnProperty(aid)) {
          var answer = $scope.question.answers[aid];
          for (var cid in answer.comments) {
            if (answer.comments.hasOwnProperty(cid) && cid === newval) {
              comment = answer.comments[cid];
              break searchLoop;
            }
          }
        }
      }

      // Set up the values needed by the new editor
      if (angular.isUndefined(comment)) {
        // console.log('Failed to find', newval, 'in', $scope.question.comments);
        return;
      }

      $scope.editedComment = {
        id: newval,
        content: comment.content,

        // dateEdited: Date.now(), // Commented out for now because the model
        // wasn't happy with a Javascript date. TODO: Figure out what format I
        // should be passing this in. RM 2013-08
        userRef: comment.userRef // Do we really need to copy this over? Or will
        // the PHP model code take care of that for us?
      };
    });

    $scope.commentEditorVisible = function (commentId) {
      return (commentId === $scope.openEditors.commentId);
    };

    $scope.newComment = {
      content: ''
    };

    $scope.newAnswer = {
      content: '',
      textHighlight: ''
    };

    $scope.updateComment = function (answerId, answer, newComment) {
      questionService.updateComment(questionId, answerId, newComment, function (result) {
        if (result.ok) {
          if (newComment.id === '') {
            notice.push(notice.SUCCESS, 'The comment was submitted successfully');
          } else {
            notice.push(notice.SUCCESS, 'The comment was updated successfully');
          }

          for (var id in result.data) {
            if (result.data.hasOwnProperty(id)) {
              // There should be one, and only one, record in result.data
              newComment = result.data[id];
            }
          }

          $scope.question.answers[answerId].comments[newComment.id] = newComment;
          $scope.myResponses.push(newComment.id);
        }
      });
    };

    $scope.submitComment = function (answerId, answer) {
      var newComment = {
        id: '',
        content: $scope.newComment.content
      };
      $scope.updateComment(answerId, answer, newComment);
      $scope.newComment.content = '';
      $scope.newComment.textHighlight = '';
    };

    $scope.editComment = function (answerId, answer, comment) {
      if ($scope.rightsEditResponse(comment.userRef.userid)) {
        $scope.updateComment(answerId, answer, comment);
      }

      $scope.hideCommentEditor();
    };

    $scope.commentDelete = function (answer, commentId) {
      var message = 'Are you sure you want to delete this Comment?';
      var modalOptions = {
        closeButtonText: 'Cancel',
        actionButtonText: 'Delete',
        headerText: 'Delete Comment?',
        bodyText: message
      };
      modalService.showModal({}, modalOptions).then(function () {
        questionService.removeComment(questionId, answer.id, commentId, function (result) {
          if (result.ok) {
            notice.push(notice.SUCCESS, 'The comment was removed successfully');

            // Delete locally
            delete answer.comments[commentId];
          }
        });
      }, angular.noop);
    };

    var afterUpdateAnswer = function (answersDto) {
      for (var id in answersDto) {
        if (answersDto.hasOwnProperty(id)) {
          $scope.question.answers[id] = answersDto[id];
          $scope.myResponses.push(id);
        }
      }

      // Recalculate answer count as it might have changed
      $scope.question.answerCount = Object.keys($scope.question.answers).length;
    };

    $scope.voteUp = function (answerId) {
      if ($scope.votes[answerId] === true || $scope.questionIsClosed()) {
        return;
      }

      questionService.answerVoteUp(questionId, answerId, function (result) {
        if (result.ok) {
          // console.log('vote up ok');
          $scope.votes[answerId] = true;
          afterUpdateAnswer(result.data);
        }
      });
    };

    $scope.voteDown = function (answerId) {
      if ($scope.votes[answerId] !== true || $scope.questionIsClosed()) {
        return;
      }

      questionService.answerVoteDown(questionId, answerId, function (result) {
        if (result.ok) {
          // console.log('vote down ok');
          delete $scope.votes[answerId];
          afterUpdateAnswer(result.data);
        }
      });
    };

    var updateAnswer = function (questionId, answer) {
      questionService.updateAnswer(questionId, answer, function (result) {
        if (result.ok) {
          if (answer.id === '') {
            notice.push(notice.SUCCESS, 'The answer was submitted successfully');
          } else {
            notice.push(notice.SUCCESS, 'The answer was updated successfully');
          }

          afterUpdateAnswer(result.data);
        }
      });
    };

    $scope.submitAnswer = function () {
      var answer = {
        id: '',
        content: $scope.newAnswer.content,
        textHighlight: $scope.newAnswer.textHighlight
      };
      updateAnswer(questionId, answer);
      $scope.newAnswer.content = '';
      $scope.newAnswer.textHighlight = '';
      $scope.selectedText = '';
    };

    $scope.editAnswer = function (answer) {
      if ($scope.rightsEditResponse(answer.userRef.userid)) {
        updateAnswer(questionId, answer);
      }

      $scope.hideAnswerEditor();
    };

    $scope.answerDelete = function (answerId) {
      var message = 'Are you sure you want to delete this Answer?';
      var modalOptions = {
        closeButtonText: 'Cancel',
        actionButtonText: 'Delete',
        headerText: 'Delete Answer?',
        bodyText: message
      };
      modalService.showModal({}, modalOptions).then(function () {
        questionService.removeAnswer(questionId, answerId, function (result) {
          if (result.ok) {
            notice.push(notice.SUCCESS, 'The answer was removed successfully');

            // Delete locally
            delete $scope.question.answers[answerId];

            // Recalculate answer count as it just changed
            $scope.question.answerCount = Object.keys($scope.question.answers).length;
          }
        });
      }, angular.noop);
    };

    $scope.selectedText = '';
    $scope.$watch('selectedText', function (newval) {
      $scope.newAnswer.textHighlight = newval;
    });

    // TAGS
    var mergeArrays = function (a, b) {
      // From http://stackoverflow.com/a/13847481/2314532
      var set = {};
      var result = [];
      var item;
      var i;

      // Can't count on forEach being available; loop the manual way
      for (i = 0; i < a.length; i++) {
        item = a[i];
        if (!set[item]) { // O(1) lookup
          set[item] = true;
          result.push(item);
        }
      }

      for (i = 0; i < b.length; i++) {
        item = b[i];
        if (!set[item]) { // O(1) lookup
          set[item] = true;
          result.push(item);
        }
      }

      return result;
    };

    $scope.addTags = function (tags, answer) {
      answer.tags = mergeArrays(tags, answer.tags);
      questionService.updateAnswerTags(questionId, answer.id, answer.tags, function (result) {
        if (result.ok) {
          notice.push(notice.SUCCESS, 'The answer tag was added successfully');
        }
      });
    };

    $scope.deletedTags = function (answer) {
      questionService.updateAnswerTags(questionId, answer.id, answer.tags, function (result) {
        if (result.ok) {
          notice.push(notice.SUCCESS, 'The answer tags were deleted successfully');
        }
      });
    };

    $scope.flagForExport = function (answer) {
      answer.isToBeExported = !answer.isToBeExported;
      questionService.updateAnswerExportFlag(questionId, answer.id, answer.isToBeExported,
        function (result) {
          if (result.ok) {
            if (answer.isToBeExported) {
              notice.push(notice.SUCCESS, 'The answer was flagged for export successfully');
            } else {
              notice.push(notice.SUCCESS, 'The answer was cleared from export successfully');
            }
          }
        }
      );
    };

  }]);
