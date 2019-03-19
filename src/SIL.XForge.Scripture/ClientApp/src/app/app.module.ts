import { DatePipe } from '@angular/common';
import { HttpClientModule } from '@angular/common/http';
import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { ServiceWorkerModule } from '@angular/service-worker';

import { UICommonModule } from 'xforge-common/ui-common.module';
import { xForgeCommonEntryComponents, XForgeCommonModule } from 'xforge-common/xforge-common.module';
import { environment } from '../environments/environment';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { CheckingModule } from './checking/checking.module';
import { ConnectProjectComponent } from './connect-project/connect-project.component';
import { CoreModule } from './core/core.module';
import { NavMenuComponent } from './nav-menu/nav-menu.component';
import { ProjectDeletedDialogComponent } from './nav-menu/project-deleted-dialog/project-deleted-dialog.component';
import { DeleteProjectDialogComponent } from './project-settings/delete-project-dialog/delete-project-dialog.component';
import { ProjectSettingsComponent } from './project-settings/project-settings.component';
import { ProjectComponent } from './project/project.component';
import { ScriptureChooserDialogComponent } from './scripture-chooser-dialog/scripture-chooser-dialog.component';
import { SharedModule } from './shared/shared.module';
import { StartComponent } from './start/start.component';
import { SyncComponent } from './sync/sync.component';
import { TranslateModule } from './translate/translate.module';

@NgModule({
  declarations: [
    AppComponent,
    NavMenuComponent,
    ConnectProjectComponent,
    DeleteProjectDialogComponent,
    ProjectSettingsComponent,
    ProjectComponent,
    SyncComponent,
    StartComponent,
    ProjectDeletedDialogComponent,
    ScriptureChooserDialogComponent
  ],
  imports: [
    AppRoutingModule,
    BrowserModule.withServerTransition({ appId: 'ng-cli-universal' }),
    BrowserAnimationsModule,
    CoreModule,
    HttpClientModule,
    // not ready for production yet - 2018-11 IJH
    ServiceWorkerModule.register('ngsw-worker.js', { enabled: environment.pwaTest }), // || environment.production }),
    SharedModule,
    TranslateModule,
    CheckingModule,
    UICommonModule,
    XForgeCommonModule
  ],
  providers: [DatePipe],
  entryComponents: [
    DeleteProjectDialogComponent,
    ProjectDeletedDialogComponent,
    ScriptureChooserDialogComponent,
    ...xForgeCommonEntryComponents
  ],
  bootstrap: [AppComponent]
})
export class AppModule {}
