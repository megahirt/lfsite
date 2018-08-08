'use strict';

angular.module('palaso.ui.archiveProject', ['coreModule'])
  .directive('puiArchiveProject', [function () {
    return {
      restrict: 'E',
      templateUrl: '/angular-app/bellows/shared/archive-project.component.html',
      scope: {
        puiActionInProgress: '='
      },
      controller: ['$scope', 'projectService',
        'silNoticeService', 'modalService', '$window',
        function ($scope, projectService, notice, modalService, $window) {

          // Archive the project
          $scope.archiveProject = function () {
            var message = 'Are you sure you want to archive this project?';
            var modalOptions = {
              closeButtonText: 'Cancel',
              actionButtonText: 'Archive',
              headerText: 'Archive Project?',
              bodyText: message
            };
            modalService.showModal({}, modalOptions).then(function () {
              $scope.puiActionInProgress = true;
              projectService.archiveProject(function (result) {
                if (result.ok) {
                  notice.push(notice.SUCCESS, 'The project was archived successfully');
                  $window.location.href = '/app/projects';
                } else {
                  $scope.puiActionInProgress = false;
                }
              });
            }, angular.noop);
          };
        }]
    };
  }])

;
