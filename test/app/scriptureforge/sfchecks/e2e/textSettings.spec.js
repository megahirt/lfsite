"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
const protractor_1 = require("protractor");
const loginPage_js_1 = require("../../../bellows/pages/loginPage.js");
const projectsPage_js_1 = require("../../../bellows/pages/projectsPage.js");
const utils_js_1 = require("../../../bellows/pages/utils.js");
const projectPage_js_1 = require("../pages/projectPage.js");
const textPage_js_1 = require("../pages/textPage.js");
const textSettingsPage_js_1 = require("../pages/textSettingsPage.js");
describe('the questions settings page - project manager', () => {
    const constants = require('../../../testConstants.json');
    const loginPage = new loginPage_js_1.BellowsLoginPage();
    const util = new utils_js_1.Utils();
    const projectListPage = new projectsPage_js_1.ProjectsPage();
    const projectPage = new projectPage_js_1.SfProjectPage();
    const textPage = new textPage_js_1.SfTextPage();
    const page = new textSettingsPage_js_1.SfTextSettingsPage();
    const CONDITION_TIMEOUT = 3000;
    it('setup: logout, login as project manager, go to text settings', () => {
        loginPage.logout();
        loginPage.loginAsManager();
        projectListPage.get();
        projectListPage.clickOnProject(constants.testProjectName);
        projectPage.textLink(constants.testText1Title).click();
        textPage.clickTextSettingsButton();
    });
    describe('edit text tab', () => {
        it('setup: click on tab', () => {
            expect(page.tabs.editText.isPresent()).toBe(true);
            page.tabs.editText.click();
        });
        it('can edit text content', () => {
            // TODO: Use actual USX from projectPage.testData (maybe move it to testConstants) for this
            // test, then verify it shows up properly on the question page
            page.editTextTab.contentEditor.sendKeys('Hello, world!');
            page.editTextTab.letMeEditLink.click();
            // Should pop up two alerts in a row
            // First alert: "This is dangerous, are you sure?"
            util.checkModalTextMatches('Caution: Editing the USX text can be dangerous');
            util.clickModalButton('Edit');
            // Second alert: "You have previous edits which will be replaced, are you really sure?"
            util.checkModalTextMatches('Caution: You had previous edits in the USX text box');
            util.clickModalButton('Replace');
            // TODO: Check alert text for one or both alerts (http://stackoverflow.com/a/19884387/2314532)
            expect(page.editTextTab.contentEditor.getAttribute('value')).toBe(constants.testText1Content);
        });
    });
    // The Archived Questions tab is tested as part of a process in the Text (Questions) page tests.
    // IJH 2014-06
    describe('audio file tab - NYI', () => {
    });
    describe('paratext export tab', () => {
        it('setup: click on tab', () => {
            expect(page.tabs.paratextExport.isPresent()).toBe(true);
            page.tabs.paratextExport.click();
        });
        it('get a message since there are not messages flagged for export', () => {
            expect(page.paratextExportTab.exportAnswers.getAttribute('checked')).toBeTruthy();
            expect(page.paratextExportTab.exportComments.getAttribute('checked')).toBeFalsy();
            expect(page.paratextExportTab.exportFlagged.getAttribute('checked')).toBeTruthy();
            expect(page.paratextExportTab.downloadButton.isDisplayed()).toBe(false);
            expect(page.paratextExportTab.prepareButton.isPresent()).toBe(true);
            page.paratextExportTab.prepareButton.click();
            protractor_1.browser.wait(protractor_1.ExpectedConditions.visibilityOf(page.paratextExportTab.noExportMsg), CONDITION_TIMEOUT);
            expect(page.paratextExportTab.noExportMsg.isDisplayed()).toBe(true);
        });
        it('can prepare export for all answers without comments', () => {
            page.paratextExportTab.exportFlagged.click();
            page.paratextExportTab.prepareButton.click();
            protractor_1.browser.wait(protractor_1.ExpectedConditions.visibilityOf(page.paratextExportTab.answerCount), CONDITION_TIMEOUT);
            expect(page.paratextExportTab.answerCount.isDisplayed()).toBe(true);
            expect(page.paratextExportTab.answerCount.getText()).toEqual('2');
            expect(page.paratextExportTab.commentCount.isDisplayed()).toBe(false);
            expect(page.paratextExportTab.downloadButton.isDisplayed()).toBe(true);
        });
        it('can prepare export for all answers with comments', () => {
            page.paratextExportTab.exportComments.click();
            page.paratextExportTab.prepareButton.click();
            protractor_1.browser.wait(protractor_1.ExpectedConditions.visibilityOf(page.paratextExportTab.answerCount), CONDITION_TIMEOUT);
            expect(page.paratextExportTab.answerCount.isDisplayed()).toBe(true);
            expect(page.paratextExportTab.answerCount.getText()).toEqual('2');
            expect(page.paratextExportTab.commentCount.isDisplayed()).toBe(true);
            expect(page.paratextExportTab.commentCount.getText()).toEqual('2');
            expect(page.paratextExportTab.downloadButton.isDisplayed()).toBe(true);
        });
    });
});
//# sourceMappingURL=textSettings.spec.js.map