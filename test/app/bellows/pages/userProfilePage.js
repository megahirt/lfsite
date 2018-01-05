'use strict';

module.exports = new SfUserProfilePage();

/**
 * This object handles the user profile page and provides methods to access items in the activity
 * list
 */
function SfUserProfilePage() {
  var util = require('./util');
  var expectedCondition = protractor.ExpectedConditions;
  var CONDITION_TIMEOUT = 3000;

  this.userProfileURL = '/app/userprofile';
  this.activitiesList = element.all(by.repeater('item in filteredActivities'));

  // Navigate to the MyProfile page (defaults to My Account tab)
  this.get = function get() {
    browser.get(browser.baseUrl + this.userProfileURL);
  };

  // Navigate to the MyProfile -> My Account page
  this.getMyAccount = function getMyAccount() {
    this.get();
  };

  this.tabs = {
    myAccount:    element(by.id('myAccountTab')),
    aboutMe:      element(by.id('AboutMeTab'))
  };

  // Navigate to the MyProfile -> About Me page
  this.getAboutMe = function getAboutMe() {
    this.get();
    this.tabs.aboutMe.click();
    browser.wait(expectedCondition.visibilityOf(this.aboutMeTab.fullName), CONDITION_TIMEOUT);
  };

  this.blueElephantAvatarUri = '/Site/views/shared/image/avatar/DodgerBlue-elephant-128x128.png';
  this.goldPigAvatarUri = '/Site/views/shared/image/avatar/gold-pig-128x128.png';

  this.myAccountTab = {
    emailInput:       element(by.id('email')),
    username:         element(by.id('username')),

    emailTaken:  element(by.id('emailTaken')),
    usernameTaken: element(by.id('usernameTaken')),

    avatarColor:      element(by.id('user-profile-avatar-color')),
    avatarShape:      element(by.id('user-profile-avatar-shape')),
    avatar:           element(by.id('avatarRef')),

    // Jamaican mobile phone number will move to Project scope
    mobilePhoneInput: element(by.id('mobile_phone')),

    // Contact preferences
    emailBtn:         element(by.id('EmailButton')),
    SMSBtn:           element(by.id('SMSButton')),
    bothBtn:          element(by.id('BothButton')),
    saveBtn:          element(by.id('saveBtn'))
  };

  this.myAccountTab.selectColor = function (newColor) {
    util.clickDropdownByValue(this.myAccountTab.avatarColor, newColor);
  }.bind(this);

  this.myAccountTab.selectShape = function (newShape) {
    util.clickDropdownByValue(this.myAccountTab.avatarShape, newShape);
  }.bind(this);

  // For some reason, the values sent with util.sendText weren't consistently being saved.
  // Reverting to sendKeys for now...

  this.myAccountTab.updateEmail = function (newEmail) {
    browser.wait(expectedCondition.visibilityOf(this.myAccountTab.emailInput), CONDITION_TIMEOUT);
    this.myAccountTab.emailInput.sendKeys(protractor.Key.chord(protractor.Key.CONTROL, 'a'));
    this.myAccountTab.emailInput.sendKeys(newEmail);

    // click another field to force validation
    this.myAccountTab.username.click();
  }.bind(this);

  this.myAccountTab.updateUsername = function (newUsername) {
    browser.wait(expectedCondition.visibilityOf(this.myAccountTab.username), CONDITION_TIMEOUT);
    this.myAccountTab.username.sendKeys(protractor.Key.chord(protractor.Key.CONTROL, 'a'));
    this.myAccountTab.username.sendKeys(newUsername);

    // click another field to force validation
    this.myAccountTab.emailInput.click();
  }.bind(this);

  this.myAccountTab.updateMobilePhone = function (newPhone) {
    browser.wait(expectedCondition.visibilityOf(this.myAccountTab.mobilePhoneInput),
      CONDITION_TIMEOUT);
    this.myAccountTab.mobilePhoneInput.sendKeys(newPhone);
  }.bind(this);

  this.myAccountTab.updateContactPreference = function () {
    this.myAccountTab.bothBtn.click();
  }.bind(this);

  this.aboutMeTab = {
    fullName: element(by.id('fullname')),
    age:      element(by.id('age')),
    gender:   element(by.id('gender')),
    saveBtn:  element(by.id('saveBtn'))
  };

  this.aboutMeTab.updateFullName = function (newFullName) {
    browser.wait(expectedCondition.visibilityOf(this.aboutMeTab.fullName), CONDITION_TIMEOUT);
    this.aboutMeTab.fullName.sendKeys(protractor.Key.chord(protractor.Key.CONTROL, 'a'));
    this.aboutMeTab.fullName.sendKeys(newFullName);
  }.bind(this);

  this.aboutMeTab.updateAge = function (newAge) {
    browser.wait(expectedCondition.visibilityOf(this.aboutMeTab.age), CONDITION_TIMEOUT);
    this.aboutMeTab.age.sendKeys(protractor.Key.chord(protractor.Key.CONTROL, 'a'));
    this.aboutMeTab.age.sendKeys(newAge);
  }.bind(this);

  this.aboutMeTab.updateGender = function (newGender) {
    util.clickDropdownByValue(this.aboutMeTab.gender, newGender);
  }.bind(this);
}
