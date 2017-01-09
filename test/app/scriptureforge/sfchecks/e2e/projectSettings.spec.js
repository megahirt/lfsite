'use strict';

describe('the project settings page - project manager', function () {
  var constants       = require('../../../testConstants.json');
  var loginPage       = require('../../../bellows/pages/loginPage.js');
  var util            = require('../../../bellows/pages/util.js');
  var projectSettingsPage = require('../pages/projectSettingsPage.js');
  var expectedCondition = protractor.ExpectedConditions;
  var CONDITION_TIMEOUT = 3000;

  it('setup: logout, login as project manager, go to project settings', function () {
    loginPage.logout();
    loginPage.loginAsManager();
    projectSettingsPage.get(constants.testProjectName);
  });

  describe('members tab', function () {
    var memberCount = 0;

    it('setup: click on tab', function () {
      expect(projectSettingsPage.tabs.members.isPresent()).toBe(true);
      projectSettingsPage.tabs.members.click();
    });

    it('can list project members', function () {
      expect(projectSettingsPage.membersTab.list.count()).toBeGreaterThan(0);
      projectSettingsPage.membersTab.list.count().then(function (val) { memberCount = val; });
    });

    it('can filter the list of members', function () {
      expect(projectSettingsPage.membersTab.list.count()).toBe(memberCount);
      projectSettingsPage.membersTab.listFilter.sendKeys(constants.managerUsername);
      expect(projectSettingsPage.membersTab.list.count()).toBe(1);
      projectSettingsPage.membersTab.listFilter.clear();
    });

    it('can add a new user as a member', function () {
      expect(projectSettingsPage.membersTab.list.count()).toBe(memberCount);
      projectSettingsPage.membersTab.addButton.click();
      projectSettingsPage.membersTab.newMember.input.sendKeys('du');

      // sendKeys is split to force correct button behaviour. IJH 2015-10
      projectSettingsPage.membersTab.newMember.input.sendKeys('de');
      projectSettingsPage.membersTab.newMember.button.click();
      projectSettingsPage.membersTab.waitForNewUserToLoad(memberCount);
      expect(projectSettingsPage.membersTab.list.count()).toBe(memberCount + 1);
    });

    it('can not add the same user twice', function () {
      projectSettingsPage.membersTab.newMember.input.clear();
      projectSettingsPage.membersTab.newMember.input.sendKeys('dude');
      expect(projectSettingsPage.membersTab.newMember.button.isEnabled()).toBeFalsy();
      expect(projectSettingsPage.membersTab.newMember.warning.isDisplayed()).toBeTruthy();
      projectSettingsPage.membersTab.newMember.input.clear();
    });

    it('can change the role of a member', function () {
      projectSettingsPage.membersTab.listFilter.sendKeys('dude');
      util.clickDropdownByValue(projectSettingsPage.membersTab.list.first()
        .element(by.model('user.role')), 'Manager');
      expect(projectSettingsPage.membersTab.list.first().element(by.model('user.role'))
        .element(by.css('option:checked')).getText()).toEqual('Manager');
      projectSettingsPage.membersTab.listFilter.clear();
    });

    it('can remove a member', function () {
      projectSettingsPage.membersTab.listFilter.sendKeys('dude');
      projectSettingsPage.membersTab.list.first().element(by.css('input[type="checkbox"]')).click();
      projectSettingsPage.membersTab.removeButton.click();
      projectSettingsPage.membersTab.listFilter.clear();
      projectSettingsPage.membersTab.listFilter.sendKeys('dude');
      expect(projectSettingsPage.membersTab.list.count()).toBe(0);
      projectSettingsPage.membersTab.listFilter.clear();
      expect(projectSettingsPage.membersTab.list.count()).toBe(memberCount);
    });

    //it('can message selected user', function() {});  // how can we test this? - cjh

  });

  describe('question templates tab', function () {
    it('setup: click on tab', function () {
      expect(projectSettingsPage.tabs.templates.isPresent()).toBe(true);
      projectSettingsPage.tabs.templates.click();
    });

    it('can list templates', function () {
      expect(projectSettingsPage.templatesTab.list.count()).toBe(2);
    });

    it('can add a template', function () {
      projectSettingsPage.templatesTab.addButton.click();
      browser.wait(expectedCondition.visibilityOf(projectSettingsPage.templatesTab.editor.title),
        CONDITION_TIMEOUT);
      projectSettingsPage.templatesTab.editor.title.sendKeys('sound check');
      projectSettingsPage.templatesTab.editor.description
        .sendKeys('What do you think of when I say the words... "boo"');
      projectSettingsPage.templatesTab.editor.saveButton.click();
      expect(projectSettingsPage.templatesTab.list.count()).toBe(3);
      expect(projectSettingsPage.templatesTab.editor.saveButton.isDisplayed()).toBe(false);
    });

    it('can update an existing template', function () {
      projectSettingsPage.templatesTab.list.last().element(by.linkText('sound check')).click();
      browser.wait(expectedCondition.visibilityOf(
        projectSettingsPage.templatesTab.editor.saveButton), CONDITION_TIMEOUT);
      expect(projectSettingsPage.templatesTab.editor.saveButton.isDisplayed()).toBe(true);
      projectSettingsPage.templatesTab.editor.title.clear();
      projectSettingsPage.templatesTab.editor.title.sendKeys('test12');
      projectSettingsPage.templatesTab.editor.saveButton.click();
      browser.wait(expectedCondition.invisibilityOf(
        projectSettingsPage.templatesTab.editor.saveButton), CONDITION_TIMEOUT);
      expect(projectSettingsPage.templatesTab.editor.saveButton.isDisplayed()).toBe(false);
      expect(projectSettingsPage.templatesTab.list.count()).toBe(3);
    });

    it('can delete a template', function () {
      projectSettingsPage.templatesTab.list.last().element(by.css('input[type="checkbox"]'))
        .click();
      projectSettingsPage.templatesTab.removeButton.click();
      expect(projectSettingsPage.templatesTab.list.count()).toBe(2);
    });

  });

  // The Archived Texts tab is tested as part of a process in the Project page tests. IJH 2014-06

  describe('project properties tab', function () {
    var newName = constants.thirdProjectName;

    it('setup: click on tab', function () {
      loginPage.loginAsManager();
      projectSettingsPage.get(constants.testProjectName);
      expect(projectSettingsPage.tabs.project.isPresent()).toBe(true);
      projectSettingsPage.tabs.project.click();
    });

    it('can read properties', function () {
      expect(projectSettingsPage.projectTab.name.getAttribute('value'))
        .toBe(constants.testProjectName);

      //expect(projectSettingsPage.projectTab.featured.getAttribute('checked')).toBeFalsy();
      expect(projectSettingsPage.projectTab.allowAudioDownload.getAttribute('checked'))
        .toBeTruthy();
    });

    it('can change properties and verify they persist', function () {
      projectSettingsPage.projectTab.name.clear();
      projectSettingsPage.projectTab.name.sendKeys(newName);

      //projectSettingsPage.projectTab.featured.click();
      projectSettingsPage.projectTab.allowAudioDownload.click();
      projectSettingsPage.projectTab.saveButton.click();
      browser.navigate().refresh();
      projectSettingsPage.tabs.project.click();
      expect(projectSettingsPage.projectTab.name.getAttribute('value')).toBe(newName);

      //expect(projectSettingsPage.projectTab.featured.getAttribute('checked')).toBeTruthy();
      expect(projectSettingsPage.projectTab.allowAudioDownload.getAttribute('checked'))
        .toBeFalsy();
      projectSettingsPage.get(newName);
      projectSettingsPage.tabs.project.click();
      projectSettingsPage.projectTab.name.clear();
      projectSettingsPage.projectTab.name.sendKeys(constants.testProjectName);

      //projectSettingsPage.projectTab.featured.click();
      projectSettingsPage.projectTab.saveButton.click();
    });

  });

  describe('user profile lists', function () {
    it('setup: click on tab and select the Location list for editing', function () {
      projectSettingsPage.tabs.optionlists.click();
      util.findRowByText(projectSettingsPage.optionlistsTab.editList, 'Study Group')
        .then(function (row) {
          row.click();
        });
    });

    it('can add two values to a list', function () {
      expect(projectSettingsPage.optionlistsTab.editContentsList.count()).toBe(0);
      projectSettingsPage.optionlistsTab.addInput.sendKeys('foo');
      projectSettingsPage.optionlistsTab.addButton.click();
      expect(projectSettingsPage.optionlistsTab.editContentsList.count()).toBe(1);
      projectSettingsPage.optionlistsTab.addInput.sendKeys('bar');
      projectSettingsPage.optionlistsTab.addButton.click();
      expect(projectSettingsPage.optionlistsTab.editContentsList.count()).toBe(2);
    });

    it('can delete values from the list', function () {
      var firstEditContentsList = projectSettingsPage.optionlistsTab.editContentsList.first();
      expect(projectSettingsPage.optionlistsTab.editContentsList.count()).toBe(2);
      projectSettingsPage.optionlistsTab.deleteButton(firstEditContentsList).click();
      expect(projectSettingsPage.optionlistsTab.editContentsList.count()).toBe(1);
      projectSettingsPage.optionlistsTab.deleteButton(firstEditContentsList).click();
      expect(projectSettingsPage.optionlistsTab.editContentsList.count()).toBe(0);
    });
  });

  describe('communication settings tab', function () {
    it('is not visible for project manager', function () {
      expect(projectSettingsPage.tabs.communication.isPresent()).toBe(false);
    });

    describe('as a system admin', function () {
      it('setup: logout, login as system admin, go to project settings', function () {
        loginPage.logout();
        loginPage.loginAsAdmin();
        projectSettingsPage.get(constants.testProjectName);
      });

      it('the communication settings tab is visible', function () {
        expect(projectSettingsPage.tabs.communication.isPresent()).toBe(true);
        projectSettingsPage.tabs.communication.click();
      });

      it('can persist communication fields', function () {
        expect(projectSettingsPage.communicationTab.sms.accountId.getAttribute('value')).toBe('');
        expect(projectSettingsPage.communicationTab.sms.authToken.getAttribute('value')).toBe('');
        expect(projectSettingsPage.communicationTab.sms.number.getAttribute('value')).toBe('');
        expect(projectSettingsPage.communicationTab.email.address.getAttribute('value')).toBe('');
        expect(projectSettingsPage.communicationTab.email.name.getAttribute('value')).toBe('');

        var sample = { a: '12345', b: '78', c: '90', d: 'email@me.com', e: 'John Smith' };
        projectSettingsPage.communicationTab.sms.accountId.sendKeys(sample.a);
        projectSettingsPage.communicationTab.sms.authToken.sendKeys(sample.b);
        projectSettingsPage.communicationTab.sms.number.sendKeys(sample.c);
        projectSettingsPage.communicationTab.email.address.sendKeys(sample.d);
        projectSettingsPage.communicationTab.email.name.sendKeys(sample.e);
        projectSettingsPage.communicationTab.button.click();

        browser.navigate().refresh();
        projectSettingsPage.tabs.communication.click();

        expect(projectSettingsPage.communicationTab.sms.accountId.getAttribute('value'))
          .toBe(sample.a);
        expect(projectSettingsPage.communicationTab.sms.authToken.getAttribute('value'))
          .toBe(sample.b);
        expect(projectSettingsPage.communicationTab.sms.number.getAttribute('value'))
          .toBe(sample.c);
        expect(projectSettingsPage.communicationTab.email.address.getAttribute('value'))
          .toBe(sample.d);
        expect(projectSettingsPage.communicationTab.email.name.getAttribute('value'))
          .toBe(sample.e);
      });
    });
  });

});
