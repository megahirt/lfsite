'use strict';

function changePasswordCtrl($scope, userService, sessionService, notice) {
	$scope.notify = {};
	
	$scope.updatePassword = function() {
		if ($scope.vars.password == $scope.vars.confirm_password) {
			userService.changePassword(sessionService.currentUserId(), $scope.vars.password, function(result) {
				if (result.ok) {
					notice.push(notice.SUCCESS, "Password Updated successfully");
					$scope.vars.password = $scope.vars.confirm_password = "";
				}
			});
		}
	};
}

angular.module('changepassword', ['jsonRpc', 'ui.bootstrap', 'bellows.services', 'ui.validate', 'palaso.ui.notice']).
controller('changePasswordCtrl', ['$scope', 'userService', 'sessionService', 'silNoticeService', changePasswordCtrl])
;
