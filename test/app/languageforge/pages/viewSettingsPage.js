'use strict';

var util = require('../../bellows/pages/util');

var ViewSettingsPage = function() {
  var page = this;

  this.settingsMenuLink = $('.hdrnav a.btn i.icon-cog');
  this.viewSettingsLink = element(by.linkText('View Settings'));
  this.get = function get() {
    this.settingsMenuLink.click();
    this.viewSettingsLink.click();
  }

  this.tabDivs = element.all(by.repeater('tab in tabs'));
  this.applyBtn = element(by.buttonText('Apply'));

  this.getTabByName = function getTabByName(tabName) {
    return $('div.tabbable ul.nav-tabs').element(by.cssContainingText('a', tabName));
  };
  this.clickTabByName = function clickTabByName(tabName) {
    return page.getTabByName(tabName).then(function(elem) { elem.click(); });
  };

  this.showAllFieldsBtn = element(by.buttonText('Show All Fields'));
  this.showCommonFieldsBtn = element(by.buttonText('Show Only Common Fields'));

  this.activePane = $('div.tab-pane.active');

  this.entryFields = this.activePane.all(by.repeater('fieldName in fieldOrder.entry'));
  this.senseFields = this.activePane.all(by.repeater('fieldName in fieldOrder.senses'));
  this.exampleFields = this.activePane.all(by.repeater('fieldName in fieldOrder.examples'));
  this.getFieldByName = function getFieldByName(fieldName, treatAsRegex) {
    // Second parameter is optional, default false. If true, fieldName will be considered
    // a regular expression that should not be touched. If false or unspecified, fieldName
    // will be considered an exact match (so "Etymology" should not match "Etymology Comment").
    var fieldRegex = (treatAsRegex ? fieldName : '^'+fieldName+'$');
    return $('div.tab-pane.active dl.picklists').element(by.elemMatches('div[data-ng-repeat]', fieldRegex));
  };
  this.clickFieldByName = function clickFieldByName(fieldName, treatAsRegex) {
    // Second parameter just as in getFieldByName()
    return this.getFieldByName(fieldName, treatAsRegex).then(function(elem) { elem.click(); });
  };

  this.showField = this.activePane.element(by.cssContainingText('label.checkbox', 'Show field')).$('input[type="checkbox"]');
  this.overrideInputSystems = this.activePane.element(by.cssContainingText('label.checkbox', 'Override Input Systems')).$('input[type="checkbox"]');
};

module.exports = new ViewSettingsPage();