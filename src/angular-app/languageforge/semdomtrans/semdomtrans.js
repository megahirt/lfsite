'use strict';

angular.module('semdomtrans',
  [
    'ui.router',
    'coreModule',
    'semdomtrans.edit',
    'semdomtrans.comments',
    'semdomtrans.services',
    'semdomtrans.review',
    'pascalprecht.translate'
  ])
  .config(['$stateProvider', '$urlRouterProvider', function ($stateProvider, $urlRouterProvider) {

    $urlRouterProvider.otherwise('/edit');

    $stateProvider
        .state('editor', {
          url: '/edit',
          views: {
              '@': { templateUrl: '/angular-app/languageforge/semdomtrans/views/edit.html' },
              'editItem@editor': {
                templateUrl: '/angular-app/languageforge/semdomtrans/views/partials/editItem.html'
              },
              'editFilter@editor': {
                templateUrl: '/angular-app/languageforge/semdomtrans/views/partials/editFilter.html'
              }
            }
        })
        .state('editor.editItem', {
          url: '/:position'
        })
        .state('comments', {
          url: '/comments/:position',
          views: {
              '': { templateUrl: '/angular-app/languageforge/semdomtrans/views/comments.html' }
            }
        })

        .state('review',  {
          url: '/review',
          views: {
              '': { templateUrl: '/angular-app/languageforge/semdomtrans/views/review.html' }
            }
        });
  }])
  .controller('MainCtrl', ['$scope', 'semdomtransEditorDataService', 'semdomtransEditService',
    'sessionService', 'lexCommentService', 'offlineCache', '$q', 'silNoticeService',
  function ($scope, editorDataService, editorApi,
            ss, commentsSerivce, offlineCache, $q, notice) {

    $scope.items = editorDataService.entries;
    $scope.workingSets = editorDataService.workingSets;
    $scope.itemsTree = editorDataService.itemsTree;

    if ($scope.items.length === 0 && !$scope.loadingDto) {
      editorDataService.loadEditorData().then(function (result) {
        editorDataService.processEditorDto(result);
      });
    }

    $scope.exportProject = function exportProject() {
      notice.setLoading('Exporting Semantic Domain Data to XML File');
      editorApi.exportProject(function (result) {
        notice.cancelLoading();
        if (result.ok) {
          window.location = 'http://' + result.data;
        }
      });
    };

    $scope.includedItems = {};
    $scope.loadingDto = false;

    // permissions stuff
    ss.getSession().then(function (session) {
        $scope.rights = {
          canEditProject: function canEditProject() {
            return session.hasProjectRight(ss.domain.PROJECTS, ss.operation.EDIT);
          },

          canEditEntry: function canEditEntry() {
            return session.hasProjectRight(ss.domain.ENTRIES, ss.operation.EDIT);
          },

          canDeleteEntry: function canDeleteEntry() {
            return session.hasProjectRight(ss.domain.ENTRIES, ss.operation.DELETE);
          },

          canComment: function canComment() {
            return session.hasProjectRight(ss.domain.COMMENTS, ss.operation.CREATE);
          },

          canDeleteComment: function canDeleteComment(commentAuthorId) {
            if (session.userId() === commentAuthorId) {
              return session.hasProjectRight(ss.domain.COMMENTS, ss.operation.DELETE_OWN);
            } else {
              return session.hasProjectRight(ss.domain.COMMENTS, ss.operation.DELETE);
            }
          },

          canEditComment: function canEditComment(commentAuthorId) {
            if (session.userId() === commentAuthorId) {
              return session.hasProjectRight(ss.domain.COMMENTS, ss.operation.EDIT_OWN);
            } else {
              return false;
            }
          },

          canUpdateCommentStatus: function canUpdateCommentStatus() {
            return session.hasProjectRight(ss.domain.COMMENTS, ss.operation.EDIT);
          }
        };
        $scope.project = session.project();
        $scope.projectSettings = session.projectSettings();
        $scope.currentUserRole = session.projectSettings().currentUserRole;
      });
  }])

// not sure if we need breadcrumbs for this app
  .controller('BreadcrumbCtrl', ['$scope', '$rootScope', 'breadcrumbService',
  function ($scope, $rootScope, breadcrumbService) {
    $scope.idmap = breadcrumbService.idmap;
    $rootScope.$on('$routeChangeSuccess', function () {
      $scope.breadcrumbs = breadcrumbService.read();
    });

    $scope.$watch('idmap', function () {
      $scope.breadcrumbs = breadcrumbService.read();
    }, true);
  }])

  ;
