import { browser, by, element, promise } from 'protractor';

export class AppPage {

  static homepage = {
    homepageHeader: element(by.css('app-root h1')),
    logoutButton: element(by.id('logout')),
    changePasswordButton: element(by.id('home-change-password-btn'))
  };

  static navigateTo(): promise.Promise<any> {
    return browser.get('https://beta.scriptureforge.local/');
  }

  static getMainHeading(): promise.Promise<string> {
    return this.homepage.homepageHeader.getText();
  }
}
