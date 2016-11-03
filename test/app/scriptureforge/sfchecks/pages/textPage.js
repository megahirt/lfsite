'use strict';

module.exports = new SfTextPage();

function SfTextPage() {
  // currently this page is called questions.html but will be refactored. IJH 2014-06

  var expectedCondition = protractor.ExpectedConditions;
  var CONDITION_TIMEOUT = 3000;

  this.archiveButton = element(by.partialButtonText('Archive Questions'));
  this.makeTemplateBtn = element(by.partialButtonText('Make Template'));
  this.addNewBtn = element(by.partialButtonText('Add New Question'));
  this.textSettingsBtn = element(by.id('text_settings_button'));

  this.questionLink = function (title) {
    return element(by.linkText(title));
  };

  this.clickOnQuestion = function (questionTitle) {
    element(by.linkText(questionTitle)).click();
  };

  this.questionNames = element.all(by.repeater('question in visibleQuestions')
    .column('{{question.calculatedTitle}}'));
  this.questionRows  = element.all(by.repeater('question in visibleQuestions'));

  //noinspection JSUnusedGlobalSymbols
  this.questionText = element(by.model('questionDescription'));

  //noinspection JSUnusedGlobalSymbols
  this.questionSummary = element(by.model('questionTitle'));

  //noinspection JSUnusedGlobalSymbols
  this.saveQuestion = element(by.partialButtonText('Save'));

  // getFirstCheckbox has to be a function because the .first() method will actually resolve the
  // finder
  this.getFirstCheckbox = function () {
    return this.questionRows.first().element(by.css('input[type="checkbox"]'));
  };

  this.newQuestion = {
    showFormButton: element(by.partialButtonText('Add New Question')),
    form: element(by.name('newQuestionForm')),
    description: element(by.model('questionDescription')),
    summary: element(by.model('questionTitle')),
    saveButton: element(by.css('form[name="newQuestionForm"]'))
      .element(by.partialButtonText('Save'))
  };

  this.addNewQuestion = function (description, summary) {
    expect(this.newQuestion.showFormButton.isDisplayed()).toBe(true);
    this.newQuestion.showFormButton.click();
    browser.wait(expectedCondition.visibilityOf(this.newQuestion.description), CONDITION_TIMEOUT);
    this.newQuestion.description.sendKeys(description);
    this.newQuestion.summary.sendKeys(summary);
    this.newQuestion.saveButton.click();
  };

  //noinspection JSUnusedGlobalSymbols
  this.printQuestionNames = function () {
    this.questionNames.each(function (names) {
      names.getText().then(console.log);
    });
  };

  this.textContent = element(by.id('text'));
}
