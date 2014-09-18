'use strict';

angular.module('lexicon.configuration', ['ui.bootstrap', 'bellows.services', 'palaso.ui.notice',
	'palaso.ui.language', 'ngAnimate', 'palaso.ui.picklistEditor', 'lexicon.services', 'palaso.util.model.transform'])
	.controller('ConfigCtrl', ['$scope', 'silNoticeService', 'lexProjectService', 'sessionService', '$filter', '$modal', 'lexConfigService', '$location', 'utilService',
		function ($scope, notice, lexProjectService, ss, $filter, $modal, lexConfigService, $location, util) {
			lexProjectService.setBreadcrumbs('configuration', $filter('translate')('Dictionary Configuration'));
			$scope.configDirty = angular.copy(ss.session.projectSettings.config);
			$scope.optionlistDirty = angular.copy(ss.session.projectSettings.optionlists);
			$scope.isSaving = false;

			// InputSystemsViewModel based on BCP 47
			// References: http://en.wikipedia.org/wiki/IETF_language_tag
			//             http://tools.ietf.org/html/rfc5646#page-15
			var InputSystemsViewModel = function(inputSystem) {

				if (inputSystem == undefined) {
					inputSystem = {};
				}

				this.uuid = util.uuid();

				// Manage the Special dropdown on the View
				this.special = '';

				// Manage the Purpose dropdown on the View
				this.purpose = '';

				// Manage the Variant textbox on the View.
				this.variantString = '';

				// 2-3 letter (RFC 5646 2.2.1 Primary Language Subtag)
				this.language = '';

				// 3-letter (RFC 5646 2.2.2 Extended Language Subtag) currently not implemented

				// 4-letter (RFC 5646 2.2.3 Script Subtag)
				this.script = '';

				// 2-letter or 3-number (RFC 5646 2.2.4 Region Subtag)
				this.region = '';

				// RFC 5646 2.2.7 Private Use Subtag)

				this.inputSystem = inputSystem;

				// Create a language tag based on the view
				InputSystemsViewModel.prototype.buildTag = function () {
					var newTag = this.language;
					var specialOptions = $scope.selects.special.optionsOrder;
					var scriptOptionsOrder = $scope.selects.script.optionsOrder;
					switch (this.special) {
						// IPA transcription
						case specialOptions[1]:
							newTag += '-fonipa';
							if (this.purpose || this.variantString.length > 0) {
								newTag += '-x';
							}
							newTag += (this.purpose) ? '-' + this.purpose : '';
							newTag += (this.variantString) ? '-' + this.variantString : '';
							break;

						// Voice
						case specialOptions[2]:
							newTag += '-Zxxx';
							newTag += '-x';
							newTag += '-audio';
							newTag += (this.variantString) ? '-' + this.variantString : '';
							break;

						// Script / Region / Variant
						case specialOptions[3]:
							newTag += (this.script) ? '-' + this.script : '';
							newTag += (this.region) ? '-' + this.region : '';
							newTag += (this.variantString) ? '-x-' + this.variantString : '';
							break;
					}

					this.inputSystem.tag = newTag;
					//console.log('newTag: ' + newTag);
					return newTag;
				};

				// Parse the language tag to populate InputSystemsViewModel
				InputSystemsViewModel.prototype.parseTag = function (tag) {
					var tokens = tag.split('-');
					var lookForPrivateUsage = false;

					// Assumption we will never have an entire tag that is private
					// usage or grandfathered (entire tag starts with x- or i-)

					// Language code
					this.language = tokens[0];

					var specialOptionsOrder = $scope.selects.special.optionsOrder;
					this.special = specialOptionsOrder[0];

					var purposeOptionsOrder = $scope.selects.purpose.optionsOrder;
					this.purpose = '';
					this.variantString = '';

					// Parse the rest of the language tag
					for (var i = 1, l = tokens.length; i < l; i++) {

						if (!lookForPrivateUsage) {
							// Script
							if ((/^[a-zA-Z]{4}$/.test(tokens[i])) &&
								(tokens[i] in _inputSystems_scripts)) { // scripts would be better obtained from a service CP 2014-08
								this.script = tokens[i];
								this.special = specialOptionsOrder[3];
								continue;
							}

							// Region
							if ((/^[a-zA-Z]{2}$/.test(tokens[i]) ||
								/^[0-9]{3}$/.test(tokens[i])) &&
								(tokens[i] in _inputSystems_regions)) {  // scripts would be better obtained from a service CP 2014-08
								this.region = tokens[i];
								this.special = specialOptionsOrder[3];
								continue;
							}

							// Variant
							if (/^[a-zA-Z]{5,}$/.test(tokens[i]) ||
								/^[0-9][0-9a-zA-Z]{3,}$/.test(tokens[i])) {
								this.variant = tokens[i];
								if (tokens[i] == 'fonipa') {
									this.special = specialOptionsOrder[1];
								}
								continue;
							}

							// Special marker for private usage
							if (tokens[i] == 'x') {
								lookForPrivateUsage = true;
								continue;
							}

							// Parse for the rest of the private usage tags
						} else {
							// SIL registered private use tags are audio, etic, and emic
							switch (tokens[i]) {
								case 'audio' :
									this.special = specialOptionsOrder[2];
									continue;
									break;
								case 'etic' :
									this.special = specialOptionsOrder[1];
									this.purpose = purposeOptionsOrder[0];
									continue;
									break;
								case 'emic' :
									this.special = specialOptionsOrder[1];
									this.purpose = purposeOptionsOrder[1];
									continue;
									break;
								default :
									// General Private Usage used to populate variantString
									if (tokens[i]) {

										// If Special hasn't been set, presence of a token here means
										// Special must have been set to Script/Region/Variant
										if (this.special == specialOptionsOrder[0]) {
											this.special = specialOptionsOrder[3];
										}

										if (this.variantString.length > 0) {
											this.variantString += '-';
										}
									}
									this.variantString += tokens[i];
									continue;
									break;
							};
						}
					}
				};

				// Compute the language name for display
				InputSystemsViewModel.prototype.languageDisplayName = function () {
					var name = this.inputSystem.languageName;
					var specialOptions = $scope.selects.special.optionsOrder;

					if (this.special == specialOptions[1]) {
						name += ' (IPA';
						name += (this.variantString) ? '-' + this.variantString : '';
						name += ')';
					} else if (this.special == specialOptions[2]) {
						name += ' (Voice';
						name += (this.variantString) ? '-' + this.variantString : '';
						name += ')';
					} else if (this.special == specialOptions[3]) {
						name += ' (' + this.variantString + ')';
					}
					return name;
				};

				if (inputSystem.tag != undefined) {
					this.parseTag(inputSystem.tag);
				}
			};

			$scope.inputSystemViewModels = {};
			$scope.selects = {
				'special': {
					'optionsOrder': ['none', 'ipaTranscription', 'voice', 'scriptRegionVariant'],
					'options': {
						'none': $filter('translate')('none'),
						'ipaTranscription': $filter('translate')('IPA transcription'),
						'voice': $filter('translate')('Voice'),
						'scriptRegionVariant': $filter('translate')('Script / Region / Variant')
					}
				},
				'purpose': {
					'optionsOrder': ['etic', 'emic'],
					'options': {
						'etic': $filter('translate')('Etic (raw phonetic transcription)'),
						'emic': $filter('translate')('Emic (uses the phonology of the language)')
					}
				},
				'script': {
					'options': InputSystems.scripts()
				},
				'region': {
					'options': InputSystems.regions()
				}
			};

			$scope.isCustomField = lexConfigService.isCustomField;
			$scope.currentInputSystemTag = '';
			$scope.selectInputSystem = function selectInputSystem(id) {
				$scope.currentInputSystemTag = id;
			};

			setupView();

			// TODO Fix sorting 2014-08 DDW
			function sortInputSystemsList() {
				return $filter('orderBy')($filter('orderAsArray')($scope.inputSystemViewModels, 'tag'), 'languageName');
				//return $filter('orderBy')($filter('orderAsArray')($scope.configDirty.inputSystems, 'tag'), 'languageName');
			}

			function setupView() {
				if (!angular.isDefined($scope.configDirty.inputSystems)) {
					return;
				}

				// InputSystemsViewModels
				$scope.inputSystemViewModels = {};
				angular.forEach($scope.configDirty.inputSystems, function (item) {
					var vm = new InputSystemsViewModel(item);
					$scope.inputSystemViewModels[vm.uuid] = vm;
				});

				$scope.inputSystemsList = sortInputSystemsList();

				// select the first items
				$scope.selectInputSystem($scope.inputSystemsList[0].uuid);
				$scope.currentTaskName = 'dashboard';

				// for FieldConfigCtrl
				$scope.fieldConfig = {};
				angular.forEach($scope.configDirty.entry.fieldOrder, function (fieldName) {
					if (angular.isDefined($scope.configDirty.entry.fields[fieldName])) {
						if ($scope.configDirty.entry.fields[fieldName].type !== 'fields') {
							$scope.fieldConfig[fieldName] = $scope.configDirty.entry.fields[fieldName];
						}
					}
					/*
					 } else {
					 if ($scope.configDirty.entry.fields.customFields.fields[fieldName].type !== 'fields') {
					 $scope.fieldConfig[fieldName] = $scope.configDirty.entry.fields.customFields.fields[fieldName];
					 }
					 */
				});
				angular.forEach($scope.configDirty.entry.fields.senses.fieldOrder, function (fieldName) {
					if (angular.isDefined($scope.configDirty.entry.fields.senses.fields[fieldName])) {
						if ($scope.configDirty.entry.fields.senses.fields[fieldName].type !== 'fields') {
							$scope.fieldConfig[fieldName] = $scope.configDirty.entry.fields.senses.fields[fieldName];
						}

					}
					//} else {
					//if ($scope.configDirty.entry.fields.senses.fields.customFields.fields[fieldName].type !== 'fields') {
					//  $scope.fieldConfig[fieldName] = $scope.configDirty.entry.fields.senses.fields.customFields.fields[fieldName];
					//}
				});
				angular.forEach($scope.configDirty.entry.fields.senses.fields.examples.fieldOrder, function (fieldName) {
					if (angular.isDefined($scope.configDirty.entry.fields.senses.fields.examples.fields[fieldName])) {
						if ($scope.configDirty.entry.fields.senses.fields.examples.fields[fieldName].type !== 'fields') {
							$scope.fieldConfig[fieldName] = $scope.configDirty.entry.fields.senses.fields.examples.fields[fieldName];
						}
					}
					//} else {
					//    if ($scope.configDirty.entry.fields.senses.fields.examples.fields.customFields.fields[fieldName].type !== 'fields') {
					//        $scope.fieldConfig[fieldName] = $scope.configDirty.entry.fields.senses.fields.examples.fields.customFields.fields[fieldName];
					//    }
				});

				// suggested languages from lexical data
				$scope.suggestedLanguageCodes = [];
			};

			$scope.configurationApply = function () {
				$scope.isSaving = true;

				// Publish updates in configDirty to send to server
				$scope.configDirty.inputSystems = {};
				angular.forEach($scope.inputSystemViewModels, function (viewModel) {
					$scope.configDirty.inputSystems[viewModel.inputSystem.tag] = viewModel.inputSystem;
				});

				lexProjectService.updateConfiguration($scope.configDirty, $scope.optionlistDirty, function (result) {
					if (result.ok) {
						notice.push(notice.SUCCESS, $filter('translate')('Dictionary configuration updated successfully'));
						$scope.configForm.$setPristine();
						$scope.projectSettings.config = angular.copy($scope.configDirty);
						$scope.projectSettings.optionlist = angular.copy($scope.optionlistDirty);
						//setupView();
					}
					$scope.isSaving = false;
				});

			};

			$scope.backToDictionary = function backToDictionary() {
				$location.path('/dbe');
			};

			$scope.showInputSystems = function () {
				return !($scope.inputSystemViewModels[$scope.currentInputSystemTag].inputSystem.tag in $scope.projectSettings.config.inputSystems);
				//return !($scope.currentInputSystemTag in $scope.projectSettings.config.inputSystems);
			};

			// InputSystemsConfigCtrl
			$scope.newExists = function (special) {
				var viewModel = new InputSystemsViewModel();
				viewModel.language = $scope.inputSystemViewModels[$scope.currentInputSystemTag].language;
				viewModel.special = special;
				var tag = viewModel.buildTag();
				for (var uuid in $scope.inputSystemViewModels) {
					if ($scope.inputSystemViewModels[uuid].inputSystem.tag == tag) {
						return true;
					}
				}
				return false;
			};

			$scope.addInputSystem = function addInputSystem(code, languageName, special) {
				var viewModel = new InputSystemsViewModel({
					'tag': code,
					'languageName': languageName,
					'abbreviation': code
				});
				viewModel.special = special;
				viewModel.buildTag();

				// Verify newly created tag doesn't already exist before adding it to the list
				for (var uuid in $scope.inputSystemViewModels) {
					if ($scope.inputSystemViewModels[uuid].inputSystem.tag == viewModel.inputSystem.tag) {
						notice.push(notice.ERROR, $filter('translate')('Input system for ' + viewModel.inputSystem.languageName + ' already exists'));
						return;
					}
				}
				$scope.inputSystemViewModels[viewModel.uuid] = viewModel;
				$scope.currentInputSystemTag = viewModel.uuid;
			};

			$scope.removeInputSystem = function removeInputSystem(currentInputSystemTag) {
				delete $scope.inputSystemViewModels[currentInputSystemTag];
				$scope.inputSystemsList = sortInputSystemsList();
				$scope.configForm.$setDirty();

				// select the first items
				$scope.selectInputSystem($scope.inputSystemsList[0].uuid);
			};

			$scope.isUnlistedLanguage = function isUnlistedLanguage(code) {
				return (code == 'qaa');
			};

			$scope.openNewLanguageModal = function openNewLanguageModal(suggestedLanguageCodes) {
				var modalInstance = $modal.open({
					templateUrl: '/angular-app/languageforge/lexicon/views/select-new-language.html',
					controller: ['$scope', '$modalInstance', function ($scope, $modalInstance) {
						$scope.selected = {
							code: '',
							language: {}
						};
						$scope.add = function () {
							$modalInstance.close($scope.selected);
						};
						$scope.suggestedLanguageCodes = suggestedLanguageCodes;
					}]
				});

				modalInstance.result.then(function (selected) {
					$scope.addInputSystem(selected.code, selected.language.name, $scope.selects.special.optionsOrder[0]);
				});

			};

			$scope.$watchCollection('inputSystemViewModels[currentInputSystemTag]', function(newValue, oldValue) {
				if (newValue == undefined) {
					return;
				}
				newValue.buildTag();
				$scope.configForm.$setDirty();
				$scope.inputSystemsList = sortInputSystemsList();
			});

		}])
	.controller('FieldConfigCtrl', ['$scope', '$modal', function ($scope, $modal) {
		$scope.showAllFields = false;

		$scope.currentField = {
			'name': '',
			'inputSystems': {
				'fieldOrder': [],
				'selecteds': {}
			}
		};
		$scope.selectField = function selectField(fieldName) {
			if ($scope.currentField.name !== fieldName) {
				var inputSystems = $scope.fieldConfig[fieldName].inputSystems;

				$scope.currentField.name = fieldName;

				$scope.currentField.inputSystems.selecteds = {};
				angular.forEach(inputSystems, function (tag) {
					$scope.currentField.inputSystems.selecteds[tag] = true;
				});

				// if the field uses input systems, add the selected systems first then the unselected systems
				if (inputSystems) {
					$scope.currentField.inputSystems.fieldOrder = inputSystems;
					angular.forEach($scope.configDirty.inputSystems, function (inputSystem, tag) {
						if (!(tag in $scope.currentField.inputSystems.selecteds) &&
							$scope.currentField.inputSystems.fieldOrder.indexOf(tag) == -1) {
							$scope.currentField.inputSystems.fieldOrder.push(tag);
						}
					});
				}
			}
		};
		$scope.selectField('lexeme');

		$scope.moveUp = function moveUp(currentTag) {
			var currentTagIndex = $scope.currentField.inputSystems.fieldOrder.indexOf(currentTag);
			$scope.currentField.inputSystems.fieldOrder[currentTagIndex] = $scope.currentField.inputSystems.fieldOrder[currentTagIndex - 1];
			$scope.currentField.inputSystems.fieldOrder[currentTagIndex - 1] = currentTag;
			$scope.fieldConfig[$scope.currentField.name].inputSystems = [];
			angular.forEach($scope.currentField.inputSystems.fieldOrder, function (tag) {
				if ($scope.currentField.inputSystems.selecteds[tag]) {
					$scope.fieldConfig[$scope.currentField.name].inputSystems.push(tag);
				}
			});
			$scope.configForm.$setDirty();
		};
		$scope.moveDown = function moveDown(currentTag) {
			var currentTagIndex = $scope.currentField.inputSystems.fieldOrder.indexOf(currentTag);
			$scope.currentField.inputSystems.fieldOrder[currentTagIndex] = $scope.currentField.inputSystems.fieldOrder[currentTagIndex + 1];
			$scope.currentField.inputSystems.fieldOrder[currentTagIndex + 1] = currentTag;
			$scope.fieldConfig[$scope.currentField.name].inputSystems = [];
			angular.forEach($scope.currentField.inputSystems.fieldOrder, function (tag) {
				if ($scope.currentField.inputSystems.selecteds[tag]) {
					$scope.fieldConfig[$scope.currentField.name].inputSystems.push(tag);
				}
			});
			$scope.configForm.$setDirty();
		};

		$scope.fieldIsHidden = function fieldIsHidden(fieldName) {
			if (angular.isUndefined($scope.fieldConfig[fieldName]) || !('hideIfEmpty' in $scope.fieldConfig[fieldName])) {
				return true;
			}
			return !$scope.showAllFields && $scope.fieldConfig[fieldName].hideIfEmpty;
		};

		$scope.fieldConfigItemExists = function fieldConfigItemExists(itemName) {
			return itemName in $scope.fieldConfig[$scope.currentField.name];
		};

        $scope.fieldConfigSubItemExists = function fieldConfigSubItemExists(subConfig, itemName) {
          return itemName in $scope.fieldConfig[$scope.currentField.name][subConfig];
        };

		$scope.openNewCustomFieldModal = function openNewCustomFieldModal() {
			var modalInstance = $modal.open({
				scope: $scope,
				templateUrl: '/angular-app/languageforge/lexicon/views/new-custom-field.html',
				controller: ['$scope', '$filter', '$modalInstance', function ($scope, $filter, $modalInstance) {
					$scope.selects = {};
					$scope.selects.level = {
						'optionsOrder': ['entry', 'senses', 'examples'],
						'options': {
							'entry': $filter('translate')('Entry Level'),
							'senses': $filter('translate')('Meaning Level'),
							'examples': $filter('translate')('Example Level')
						}
					};
					$scope.selects.type = {
						'optionsOrder': ['multitext', 'optionlist', 'multioptionlist'],
						'options': {
							'multitext': $filter('translate')('Multi-input-system Text'),
							'optionlist': $filter('translate')('Option List'),
							'multioptionlist': $filter('translate')('Multi-option List'),
							'reference': $filter('translate')('Entry Reference'),
							'picture': $filter('translate')('Picture'),
							'date': $filter('translate')('Date'),
							'number': $filter('translate')('Number')
						}
					};
					$scope.newCustomData = {
						'name': ''
					};
					$scope.customFieldNameExists = function customFieldNameExists(level, code) {
						var customFieldName = 'customField_' + level + '_' + code;
						return customFieldName in $scope.fieldConfig;
					};
					$scope.add = function add() {
						$modalInstance.close($scope.newCustomData);
					};

					$scope.$watch('newCustomData.name', function (newValue, oldValue) {
						if (angular.isDefined(newValue) && newValue !== oldValue) {

							// replace spaces with underscore
							$scope.newCustomData.code = newValue.replace(/ /g, '_');
						}
					});

				}]
			});

			modalInstance.result.then(function (newCustomData) {
				var customField = {},
					customViewField = {},
					customFieldName = 'customField_' + newCustomData.level + '_' + newCustomData.code;
				customField.label = newCustomData.name;
				customField.type = newCustomData.type;
				customField.hideIfEmpty = false;
				customViewField.type = 'basic';
				customViewField.show = false;
				if (newCustomData.type === 'multitext') {
					customField.displayMultiline = false;
					customField.width = 20;
					customField.inputSystems = [$scope.inputSystemsList[0].tag];
					customViewField.type = 'multitext';
					customViewField.overrideInputSystems = false;
					customViewField.inputSystems = [];
				}

				switch (newCustomData.level) {
					case 'examples':
						$scope.configDirty.entry.fields.senses.fields.examples.fields[customFieldName] = customField;
						$scope.fieldConfig[customFieldName] = $scope.configDirty.entry.fields.senses.fields.examples.fields[customFieldName];
						if (!(customFieldName in $scope.configDirty.entry.fields.senses.fields.examples.fieldOrder)) {
							$scope.configDirty.entry.fields.senses.fields.examples.fieldOrder.push(customFieldName);
						}
						break;
					case 'senses':
						$scope.configDirty.entry.fields.senses.fields[customFieldName] = customField;
						$scope.fieldConfig[customFieldName] = $scope.configDirty.entry.fields.senses.fields[customFieldName];
						if (!(customFieldName in $scope.configDirty.entry.fields.senses.fieldOrder)) {
							$scope.configDirty.entry.fields.senses.fieldOrder.push(customFieldName);
						}
						break;

					// 'entry'
					default:
						$scope.configDirty.entry.fields[customFieldName] = customField;
						$scope.fieldConfig[customFieldName] = $scope.configDirty.entry.fields[customFieldName];
						if (!(customFieldName in $scope.configDirty.entry.fieldOrder)) {
							$scope.configDirty.entry.fieldOrder.push(customFieldName);
						}
				}

				angular.forEach($scope.configDirty.roleViews, function (roleView) {
					roleView.fields[customFieldName] = angular.copy(customViewField);
				});
				$scope.configDirty.roleViews['project_manager'].fields[customFieldName].show = true;
				angular.forEach($scope.configDirty.userViews, function (userView) {
					userView.fields[customFieldName] = angular.copy(customViewField);
				});

				$scope.selectField(customFieldName);
				$scope.configForm.$setDirty();
			});
		};

		$scope.showRemoveCustomField = function showRemoveCustomField(fieldName) {
			if ($scope.isCustomField(fieldName) && !(fieldName in $scope.projectSettings.config.entry.fields) && !(fieldName in $scope.projectSettings.config.entry.fields.senses.fields) && !(fieldName in $scope.projectSettings.config.entry.fields.senses.fields.examples.fields)) {
				return true;
			}
			return false;
		};

		$scope.removeSelectedCustomField = function removeSelectedCustomField() {
			var fieldName = $scope.currentField.name,
				i;
			if ($scope.isCustomField(fieldName)) {
				delete $scope.fieldConfig[fieldName];

				// remove field name from fieldOrder
				i = $scope.configDirty.entry.fields.senses.fields.examples.fieldOrder.indexOf(fieldName);
				if (i > -1) {
					$scope.configDirty.entry.fields.senses.fields.examples.fieldOrder.splice(i, 1);
				}
				i = $scope.configDirty.entry.fields.senses.fieldOrder.indexOf(fieldName);
				if (i > -1) {
					$scope.configDirty.entry.fields.senses.fieldOrder.splice(i, 1);
				}
				i = $scope.configDirty.entry.fieldOrder.indexOf(fieldName);
				if (i > -1) {
					$scope.configDirty.entry.fieldOrder.splice(i, 1);
				}

				$scope.configForm.$setDirty();
				$scope.selectField('lexeme');
			}
		};

		$scope.editInputSystems = {
			'collapsed': true,
			'done': function () {
				this.collapsed = true;
			}
		};

		$scope.$watchCollection('currentField.inputSystems.selecteds', function (newValue) {
			if (angular.isDefined(newValue)) {
				if (angular.isDefined($scope.fieldConfig[$scope.currentField.name].inputSystems)) {
					$scope.fieldConfig[$scope.currentField.name].inputSystems = [];
					angular.forEach($scope.currentField.inputSystems.fieldOrder, function (tag) {
						if ($scope.currentField.inputSystems.selecteds[tag]) {
							$scope.fieldConfig[$scope.currentField.name].inputSystems.push(tag);
						}
					});
				}
			}
		});

	}])
	.controller('TaskConfigCtrl', ['$scope', '$filter', function ($scope, $filter) {
		$scope.selects.timeRange = {
			'optionsOrder': ['30days', '90days', '1year', 'all'],
			'options': {
				'30days': $filter('translate')('Up to 30 days'),
				'90days': $filter('translate')('Up to 90 days'),
				'1year': $filter('translate')('Up to 1 year'),
				'all': $filter('translate')('All')
			}
		};
		$scope.selects.language = {
			'options': {
				'en': $filter('translate')('English'),
				'es': $filter('translate')('Spanish'),
				'fr': $filter('translate')('French'),
				'hi': $filter('translate')('Hindi'),
				'id': $filter('translate')('Indonesian'),
				'km': $filter('translate')('Central Khmer'),
				'ne': $filter('translate')('Nepali'),
				'ru': $filter('translate')('Russian'),
				'th': $filter('translate')('Thai'),
				'ur': $filter('translate')('Urdu'),
				'zh-CN': $filter('translate')('Chinese')
			}
		};

		$scope.selectTask = function (taskName) {
			$scope.currentTaskName = taskName;
		};

	}])
	.controller('OptionListCtrl', ['$scope', function ($scope) {
		$scope.optionLists = {
			pos: {
				id: 'pos',
				name: 'Part of Speech',
				items: $scope.optionlistDirty[0].items,
				defaultKey: 'noun'
			}
		};
		$scope.currentListId = 'pos';

		$scope.selectList = function (listId) {
			$scope.currentListId = listId;
		};

		$scope.$watch('optionLists.pos.items', function (newval, oldval) {
			if (angular.isDefined(newval) && newval != oldval) {
				$scope.configForm.$setDirty();
			}
		}, true);

	}])
;
