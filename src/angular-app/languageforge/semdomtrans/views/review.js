'use strict';

angular.module('semdomtrans.review', ['ui.bootstrap', 'coreModule',  'palaso.ui.notice',
  'semdomtrans.services', 'palaso.ui.utils', 'palaso.ui.typeahead'])

.controller('reviewCtrl', ['$scope', '$state', '$stateParams', 'semdomtransEditorDataService',
  'semdomtransEditService',
function ($scope, $state, $stateParams, editorService,
          semdomEditApi) {
    $scope.control = $scope;

    $scope.refreshDbeData = function refreshDbeData() {
      return editorService.refreshEditorData().then(function (result) {
          editorService.processEditorDto(result).then(function () {
            calculateDisplayedItems();
          });
        });
    };

    if ($scope.displayedItems === undefined) {
      $scope.refreshDbeData();
    }

    function calculateDisplayedItems() {
      $scope.displayedItems = [];

      var isCurrentEntryStillInList = false;
      for (var i in $scope.items) {
        if (doesItemNeedReview($scope.items[i])) {
          $scope.displayedItems.push($scope.items[i]);
          if ($scope.currentEntry !== undefined && $scope.currentEntry.id === $scope.items[i].id) {
            isCurrentEntryStillInList = true;
          }
        }
      }

      if (!isCurrentEntryStillInList) {
        $scope.currentEntry = undefined;
      }

    }

    function doesItemNeedReview(item) {
      var nr = false;
      nr = nr || doesFieldNeedReview(item.name);
      nr = nr || doesFieldNeedReview(item.description);
      for (var i = 0; i < item.searchKeys.length; i++) {
        nr =  nr || doesFieldNeedReview(item.searchKeys[i]);
      }

      for (var i = 0; i < item.questions.length; i++) {
        nr =  nr || doesFieldNeedReview(item.questions[i].question);
        nr =  nr || doesFieldNeedReview(item.questions[i].terms);
      }

      return nr;
    }

    $scope.changeTerm = function changeTerm(key) {
      for (var i = 0; i < $scope.items.length; i++) {
        if ($scope.items[i].key === key) {
          $scope.currentEntry = $scope.items[i];
          $scope.currentEntryIndex = i;
          break;
        }
      }
    };

    $scope.$watch('currentEntry', function (newVal, oldVal) {
      if (newVal !== oldVal) {
        calculateFieldsForReview();
      }
    });

    function calculateFieldsForReview() {
      var fieldsForReview = {};
      if (!angular.isUndefined($scope.currentEntry)) {
        if (doesFieldNeedReview($scope.currentEntry.name)) {
          fieldsForReview.Name = $scope.currentEntry.name;
        }

        if (doesFieldNeedReview($scope.currentEntry.description)) {
          fieldsForReview.Description = $scope.currentEntry.description;
        }

        for (var i = 0; i < $scope.currentEntry.searchKeys.length; i++) {
          if (doesFieldNeedReview($scope.currentEntry.searchKeys[i])) {
            fieldsForReview['Search Key ' + i] = $scope.currentEntry.searchKeys[i];
          }
        }

        for (var i = 0; i < $scope.currentEntry.questions.length; i++) {
          if (doesFieldNeedReview($scope.currentEntry.questions[i].question)) {
            fieldsForReview['Question ' + i] = $scope.currentEntry.questions[i].question;
          }

          if (doesFieldNeedReview($scope.currentEntry.questions[i].terms)) {
            fieldsForReview['Question Terms ' + i] = $scope.currentEntry.questions[i].terms;
          }
        }
      }

      $scope.fieldsForReview = fieldsForReview;
    }

    function doesFieldNeedReview(field) {
      return field.translation !== '' && (field.status === 1 || field.status === 2);
    }

    $scope.markAsApproved = function markAsApproved(field) {
      field.status = 4;
      updateParentItem();
    };

    $scope.markAsNeedsRevision = function markAsNeedsRevision(field) {
      field.status = 3;
      updateParentItem();
    };

    $scope.getAllFieldsForRevison = function getAllFieldsForRevision() {

    };

    function updateParentItem() {
      calculateFieldsForReview();
      semdomEditApi.updateTerm($scope.currentEntry, function (result) {
        if (result.ok) {
          $scope.refreshDbeData();
        }
      });
    }

  }]);
