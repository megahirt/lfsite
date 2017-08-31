'use strict';

module.exports = new BellowsChangePasswordPage();

function BellowsChangePasswordPage() {
  var expectedCondition = protractor.ExpectedConditions;
  var CONDITION_TIMEOUT = 3000;

  // TODO: this will likely change when we refactor the display of notifications - cjh 2014-06
  this.get = function get() {
    browser.get(browser.baseUrl + '/app/changepassword');
    browser.wait(expectedCondition.visibilityOf(this.password), CONDITION_TIMEOUT);
  };

  this.form = element(by.tagName('form'));
  this.password = element(by.model('vars.password'));
  this.confirm = element(by.model('vars.confirm_password'));
  this.passwordMatchImage = element(by.id('passwordMatch'));
  this.submitButton = element(by.partialButtonText('Change Password'));
  this.noticeList = element.all(by.repeater('notice in $ctrl.notices()'));
}
