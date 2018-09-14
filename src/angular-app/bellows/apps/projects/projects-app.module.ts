import * as angular from 'angular';

import {BreadcrumbModule} from '../../core/breadcrumbs/breadcrumb.module';
import {CoreModule} from '../../core/core.module';
import {NoticeModule} from '../../core/notice/notice.module';
import {ListViewModule} from '../../shared/list-view.component';
import {PuiUtilityModule} from '../../shared/utils/pui-utils.module';
import {ProjectsAppComponent} from './projects-app.component';

export const ProjectsAppModule = angular
  .module('projects', [
    'ui.bootstrap',
    CoreModule,
    ListViewModule,
    NoticeModule,
    PuiUtilityModule,
    BreadcrumbModule
  ])
  .component('projectsApp', ProjectsAppComponent)
  .name;
