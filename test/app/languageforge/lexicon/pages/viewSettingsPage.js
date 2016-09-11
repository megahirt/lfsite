'use strict';

function ViewSettingsPage() {
  var _this = this;

  this.settingsMenuLink = $('.hdrnav a.btn i.icon-cog');
  this.viewSettingsLink = element(by.linkText('View Settings'));
  this.get = function get() {
    this.settingsMenuLink.click();
    this.viewSettingsLink.click();
  };
  this.backToDictionaryBtn = element(by.buttonText('Dictionary'));

  this.tabDivs = element.all(by.repeater('tab in tabs'));
  this.applyBtn = element(by.buttonText('Apply'));

  this.getTabByName = function getTabByName(tabName) {
    return $('div.tabbable ul.nav-tabs').element(by.cssContainingText('a', tabName));
  };

  this.tabs = {
      observer: {
        go: function() {
          _this.getTabByName('Observer').click();
        }
      },
      contributor: {
        go: function() {
          _this.getTabByName('Contributor').click();
        }
      },
      manager: {
        go: function() {
          _this.getTabByName('Manager').click();
        },
        showAllFieldsBtn: this.tabDivs.get(3).element(by.buttonText('Show All Fields'))
      },
  };
  
  this.observerTab = {
      showAllFieldsBtn: this.tabDivs.get(0).element(by.buttonText('Show All Fields'))
  };
  
  this.managerTab = {
  };
  
  this.showAllFieldsBtn = element(by.buttonText('Show All Fields'));
  this.showCommonFieldsBtn = element(by.buttonText('Show Only Common Fields'));

  this.activePane = $('div.tab-pane.active');

  this.accordionDiv = this.activePane.$('div.accordion');
  this.accordionEnabledFields = this.accordionDiv.element(by.elemMatches('div.accordion-heading a', '^Enabled Fields for'));
  this.accordionEnabledTasks = this.accordionDiv.element(by.elemMatches('div.accordion-heading a', '^Enabled Tasks'));

  this.entryFields = this.activePane.all(by.repeater('fieldName in fieldOrder.entry'));
  this.senseFields = this.activePane.all(by.repeater('fieldName in fieldOrder.senses'));
  this.exampleFields = this.activePane.all(by.repeater('fieldName in fieldOrder.examples'));
  
  /** Second parameter is optional, default false. If true, fieldName will be considered
   * a regular expression that should not be touched. If false or unspecified, fieldName
   * will be considered an exact match (so "Etymology" should not match "Etymology Comment").
   */
  this.getFieldByName = function getFieldByName(fieldName, treatAsRegex) {
    var fieldRegex = (treatAsRegex ? fieldName : '^'+fieldName+'$');
    return $('div.tab-pane.active dl.picklists').element(by.elemMatches('div[data-ng-repeat]', fieldRegex));
  };
  this.clickFieldByName = function clickFieldByName(fieldName, treatAsRegex) {
    return this.getFieldByName(fieldName, treatAsRegex).click();
  };

  this.showField = this.activePane.element(by.cssContainingText('label.checkbox', 'Show field')).$('input[type="checkbox"]');
  this.overrideInputSystems = this.activePane.element(by.cssContainingText('label.checkbox', 'Override Input Systems')).$('input[type="checkbox"]');

  this.usersWithViewSettings = this.activePane.$('#userSelectList');
  this.addViewSettingsForMember = function addViewSettingsForMember(memberName) {
    this.activePane.$('div.typeahead input').sendKeys(memberName);
    this.activePane.$('div.typeahead').all(by.repeater('user in typeahead.users')).first().click();
    this.activePane.element(by.buttonText('Add Member Specific Settings')).click();
  };
  this.pickMemberWithViewSettings = function pickMemberWithViewSettings(memberName) {
    this.usersWithViewSettings.element(by.elemMatches('div.picklists > ul.unstyled > li', memberName)).click();
  };
  this.selectMemberBtn = this.activePane.element(by.buttonText('Select Member'));
  this.removeMemberViewSettingsBtn = this.activePane.element(by.buttonText('Remove Member Specific Settings'));
};

module.exports = new ViewSettingsPage();