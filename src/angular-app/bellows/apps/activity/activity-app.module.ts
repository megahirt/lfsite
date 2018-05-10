import * as angular from 'angular';
import 'angular-moment-picker/dist/angular-moment-picker.css';
import 'angular-moment-picker/dist/angular-moment-picker.js';

import {BreadcrumbModule} from '../../core/breadcrumbs/breadcrumb.module';
import {CoreModule} from '../../core/core.module';
import {ActivityAppComponent} from './activity-app.component';
import {ActivityContainerComponent} from './activity-container.compontent';

export const ActivityAppModule = angular
  .module('activity', [
    'ngRoute',
    'ui.bootstrap',
    'moment-picker',
    CoreModule,
    BreadcrumbModule
  ])
  .component('activityApp', ActivityAppComponent)
  .component('activityContainer', ActivityContainerComponent)
  .name;
