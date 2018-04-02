import {browser, by, element} from 'protractor';
import {ElementFinder} from 'protractor/built/element';

// This object handles the activity page and provides methods to access items in the activity list
export class SfActivityPage {
  activityURL = '/app/activity';
  activitiesList = element.all(by.repeater('item in $ctrl.filteredActivities'));

  // Navigate to the Activity page
  get() {
    browser.get(browser.baseUrl + this.activityURL);
  }

  static clickOnAllActivity() {
    element(by.id('activity-showAllActivityButton')).click();
  }

  static clickOnShowOnlyMyActivity() {
    element(by.id('activity-showOnlyMyActivityButton')).click();
  }

  // Returns the number of items in the activity list
  //noinspection JSUnusedGlobalSymbols
  getLength() {
    return this.activitiesList.count();
  }

  // Returns the text in the activity list for a specified index
  getActivityText(index: number) {
    return this.activitiesList.get(index).getText();
  }

  getAllActivityTexts() {
    return this.activitiesList.map((elem: ElementFinder) => elem.getText());
  }

  // Prints the entire activity list
  //noinspection JSUnusedGlobalSymbols
  printActivitiesNames() {
    (this.activitiesList).each((names: ElementFinder) => {
      names.getText().then(console.log);
    });
  }
}
