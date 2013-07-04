'use strict';

function projectIdFromLocation(location) {
	return location.path;
}

/* Controllers */
var app = angular.module(
		'projectAdmin.controllers',
		[ 'sf.services', 'palaso.ui.listview', 'palaso.ui.typeahead', 'ui.bootstrap' ]
	)
	.controller('UserListCtrl', ['$scope', '$location', 'projectService', function($scope, $location, projectService) {
		$scope.projectId = projectIdFromLocation($location);
		$scope.selected = [];
		$scope.updateSelection = function(event, item) {
			var selectedIndex = $scope.selected.indexOf(item);
			var checkbox = event.target;
			if (checkbox.checked && selectedIndex == -1) {
				$scope.selected.push(item);
			} else if (!checkbox.checked && selectedIndex != -1) {
				$scope.selected.splice(selectedIndex, 1);
			}
		};
		$scope.isSelected = function(item) {
			return item != null && $scope.selected.indexOf(item) >= 0;
		};
		
		$scope.users = [];
		$scope.queryUsers = function() {
			projectService.listUsers($scope.projectId, function(result) {
				if (result.ok) {
					$scope.users = result.data.entries;
					$scope.userCount = result.data.count;
				}
			});
		};
		
		$scope.removeUsers = function() {
			console.log("removeUsers");
			var userIds = [];
			for(var i = 0, l = $scope.selected.length; i < l; i++) {
				userIds.push($scope.selected[i].id);
			}
			if (l == 0) {
				// TODO ERROR
				return;
			}
			projectService.remove($scope.projectId, userIds, function(result) {
				if (result.ok) {
					// TODO
				}
			});
		};
		
		$scope.selectUser = function(item) {
			console.log("Called selectUser(", item, ")");
		};
		
	}])
	.controller('UserSearchCtrl', ['$scope', '$location', 'userService', 'projectService', function($scope, $location, userService, projectService) {
		$scope.projectId = projectIdFromLocation($location);
	    $scope.users = [];
	    $scope.addModes = {
	    	'addNew': { 'en': 'Create New', 'icon': 'icon-user'},
	    	'addExisting' : { 'en': 'Add Existing', 'icon': 'icon-user'},
	    	'invite': { 'en': 'Send Invite', 'icon': 'icon-envelope'}
	    };
	    $scope.addMode = 'addNew';
		
		$scope.searchUser = function(term) {
			console.log('searching for ', term);
			userService.typeahead(term, function(result) {
				// TODO Check term == controller view value (cf bootstrap typeahead) else abandon.
				if (result.ok) {
					$scope.users = result.data.entries;
					$scope.updateAddMode();
				}
			});
		};
		$scope.addModeText = function(addMode) {
			return $scope.addModes[addMode].en;
		};
		$scope.addModeIcon = function(addMode) {
			return $scope.addModes[addMode].icon;
		};
		$scope.updateAddMode = function() {
			// TODO This isn't adequate.  Need to watch the 'term' and 'selection' also. CP 2013-07
			if ($scope.users.length == 0) {
				$scope.addMode = 'addNew';
			} else if ($scope.users.length == 1) {
				$scope.addMode = 'addExisting';
			}
		};
		
		$scope.addUser = function() {
			var model = {};
			if ($scope.addMode == 'addNew') {
				model.name = $scope.term;
			} else if ($scope.addMode == 'addExisting') {
				model.id = $scope.user.id;
			} else if ($scope.addMode == 'invite') {
				$model.email = $scope.term;
			}
			console.log("addUser ", model);
			projectService.updateUser($scope.projectId, model, function(result) {
				if (result.ok) {
					// TODO broadcast notice and add
				}
			});
		};
	
		$scope.selectUser = function(item) {
			console.log('user selected', item);
			$scope.user = item;
			$scope.term = item.name;
		};
	
		$scope.imageSource = function(avatarRef) {
			return avatarRef ? '/images/avatar/' + avatarRef : '/images/avatar/anonymous02.png';
		};
	
	}])
	;
