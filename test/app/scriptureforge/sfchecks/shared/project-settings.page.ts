import {browser, by, element, ExpectedConditions} from 'protractor';

import {ProjectsPage} from '../../../bellows/shared/projects.page';

export class SfProjectSettingsPage {
  private readonly projectsPage = new ProjectsPage();

  conditionTimeout = 3000;
  settingsMenuLink = element(by.id('settings-dropdown-button'));
  projectSettingsLink = element(by.id('dropdown-project-settings'));

  // Get the projectSettings for project projectName
  get(projectName: string = '') {
    this.projectsPage.get();
    this.projectsPage.clickOnProject(projectName);
    browser.wait(ExpectedConditions.visibilityOf(this.settingsMenuLink), this.conditionTimeout);
    this.settingsMenuLink.click();
    this.projectSettingsLink.click();
  }

  clickOnSettingsLink() {
    this.settingsMenuLink.click();
    this.projectSettingsLink.click();
  }

  tabs = {
    members: element(by.linkText('Members')),
    templates: element(by.linkText('Question Templates')),
    archiveTexts: element(by.linkText('Archived Texts')),
    project: element(by.linkText('Project Properties')),
    optionlists: element(by.linkText('User Profile Lists')),
    communication: element(by.linkText('Communication Settings'))
  };

  membersTab = new MembersTab(this);

  templatesTab = {
    list: element.all(by.repeater('template in visibleTemplates')),
    addButton: element(by.id('project-settings-new-template-btn')),
    removeButton: element(by.id('project-settings-remove-btn')),
    editor: {
      title: element(by.model('editedTemplate.title')),
      description: element(by.model('editedTemplate.description')),
      saveButton: element(by.id('project-settings-question-save-btn'))
    }
  };

  archivedTextsTab = {
    textNames: element.all(by.repeater('text in visibleTexts').column('title')),
    textList: element.all(by.repeater('text in visibleTexts')),
    publishButton: element(by.id('project-settings-republish-btn')),
    textLink(title: string) {
      return element(by.linkText(title));
    }
  };

  // getFirstCheckbox has to be a function because the .first() method will actually resolve the
  // finder
  archivedTextsTabGetFirstCheckbox() {
    return this.archivedTextsTab.textList.first().element(by.css('input[type="checkbox"]'));
  }

  projectTab = {
    name: element(by.model('project.projectName')),
    code: element(by.model('project.projectCode')),
    featured: element(by.model('project.featured')),
    allowAudioDownload: element(by.model('project.allowAudioDownload')),
    usersSeeEachOthersResponses: element(by.model('project.usersSeeEachOthersResponses')),
    saveButton: element(by.id('project-properties-save-button')),
    // Set a checkbox to either true or false no matter what its current value is
    // TODO: Move this function to a general utilities library somewhere
    setCheckbox(checkboxElement: any, value: any) {
      checkboxElement.isSelected().then((selected: any) => {
        if (value !== selected) {
          checkboxElement.click();
        }
      });
    }
  };

  optionlistsTab = {
    // TODO: Find better names for these
    showList: element(by.id('showInProfileFieldset'))
      .all(by.repeater('(listId, list) in project.userProperties.userProfilePickLists')),
    editList: element(by.id('editListValuesFieldset'))
      .all(by.repeater('(listId, list) in project.userProperties.userProfilePickLists')),
    editContentsList: element(by.id('picklistEditorFieldset')).all(by.repeater('item in items')),
    defaultValue: element(by.id('picklistEditorFieldset')).element(by.model('defaultKey')),
    addInput: element(by.id('picklistEditorFieldset')).element(by.model('newValue')),
    addButton: element(by.id('picklistEditorFieldset')).element(by.css('.add-item-to-list')),
    saveButton: element(by.id('user_profile_lists_save_button')),
    unsavedWarning: element(by.id('project-settings-unsaved')),
    deleteButton(repeaterRow: any) {
      // Given a single repeater row in the picklist, return the delete button for that row
      return repeaterRow.element(by.css('a:first-of-type'));
    }
  }; // NYI - wait for refactor

  communicationTab = {
    sms: {
      accountId: element(by.model('settings.sms.accountId')),
      authToken: element(by.model('settings.sms.authToken')),
      number: element(by.model('settings.sms.fromNumber'))
    },
    email: {
      address: element(by.model('settings.email.fromAddress')),
      name: element(by.model('settings.email.fromName'))
    },
    button: element(by.id('communication_settings_save_button'))
  };
}

class MembersTab {
  sfProjectSettingsPage: any;
  constructor(sfProjectSettingsPage: any) {
    this.sfProjectSettingsPage = sfProjectSettingsPage;
  }

  addButton = element(by.id('addMembersButton'));
  removeButton = element(by.id('remove-members-button'));
  messageButton = element(by.id('messageUsersButton'));
  listFilter = element(by.model('userFilter'));
  list = element.all(by.repeater('user in list.visibleUsers'));
  newMember = {
    input: element(by.model('term')),
    button: element(by.model('addMode')),
    warning: element(by.binding('warningText')),
    results: element(by.id('typeaheadDiv')).element(by.css('ul li'))
  };

  addNewMember(name: string) {
    this.sfProjectSettingsPage.tabs.members.click();
    this.addButton.click();
    browser.wait(ExpectedConditions.visibilityOf(this.newMember.input),
      this.sfProjectSettingsPage.conditionTimeout);
    this.newMember.input.sendKeys(name);
    browser.wait(ExpectedConditions.textToBePresentInElementValue(this.newMember.input,
      name), this.sfProjectSettingsPage.conditionTimeout);
    this.newMember.button.click();
  }

  waitForNewUserToLoad(memberCount: number) {
    browser.wait(() => {
      return this.list.count().then((count: number) => {
        return count >= memberCount + 1;
      });
    });
  }
}
