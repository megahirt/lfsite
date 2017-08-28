'use strict';

angular.module('projects', ['bellows.services', 'palaso.ui.listview', 'ui.bootstrap',
  'palaso.ui.notice', 'palaso.ui.utils'
])
  .controller('ProjectsCtrl', ['$scope', '$window', 'projectService', 'sessionService',
    'silNoticeService',
  function ($scope, $window, projectService, ss,
            notice) {
    $scope.finishedLoading = false;
    $scope.rights = {};
    $scope.projectTypeNames = projectService.data.projectTypeNames;
    $scope.projectTypesBySite = projectService.data.projectTypesBySite;

    ss.getSession().then(function (session) {
      $scope.rights.edit = session.hasSiteRight(ss.domain.PROJECTS, ss.operation.EDIT);
      $scope.rights.create = session.hasSiteRight(ss.domain.PROJECTS, ss.operation.CREATE);
      $scope.rights.showControlBar = $scope.rights.create;
      $scope.siteName = session.baseSite();
    });

    // Listview Selection
    $scope.newProjectCollapsed = true;
    $scope.selected = [];
    $scope.updateSelection = function (event, item) {
      var selectedIndex = $scope.selected.indexOf(item);
      var checkbox = event.target;
      if (checkbox.checked && selectedIndex === -1) {
        $scope.selected.push(item);
      } else if (!checkbox.checked && selectedIndex !== -1) {
        $scope.selected.splice(selectedIndex, 1);
      }
    };

    $scope.isSelected = function (item) {
      // noinspection EqualityComparisonWithCoercionJS
      return item != null && $scope.selected.indexOf(item) >= 0;
    };

    // Listview Data
    $scope.projects = [];
    $scope.queryProjectsForUser = function () {
      projectService.list().then(function (projects) {
        $scope.projects = projects;

        // Is this perhaps wrong? Maybe not all projects are included in the JSONRPC response?
        // That might explain the existance of the previous result.data.count
        $scope.projectCount = projects.length;
        $scope.finishedLoading = true;
      }).catch(console.error);
    };

    $scope.isInProject = function (project) {
      return (project.role !== 'none');
    };

    $scope.isManager = function (project) {
      return (project.role === 'project_manager');
    };

    // Add user as Manager of project
    $scope.addManagerToProject = function (project) {
      projectService.joinProject(project.id, 'project_manager', function (result) {
        if (result.ok) {
          notice.push(notice.SUCCESS, 'You are now a Manager of the ' + project.projectName +
            ' project.');
          $scope.queryProjectsForUser();
        }
      });
    };

    // Add user as Member of project
    $scope.addMemberToProject = function (project) {
      projectService.joinProject(project.id, 'contributor', function (result) {
        if (result.ok) {
          notice.push(notice.SUCCESS, 'You are now a Contributor for the ' + project.projectName +
            ' project.');
          $scope.queryProjectsForUser();
        }
      });
    };

    $scope.startProject = function startProject() {
      if ($scope.projectTypesBySite().length === 1) {
        var appName = $scope.projectTypesBySite()[0];
        $window.location.href = '/app/' + appName + '/new-project';
      } else {
        $scope.newProjectCollapsed = !$scope.newProjectCollapsed;
      }
    };

  }])

  ;
