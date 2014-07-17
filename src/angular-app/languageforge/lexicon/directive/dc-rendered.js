'use strict';

angular.module('palaso.ui.dc.rendered', ['lexicon.services'])
  // Palaso UI Rendered Definition
  .directive('dcRendered', [function() {
		return {
			restrict: 'E',
			templateUrl: '/angular-app/languageforge/lexicon/directive/dc-rendered.html',
			scope: {
				config: "=",
				model: "=",
				hideIfEmpty: "=?"
			},
			controller: ['$scope', 'lexUtils', function($scope, utils) {
				$scope.render = function() {
                    $scope.entry = {
                        word: '',
                        senses: []
                    };
                    $scope.entry.word = utils.getWord($scope.config, $scope.model);
                    angular.forEach($scope.model.senses, function (senseModel) {
                        var sense = {
                            meaning: utils.getMeaning($scope.config.fields.senses, senseModel),
                            partOfSpeech: utils.getPartOfSpeechAbbreviation(senseModel.partOfSpeech),
                            examples: []
                        };
                        angular.forEach(senseModel.examples, function (exampleModel) {
                            sense.examples.push(
                                {
                                    sentence: utils.getExampleSentence($scope.config.fields.senses.fields.examples, exampleModel)
                                }
                            );
                        });
                        $scope.entry.senses.push(sense);
                    });
                };

				$scope.makeValidModel = function() {
					// if the model doesn't exist, create an object for it based upon the definition
					if (!$scope.model) {
						$scope.model = {senses:[]};
					}
				};
			}],
			link: function(scope, element, attrs, controller) {
                if (angular.isUndefined(scope.hideIfEmpty)) {
                    scope.hideIfEmpty = false;
                }
				scope.$watch('model', function(model) {
					scope.makeValidModel();
					scope.render();
				});
			}
		};
  }])
  ;
