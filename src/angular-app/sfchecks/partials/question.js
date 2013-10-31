'use strict';

angular.module(
		'sfchecks.question',
		[ 'sf.services', 'palaso.ui.listview', 'palaso.ui.jqte', 'ui.bootstrap', 'palaso.ui.m3ulink', 'palaso.ui.selection', 'palaso.ui.tagging', 'palaso.ui.notice' ]
	)
	.controller('QuestionCtrl', ['$scope', '$routeParams', 'questionService', 'sessionService', 'breadcrumbService', 'silNoticeService',
	                             function($scope, $routeParams, questionService, ss, breadcrumbService, notice) {
		$scope.jqteOptions = {
			'placeholder': 'Type your answer here. Click "Done" when finished.',
			'u': false,
			'indent': false,
			'outdent': false,
			'left': false,
			'center': false,
			'right': false,
			'rule': false,
			'source': false,
			'link': false,
			'unlink': false,
			'fsize': false,
			'sub': false,
			'color': false,
			'format': false,
			'formats': [
				['p', 'Normal'],
				['h4', 'Large']
			]
		};

		var projectId = $routeParams.projectId;
		var questionId = $routeParams.questionId;

		// Breadcrumb
		breadcrumbService.set('top',
				[
				 {href: '/app/sfchecks#/projects', label: 'My Projects'},
				 {href: '/app/sfchecks#/project/' + $routeParams.projectId, label: ''},
				 {href: '/app/sfchecks#/project/' + $routeParams.projectId + '/' + $routeParams.textId, label: ''},
				 {href: '/app/sfchecks#/project/' + $routeParams.projectId + '/' + $routeParams.textId + '/' + $routeParams.questionId, label: ''},
				]
		);

		$scope.votes = {};
		$scope.unreadComments = [];
		$scope.unreadAnswers = [];
		$scope.myResponses = [];
		
		$scope.unreadResponseCount = function() {
			return $scope.unreadComments.length + $scope.unreadAnswers.length;
		};
		
		$scope.isUnreadComment = function(id) {
			return ($.inArray(id, $scope.unreadComments) > -1 || $.inArray(id, $scope.myResponses) > -1);
		};
		$scope.isUnreadAnswer = function(id) {
			return ($.inArray(id, $scope.unreadAnswers) > -1 || $.inArray(id, $scope.myResponses) > -1);
		};
		
		questionService.read(projectId, questionId, function(result) {
			//console.log('questionService.read(', projectId, questionId, ')');
			if (result.ok) {
				$scope.text = result.data.text;
				if ($scope.text.audioUrl) {
					$scope.mp3link = '/' + $scope.text.audioUrl; // Should the added / be in upload.php?
				}
				$scope.question = result.data.question;
				$scope.votes = result.data.votes;
				$scope.project = result.data.project;
				$scope.unreadComments = result.data.unreadComments;
				$scope.unreadAnswers = result.data.unreadAnswers;
				//console.log(result.data);
				breadcrumbService.updateCrumb('top', 1, {label: $scope.project.projectname});
				breadcrumbService.updateCrumb('top', 2, {label: $scope.text.title});
				breadcrumbService.updateCrumb('top', 3, {label: $scope.question.title});
				// Keep track of answer count so we can show or hide "There are no answers" as appropriate
				$scope.question.answerCount = Object.keys($scope.question.answers).length;
				$scope.rights = result.data.rights;
			}
		});

		// Rights: Answers
		$scope.rightsEditResponse = function(userId) {
			var right = ss.hasRight($scope.rights, ss.domain.ANSWERS, ss.operation.EDIT_OTHER)
				|| ((userId == ss.currentUserId()) && ss.hasRight($scope.rights, ss.domain.ANSWERS, ss.operation.EDIT_OWN));
			return right;
		};

		$scope.rightsDeleteResponse = function(userId) {
			var right = ss.hasRight($scope.rights, ss.domain.ANSWERS, ss.operation.DELETE_OTHER)
				|| ((userId == ss.currentUserId()) && ss.hasRight($scope.rights, ss.domain.ANSWERS, ss.operation.DELETE_OWN));
			return right;
		};
		
		// Rights: Question
		$scope.rightsCloseQuestion = function(userId) {
			return ss.hasRight($scope.rights, ss.domain.QUESTIONS, ss.operation.EDIT_OTHER);
		};
		
		$scope.rightsEditQuestion = function(userId) {
			return ss.hasRight($scope.rights, ss.domain.QUESTIONS, ss.operation.EDIT_OTHER);
		};
		
		
		// Rights: Tags
		$scope.rightsCreateTag = function() {
			return ss.hasRight($scope.rights, ss.domain.TAGS, ss.operation.CREATE);
		};
		
		$scope.rightsDeleteTag = function() {
			return ss.hasRight($scope.rights, ss.domain.TAGS, ss.operation.DELETE_OTHER);
		};
		
		$scope.workflowStates = [
			{
				state: "open",
				label: "Open"
			},
			{
				state: "review",
				label: "In Review"
			},
			{
				state: "closed",
				label: "Closed"
			},
		];
		
		$scope.questionIsClosed = function() {
			if ($scope.question) {
				return ($scope.question.workflowState == 'closed');
			}
		}
		
		$scope.editQuestionCollapsed = true;
		$scope.showQuestionEditor = function() {
			$scope.editQuestionCollapsed = false;
		};
		$scope.hideQuestionEditor = function() {
			$scope.editQuestionCollapsed = true;
		};
		$scope.toggleQuestionEditor = function() {
			$scope.editQuestionCollapsed = !$scope.editQuestionCollapsed;
		};
		$scope.$watch('editQuestionCollapsed', function(newval) {
			if (newval) { return; }
			// Question editor not collapsed? Then set up initial values
			$scope.editedQuestion = {
				id: $scope.question.id,
				title: $scope.question.title,
				description: $scope.question.description,
				workflowState: $scope.question.workflowState
				// Do we need to copy the other values? Let's check:
				//dateCreated: $scope.question.dateCreated,
				//textRef: $scope.question.textRef,
				//answers: $scope.question.answers,
				//answerCount: $scope.question.answerCount,
			};
		});
		$scope.updateQuestion = function(newQuestion) {
			questionService.update(projectId, newQuestion, function(result) {
				if (result.ok) {
					notice.push(notice.SUCCESS, "The question was updated successfully")
					questionService.read(projectId, newQuestion.id, function(result) {
						if (result.ok) {
							$scope.question = result.data.question;
							breadcrumbService.updateCrumb('top', 3, {label: $scope.question.title});
							// Recalculate answer count since the DB doesn't store it
							$scope.question.answerCount = Object.keys($scope.question.answers).length;
						}
					});
				}
			});
		};

		$scope.openEditors = {
			answerId: null,
			commentId: null,
		};

		$scope.showAnswerEditor = function(answerId) {
			$scope.openEditors.answerId = answerId;
		};

		$scope.hideAnswerEditor = function() {
			$scope.openEditors.answerId = null;
		};

		$scope.$watch('openEditors.answerId', function(newval, oldval) {
			if (newval === null || newval === undefined) {
				// Skip; we're being called during initialization
				return;
			}

			// Set up the values needed by the new editor
			var answer = $scope.question.answers[newval];
			if (angular.isUndefined(answer)) {
				//console.log('Failed to find', newval, 'in', $scope.question.answers);
				return;
			}
			$scope.editedAnswer = {
				id: newval,
				comments: {},
				content: answer.content,
				//dateEdited: Date.now(), // Commented out for now because the model wasn't happy with a Javascript date. TODO: Figure out what format I should be passing this in. RM 2013-08
				score: answer.score,
				textHighlight: answer.textHighlight,
				userRef: answer.userRef,
			};
			for (var id in answer.comments) {
				var strippedComment = {};
				var comment = answer.comments[id];
				strippedComment.id = comment.id;
				strippedComment.content = comment.content;
				strippedComment.dateCreated = comment.dateCreated;
				strippedComment.dateEdited = comment.dateEdited;
				strippedComment.userRef = comment.userRef.userid;
				$scope.editedAnswer.comments[id] = strippedComment;
			}
		});

		$scope.answerEditorVisible = function(answerId) {
			return (answerId == $scope.openEditors.answerId);
		};

		$scope.showCommentEditor = function(commentId) {
			$scope.openEditors.commentId = commentId;
		};
		$scope.hideCommentEditor = function() {
			$scope.openEditors.commentId = null;
		};
		$scope.$watch('openEditors.commentId', function(newval, oldval) {
			if (newval === null || newval === undefined) {
				// Skip; we're being called during initialization
				return;
			}

			// We're in the question-level scope, and we need to find a
			// specific commentId without knowing which answer it belongs
			// to, because all we have to work with is the new value of
			// the commentId (the old value won't help us).
			var comment = undefined;
			search_loop:
			for (var aid in $scope.question.answers) {
				var answer = $scope.question.answers[aid];
				for (var cid in answer.comments) {
					if (cid == newval) {
						comment = answer.comments[cid];
						break search_loop;
					}
				}
			}
			// Set up the values needed by the new editor
			if (angular.isUndefined(comment)) {
				//console.log('Failed to find', newval, 'in', $scope.question.comments);
				return;
			}
			$scope.editedComment = {
				id: newval,
				content: comment.content,
				//dateEdited: Date.now(), // Commented out for now because the model wasn't happy with a Javascript date. TODO: Figure out what format I should be passing this in. RM 2013-08
				userRef: comment.userRef, // Do we really need to copy this over? Or will the PHP model code take care of that for us?
			};
		});

		$scope.commentEditorVisible = function(commentId) {
			return (commentId == $scope.openEditors.commentId);
		};
		
		$scope.newComment = {
			'content': ''
		};
		
		$scope.newAnswer = {
			content: '',
			textHighlight: '',
		};
		
		$scope.updateComment = function(answerId, answer, newComment) {
			questionService.update_comment(projectId, questionId, answerId, newComment, function(result) {
				if (result.ok) {
					if (newComment.id == '') {
						notice.push(notice.SUCCESS, "The comment was submitted successfully");
					} else {
						notice.push(notice.SUCCESS, "The comment was updated successfully");
					}
					for (var id in result.data) {
						newComment = result.data[id]; // There should be one, and only one, record in result.data
					}
					$scope.question.answers[answerId].comments[newComment.id] = newComment;
					$scope.myResponses.push(newComment.id);
				}
			});
		};
		
		$scope.submitComment = function(answerId, answer) {
			var newComment = {
				id: '',
				content: $scope.newComment.content,
			};
			$scope.updateComment(answerId, answer, newComment);
			$scope.newComment.content = '';
			$scope.newComment.textHighlight = '';
		};
		
		$scope.editComment = function(answerId, answer, comment) {
			if ($scope.rightsEditResponse(comment.userRef.userid)) {
				$scope.updateComment(answerId, answer, comment);
			}
			$scope.hideCommentEditor();
		};
		
		$scope.commentDelete = function(answer, commentId) {
			console.log('delete ', commentId);
			questionService.remove_comment(projectId, questionId, answer.id, commentId, function(result) {
				if (result.ok) {
					notice.push(notice.SUCCESS, "The comment was removed successfully");
					// Delete locally
					delete answer.comments[commentId];
				}
			});
		};

		var afterUpdateAnswer = function(answersDto) {
			for (var id in answersDto) {
				$scope.question.answers[id] = answersDto[id];
				$scope.myResponses.push(id);
			}
			// Recalculate answer count as it might have changed
			$scope.question.answerCount = Object.keys($scope.question.answers).length;
		};
		
		$scope.voteUp = function(answerId) {
			if ($scope.votes[answerId] == true || $scope.questionIsClosed()) {
				return;
			}
			questionService.answer_voteUp(projectId, questionId, answerId, function(result) {
				if (result.ok) {
					console.log('vote up ok');
					$scope.votes[answerId] = true;
					afterUpdateAnswer(result.data);
				}
			});
		};
		
		$scope.voteDown = function(answerId) {
			if ($scope.votes[answerId] != true || $scope.questionIsClosed()) {
				return;
			}
			questionService.answer_voteDown(projectId, questionId, answerId, function(result) {
				if (result.ok) {
					console.log('vote down ok');
					delete $scope.votes[answerId];
					afterUpdateAnswer(result.data);
				}
			});
		};
		
		var updateAnswer = function(projectId, questionId, answer) {
			questionService.update_answer(projectId, questionId, answer, function(result) {
				if (result.ok) {
					if (answer.id == '') {
						notice.push(notice.SUCCESS, "The answer was submitted successfully");
					} else {
						notice.push(notice.SUCCESS, "The answer was updated successfully");
					}
					afterUpdateAnswer(result.data);
				}
			});
		};
		
		$scope.submitAnswer = function() {
			var answer = {
				'id':'',
				'content': $scope.newAnswer.content,
				'textHighlight': $scope.newAnswer.textHighlight,
			};
			updateAnswer(projectId, questionId, answer);
			$scope.newAnswer.content = '';
			$scope.newAnswer.textHighlight = '';
			$scope.selectedText = '';
		};
		
		$scope.editAnswer = function(answer) {
			if ($scope.rightsEditResponse(answer.userRef.userid)) {
				updateAnswer(projectId, questionId, answer);
			}
			$scope.hideAnswerEditor();
		};
		
		$scope.answerDelete = function(answerId) {
			console.log('delete ', answerId);
			questionService.remove_answer(projectId, questionId, answerId, function(result) {
				if (result.ok) {
					notice.push(notice.SUCCESS, "The answer was removed successfully");
					// Delete locally
					delete $scope.question.answers[answerId];
					// Recalculate answer count as it just changed
					$scope.question.answerCount = Object.keys($scope.question.answers).length;
				}
			});
		};

		$scope.selectedText = '';
		$scope.$watch('selectedText', function(newval) {
			$scope.newAnswer.textHighlight = newval;
		});

		// TAGS
		var mergeArrays = function(a, b) {
			// From http://stackoverflow.com/a/13847481/2314532
			var set = {};
			var result = [];

			// Can't count on forEach being available; loop the manual way
			for (var i=0; i < a.length; i++) {
				var item = a[i];
				if (!set[item]) { // O(1) lookup
					set[item] = true;
					result.push(item);
				}
			}
			for (var i=0; i < b.length; i++) {
				var item = b[i];
				if (!set[item]) { // O(1) lookup
					set[item] = true;
					result.push(item);
				}
			}
			return result;
		};

		$scope.addTags = function(tags, answer) {
//			console.log('Tags to add', tags, answer);
			answer.tags = mergeArrays(tags, answer.tags);
			updateAnswer(projectId, questionId, answer);
		};
		
		$scope.deletedTags = function(answer) {
//			console.log('Tags deleted');
			updateAnswer(projectId, questionId, answer);
		};
		
	}])
	;
