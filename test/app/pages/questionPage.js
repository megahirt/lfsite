'use strict';

/*
// This object tests the Question page view where the user can do the following:
// Answers {add, edit, archive (instead of delete) }
// Comments {add, edit, archive (instead of delete) }
// Note: "delete" is a reserved word, and the functionality will be moved to "archiving" at a later time
*/
var SfQuestionPage = function() {
	var page = this; // For use inside our methods. Necessary when passing anonymous functions around, which lose access to "this".
	
	this.answers  = {};
	this.comments = {};

	this.answers.list  = element.all(by.repeater('(answerId, answer) in question.answers'));
	this.comments.list = element.all(by.repeater('comment in answer.comments'));

	// Return the handle to the last answer in the list
	this.answers.last = function() {
		return page.answers.list.last();
	};
	
	// Return the handle to the last comment in the list
	this.comments.last = function() {
		return page.comments.list.last();
	};
	
	// Add new answer to the end of the answers list
	this.answers.add = function(answer) {
		this.answerCtrl = browser.findElement(by.id('comments')); // Using ID "Comments" contains Answers and Comments
		this.answerCtrl.$(".jqte_editor").sendKeys(answer);
		
		// TODO: Currently Chrome browser has issues and separates the string.
		// Firefox 28.0 correctly sends the string, but Firefox 29.0.1 does not
		// TODO: Currently sending this extra "TAB" key appears to help sendKeys send the entire answer
		this.answerCtrl.$(".jqte_editor").sendKeys(protractor.Key.TAB);
		this.answerCtrl.findElement(by.id('doneBtn')).click();
	};

	// Edit last answer
	this.answers.edit = function(answer) {
		this.editCtrl     = page.answers.last().$('.answer').findElement(by.linkText('edit'));

		// Clicking 'edit' changes the DOM so these handles are updated here
		this.editCtrl.click();
		var answersField = page.answers.last().$('.answer').$(".jqte_editor");
		var saveCtrl     = page.answers.last().$(".answerBtn");

		answersField.clear();
		answersField.sendKeys(answer);
		answersField.sendKeys(protractor.Key.TAB);
		
		saveCtrl.click();
	};
	
	// TBD: "delete" is a reserved word, and the functionality will be moved to "archive" at a later time
	this.answers.archive = function(answer) {
	};
	

	// Add a comment to the last (most recent) Answer on the page
	this.comments.addToLastAnswer = function(comment) {
		this.addCommentCtrl = page.answers.last().$('table.comments').$('a.addCommentLink');
		this.commentField   = page.answers.last().findElement(by.model('newComment.content'));
		this.submit         = page.answers.last().$('button.btn-small');
		
		// Click "add comment" at the end of the Answers list to un-collapse the comment text area.
		this.addCommentCtrl.click();

		// TODO: Currently Chrome browser has issues and separates the string.
		// Firefox 28.0 correctly sends the string, but Firefox 29.0.1 does not
		// TODO: Currently sending this extra "TAB" key appears to help sendKeys send the entire comment
		//this.commentCtrl.$(".jqte_editor").sendKeys(protractor.Key.TAB);
		//this.commentCtrl.findElement(by.id('doneBtn')).click();
		this.commentField.sendKeys(comment);
		this.commentField.sendKeys(protractor.Key.TAB);
		this.submit.click();
	};

	// Edit the last comment.  Comments are interspersed with the answers
	this.comments.edit = function(comment) {
		this.editCtrl     = page.comments.last().findElement(by.linkText('edit'));

		this.editCtrl.click();

		// Clicking 'edit' changes the DOM so these handles are updated here
		var commentsField = page.comments.last().$('textarea');
		var saveCtrl      = page.comments.last().findElement(by.partialButtonText('Save'));

		commentsField.clear();
		commentsField.sendKeys(comment);
		commentsField.sendKeys(protractor.Key.TAB);

		saveCtrl.click();
		browser.debugger();
	};
	
	// TBD: "delete" is a reserved word, and the functionality will be moved to "archive" at a later time
	this.comments.archive = function(comment) {
	};
};


module.exports = new SfQuestionPage;
