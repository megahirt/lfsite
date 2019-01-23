import { Component, OnInit, ViewChild } from '@angular/core';
import { Router } from '@angular/router';
import { Observable } from 'rxjs';

import { AuthService } from '@xforge-common/auth.service';
import { LocationService } from '@xforge-common/location.service';
import { SubscriptionDisposable } from '@xforge-common/subscription-disposable';
import { SFUser } from './core/models/sfuser';
import { SFUserService } from './core/sfuser.service';
import { NavMenuComponent } from './nav-menu/nav-menu.component';

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent extends SubscriptionDisposable implements OnInit {
  @ViewChild(NavMenuComponent) navMenu: NavMenuComponent;
  title = 'Scripture Forge';
  today = new Date();
  version = '9.9.9';

  currentUser$: Observable<SFUser>;

  constructor(
    private readonly router: Router,
    private readonly authService: AuthService,
    private readonly locationService: LocationService,
    private readonly sfUserService: SFUserService
  ) {
    super();
  }

  async ngOnInit(): Promise<void> {
    this.authService.init();
    if (await this.isLoggedIn) {
      this.currentUser$ = this.sfUserService.getCurrentUser();
    }
  }

  get isLoggedIn(): Promise<boolean> {
    return this.authService.isLoggedIn;
  }

  logOut(): void {
    this.authService.logOut();
  }

  async goHome(): Promise<void> {
    (await this.isLoggedIn) ? this.router.navigateByUrl('/home') : this.locationService.go('/');
  }
}
