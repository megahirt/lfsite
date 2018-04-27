import * as angular from 'angular';

import {CoreModule} from '../../core/core.module';
import {NoticeModule} from '../../core/notice/notice.module';
import {PuiUtilityModule} from '../../shared/utils/pui-utils.module';
import {ProjectsAppComponent} from './projects-app.component';

export const ProjectsAppModule = angular
  .module('projects', ['ui.bootstrap', CoreModule,
    'palaso.ui.listview', NoticeModule, PuiUtilityModule, 'sgw.ui.breadcrumb'
  ])
  .component('projectsApp', ProjectsAppComponent)
  .name;
