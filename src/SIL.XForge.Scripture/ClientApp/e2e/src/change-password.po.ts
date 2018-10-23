import { browser, by, element, ExpectedConditions } from 'protractor';

export class ChangePasswordPage {
  private static readonly baseUrl = 'https://beta.scriptureforge.local';
  private readonly constants = require('../testConstants.json');

  changePasswordButton = element(by.id('btnChangePassword'));
  newPasswordInput = element(by.id('newPassword'));
  confirmPasswordInput = element(by.id('confirmPassword'));
  successMessage = element(by.css('div simple-snack-bar'));

  async get() {
    await browser.get(ChangePasswordPage.baseUrl + '/change-password');
    await browser.wait(ExpectedConditions.visibilityOf(this.newPasswordInput), this.constants.conditionTimeout);
  }
}
