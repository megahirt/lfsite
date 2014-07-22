angular.module('palaso.ui.dc.entry', ['palaso.ui.dc.sense', 'palaso.ui.dc.multitext', 'ngAnimate', 'lexicon.services', 'bellows.services'])
  // Palaso UI Dictionary Control: Entry
  .directive('dcEntry', ['lexUtils', 'modalService', function(utils, modal) {
		return {
			restrict : 'E',
			templateUrl : '/angular-app/languageforge/lexicon/directive/dc-entry.html',
			scope : {
				config : "=",
				model : "=",
				control : "="
			},
			controller: ["$scope", function($scope) {
				$scope.addSense = function() {
                    var newSense = {};
                    $scope.control.makeValidModelRecursive($scope.config.fields.senses, newSense, 'examples');
					$scope.model.senses.unshift(newSense);
				};
				
				
				$scope.deleteSense = function(index) {
                    var deletemsg = "Are you sure you want to delete the sense <b>' " + utils.getMeaning($scope.config.fields.senses, $scope.model.senses[index])  + " '</b>";
                    modal.showModalSimple('Delete Sense', deletemsg, 'Cancel', 'Delete Sense').then(function() {
                        $scope.model.senses.splice(index, 1);
					});
				};


			}],
			link : function(scope, element, attrs, controller) {
			}
		};
  }])
  ;
