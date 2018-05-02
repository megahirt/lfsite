import * as angular from 'angular';
import uiRouter from 'angular-ui-router';

import {BreadcrumbModule} from '../../core/breadcrumbs/breadcrumb.module';
import {CoreModule} from '../../core/core.module';
import {NoticeModule} from '../../core/notice/notice.module';
import {UserManagementJoinRequestsComponent} from './join-requests.component';
import {UserManagementMembersComponent} from './members.component';
import {UserManagementAppComponent} from './user-management-app.component';

export const UserManagementAppModule = angular
  .module('usermanagement',['ui.router', 'ui.bootstrap', CoreModule,
    NoticeModule, 'palaso.ui.listview', 'palaso.ui.typeahead', BreadcrumbModule, 'lexiconCoreModule'
  ])
  .component('userManagementApp', UserManagementAppComponent)
  .component('userManagementMembers', UserManagementMembersComponent)
  .component('userManagementJoinRequests', UserManagementJoinRequestsComponent)
  .config(['$stateProvider', '$urlRouterProvider',
    ($stateProvider: angular.ui.IStateProvider, $urlRouterProvider: angular.ui.IUrlRouterProvider) => {
      console.log('config uiRouter:', uiRouter);
      $urlRouterProvider.otherwise('/members');

      $stateProvider
        .state('members', {
          url: '/members',
          views: {
            '@': {
              template: `<user-management-members query-user-list="$ctrl.queryUserList()"
                list="$ctrl.list" project="$ctrl.project" roles="$ctrl.roles" rights="$ctrl.rights">
                </user-management-members>`
            }
          }
        })
        .state('joinRequests', {
          url: '/joinRequests',
          views: {
            '@': {
              template: `<user-management-join-requests join-requests="$ctrl.joinRequests"
                roles="$ctrl.roles" rights="$ctrl.rights"></user-management-join-requests>`
            }
          }
        })
      ;
    }
  ])
  .name;
