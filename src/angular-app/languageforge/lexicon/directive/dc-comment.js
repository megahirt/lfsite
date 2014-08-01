angular.module('palaso.ui.dc.comment', ['angularjs-gravatardirective'])
// Palaso UI Dictionary Control: Comments
.directive('dcComments', [function() {
	return {
		restrict: 'E',
		templateUrl: '/angular-app/languageforge/lexicon/directive/dc-comment.html',
		scope: {
			config : "=",
			model : "=",
			control: "="
		},
		controller: ['$scope', function($scope) {
			$scope.data = {newComment: "", newReply: ""};
			$scope.validStatuses = [ // TODO: Get this list from the appropriate service
				"To Do",
				"Reviewed",
				"Resolved",
			];
			$scope.nextStatus = function(prevStatus) {
				var idx = $scope.validStatuses.indexOf(prevStatus);
				return $scope.validStatuses[(idx+1) % $scope.validStatuses.length];
			};
			$scope.config = angular.copy($scope.config); // Don't want to make changes to the passed-in config object
			$scope.makeValidModel = function() {
				if (!$scope.model) {
					$scope.model = {};
				}
				if (!$scope.model.comments) {
					$scope.model.comments = [];
					// $scope.model.comments.push($scope.makeValidComment()); // Sample data for debugging
				}
			};

			/*
			$scope.makeValidComment = function() {
				// Create and return an empty comment object
				return {
					//userRef: {username: "Robin M.", email: "Robin_Munn@sil.org"}, // Sample data. If email provided, will be used in fetching Gravatar
					// TODO: Get actual username & email from session service
					//dateModified: new Date(), // Default to today's date, can modify this elsewhere if needed
					regarding: "",
					content: "",
					score: 0,
					replies: [],
					status: "To Do",
				};
			};
			*/
			

			$scope.newCommentClick = function() {
				console.log("newCommentClick");
				if ($scope.newComment != '') {
					var comment = {
						id : '',
						regarding : $scope.model.value,
						content : $scope.data.newComment
					};
					$scope.data.newComment = '';
					$scope.submitComment({comment:comment});
				}
			};
			
			$scope.newReplyClick = function(parentComment) {
				var reply = {
					id : '',
					content : $scope.data.newReply,
					parentId : parentComment.id
				};
				$scope.data.newReply = '';
				$scope.replyBoxVisible = false;
				$scope.submitComment({comment:reply});
			};

			// TODO: The correct way to do this, per spec, is to store votes on a per-user basis,
			// then calculate the score by subtracting downvotes from upvotes. This permits us to control
			// ballot stuffing by giving each user only one vote per comment, which can be an upvote
			// or a downvote. We'll need to hook up the session service and get the current username,
			// then store that in either the comment.upvotes or comment.downvotes list. Then the current
			// score will become a function: return comment.upvotes.length - comment.downvotes.length.
			$scope.incScore = function(comment) {
				comment.score++;
			};
			$scope.decScore = function(comment) {
				comment.score--;
			};
		}],
		link: function(scope, element, attrs, controller) {
			scope.$watch('visibility', function() { // Or some other variable instead of visibility... this is just an example
				// Hide me
			});
			scope.$watch('model', function() {
				scope.makeValidModel();
			});
		},
	};
}])
;
