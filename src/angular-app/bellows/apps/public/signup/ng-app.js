'use strict';

// based on http://scotch.io/tutorials/javascript/angularjs-multi-step-form-using-ui-router
// also http://www.ng-newsletter.com/posts/angular-ui-router.html

// Declare app level module which depends on filters, and services
angular.module('signup', ['bellows.services', 'ui.bootstrap', 'ngAnimate', 'ui.router', 'pascalprecht.translate'])
.config(['$stateProvider', '$urlRouterProvider', '$translateProvider', 
         function($stateProvider, $urlRouterProvider, $translateProvider) {
	
	$stateProvider
		// route to show our basic form (/form)
		.state('form', {
			abstract: true,
			url: '/form',
			templateUrl: '/angular-app/bellows/apps/public/signup/views/form-.html',
			controller: 'SignupCtrl'
		})
		
		// nested states 
		// each of these sections have their own view
		// url will be nested (/form/identify)
		.state('form.identify', {
			url: '/identify',
			templateUrl: '/angular-app/bellows/apps/public/signup/views/form-identify.html'
		})
		
		// url will be /form/register
		.state('form.register', {
			url: '/register',
			templateUrl: '/angular-app/bellows/apps/public/signup/views/form-register.html'
		})
		
		// url will be /form/activate
		.state('form.activate', {
			url: '/activate',
			templateUrl: '/angular-app/bellows/apps/public/signup/views/form-activate.html'
		})
		
		// url will be /validate
		.state('validate', {
			url: '/validate',
			templateUrl: '/angular-app/bellows/apps/public/signup/views/validate.html'
		})
		
		// url will be /form/login
		.state('form.login', {
			url: '/login',
			templateUrl: '/angular-app/bellows/apps/public/signup/views/form-login.html'
		})
	;
	
	// catch all route
	// send users to the form page 
	$urlRouterProvider.otherwise('/form/identify');
	
	// configure interface language filepath
	$translateProvider.useStaticFilesLoader({
		prefix: '/angular-app/languageforge/lexicon/lang/',
		suffix: '.json'
	});
	$translateProvider.preferredLanguage('en');
}])
.controller('SignupCtrl', ['$scope', '$state', 'userService', 'sessionService', 'silNoticeService',  
                           function($scope, $state, userService, sessionService, notice) {
	$scope.showPassword = false;
	$scope.record = {};
	$scope.record.id = '';
	$scope.captchaSrc = '';
	$scope.currentState = $state.current;
	
	$scope.getCaptchaSrc = function() {
		sessionService.getCaptchaSrc(function(result) {
			if (result.ok) {
				$scope.captchaSrc = result.data;
				$scope.record.captcha = "";
			}
		});
	};
	
	$scope.processForm = function() {
		switch ($state.current.name) {
			case 'form.identify':
				$scope.checkIdentity(function(){
					if ($scope.usernameOk && ! $scope.emailExists) {
						$state.go('form.register');
						$scope.getCaptchaSrc();
					} else if ($scope.usernameExists && ! $scope.usernameExistsOnThisSite && 
					    $scope.allowSignupFromOtherSites && $scope.emailIsEmpty) {
						$state.go('form.activate');
					} else if ($scope.usernameExists && ! $scope.usernameExistsOnThisSite && 
					    $scope.allowSignupFromOtherSites && $scope.emailMatchesAccount) {
						$state.go('form.login');
					} else {
						// error messages
						if ($scope.usernameExists) {
							$scope.signupForm.username.$setPristine();
						}
						if ($scope.emailExists) {
							$scope.signupForm.email.$setPristine();
						}
					}
				});
				break;
			case 'form.register':
				registerUser(function() {
					$state.go('validate');
				});
				break;
			case 'form.activate':
				activateUser(function() {
					$state.go('validate');
				});
				break;
			case 'form.login':
				activateUser(function() {
					console.log('activate and login');
				});
				break;
			default:
				break;
		}
	};
	
	function registerUser(successCallback) {
		$scope.submissionInProgress = true;
		userService.register($scope.record, function(result) {
			$scope.submissionInProgress = false;
			if (result.ok) {
				if (!result.data) {
					notice.push(notice.WARN, "The image verification failed.  Please try again");
					$scope.getCaptchaSrc();
				} else {
					$scope.submissionComplete = true;
					(successCallback || angular.noop)();
				}
			}
		});
	};
	
	function activateUser(successCallback) {
		$scope.submissionInProgress = true;
		userService.activate($scope.record.username, $scope.record.password, $scope.record.email, function(result) {
			$scope.submissionInProgress = false;
			if (result.ok) {
				if (!result.data) {
					notice.push(notice.ERROR, "Login failed.<br /><br />If this is NOT your account, click <b>Back</b> to create a different account.");
				} else {
					$scope.submissionComplete = true;
					(successCallback || angular.noop)();
				}
			}
		});
	};
	
	$scope.checkIdentity = function(callback) {
		$scope.usernameOk = false;
		$scope.usernameExists = false;
		$scope.usernameExistsOnThisSite = false;
		$scope.allowSignupFromOtherSites = false;
		$scope.emailExists = false;
		$scope.emailIsEmpty = true;
		$scope.emailMatchesAccount = false;
		if ($scope.record.username) {
			$scope.usernameLoading = true;
			if (! $scope.record.email) {
				$scope.record.email = '';
			}
			userService.identityCheck($scope.record.username, $scope.record.email, function(result) {
				$scope.usernameLoading = false;
				if (result.ok) {
					$scope.usernameExists = result.data.usernameExists;
					$scope.usernameOk = ! $scope.usernameExists;
					$scope.usernameExistsOnThisSite = result.data.usernameExistsOnThisSite;
					$scope.allowSignupFromOtherSites = result.data.allowSignupFromOtherSites;
					$scope.emailExists = result.data.emailExists;
					$scope.emailIsEmpty = result.data.emailIsEmpty;
					$scope.emailMatchesAccount = result.data.emailMatchesAccount;
				}
				(callback || angular.noop)();
			});
		}
	};
	
}])
;
