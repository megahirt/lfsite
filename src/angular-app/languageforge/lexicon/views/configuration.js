'use strict';

angular.module('lexicon.configuration', ['ui.bootstrap', 'bellows.services', 'palaso.ui.notice', 'palaso.ui.language', 'ngAnimate'])
.controller('ConfigCtrl', ['$scope', 'silNoticeService', 'lexProjectService', 'sessionService', '$filter', '$modal', 
                           function($scope, notice, lexProjectService, ss, $filter, $modal) {
	lexProjectService.setBreadcrumbs('configuration', 'Dictionary Configuration');
	$scope.configDirty = angular.copy($scope.config);

	$scope.haveConfig = function() {
		return angular.isDefined($scope.configDirty.entry);
	};

	$scope.inputSystems = {};
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
		},
	};
	
	$scope.currentInputSystemTag = '';
	$scope.selectInputSystem = function(inputSystemTag) {
		$scope.currentInputSystemTag = inputSystemTag;
	};
	
	setupView();

	function sortInputSystemsList() {
		return $filter('orderBy')($filter('orderAsArray')($scope.configDirty.inputSystems, 'tag'), 'name');
	};

	function setupView() {
		if (angular.isDefined($scope.configDirty.inputSystems)) {
			$scope.inputSystems = $scope.configDirty.inputSystems;
			for (var tag in $scope.inputSystems) {
				var script = InputSystems.getScript(tag);
				var privateUse = InputSystems.getPrivateUse(tag);
				$scope.inputSystems[tag].name = InputSystems.getName($scope.inputSystems[tag].languageName, tag);
				$scope.inputSystems[tag].code = InputSystems.getCode(tag);
				$scope.inputSystems[tag].purpose = '';
				$scope.inputSystems[tag].script = '';
				$scope.inputSystems[tag].region = '';
				$scope.inputSystems[tag].variant = '';
				switch(script) {
					case '':
						$scope.inputSystems[tag].special = $scope.selects.special.optionsOrder[0];
						break;
					case 'fonipa':
						$scope.inputSystems[tag].special = $scope.selects.special.optionsOrder[1];
						$scope.inputSystems[tag].purpose = privateUse;
						break;
					case 'Zxxx':
						if (privateUse == 'audio') {
							$scope.inputSystems[tag].special = $scope.selects.special.optionsOrder[2];
							break;
						}
					default:
						$scope.inputSystems[tag].special = $scope.selects.special.optionsOrder[3];
						$scope.inputSystems[tag].script = script;
						$scope.inputSystems[tag].region = InputSystems.getRegion(tag);
						$scope.inputSystems[tag].variant = privateUse;
				}
			};
			$scope.inputSystemsList = sortInputSystemsList();
			
			// select the first items
			$scope.selectInputSystem($scope.inputSystemsList[0].tag);
			$scope.currentTaskName = 'dashboard';
			
			// for FieldConfigCtrl
			$scope.fieldConfig = {
				'lexeme': $scope.configDirty.entry.fields['lexeme'],
				'definition': $scope.configDirty.entry.fields.senses.fields['definition'],
				'gloss': $scope.configDirty.entry.fields.senses.fields['gloss'],
				'partOfSpeech': $scope.configDirty.entry.fields.senses.fields['partOfSpeech'],
				'semanticDomain': $scope.configDirty.entry.fields.senses.fields['semanticDomain'],
				'sentence': $scope.configDirty.entry.fields.senses.fields.examples.fields['sentence'],
				'translation': $scope.configDirty.entry.fields.senses.fields.examples.fields['translation']
			};
		}
	};
	
	$scope.configurationApply = function() {
		lexProjectService.updateConfiguration($scope.configDirty, function(result) {
			if (result.ok) {
				notice.push(notice.SUCCESS, $filter('translate')("Dictionary configuration updated successfully"));
				$scope.configForm.$setPristine();
				$scope.config = angular.copy($scope.configDirty);
				setupView();
			}
		});
	};
	
// InputSystemsConfigCtrl
	$scope.newExists = function(code, special) {
		var tag = code;
		switch(special) {
		case $scope.selects.special.optionsOrder[1]:		// IPA transcription
			tag += '-fonipa';
			break;
		case $scope.selects.special.optionsOrder[2]:		// Voice
			tag += '-Zxxx-x-audio';
			break;
		case $scope.selects.special.optionsOrder[3]:		// Script / Region / Variant
			tag += '-unspecified';
			break;
	}
		return (tag in $scope.inputSystems);
	};
	$scope.addInputSystem = function(code, languageName, special) {
		var tag = 'xxNewTagxx';
		var script = '';
		$scope.inputSystems[tag] = {};
		$scope.inputSystems[tag].languageName = languageName;
		$scope.inputSystems[tag].abbreviation = code;
		$scope.inputSystems[tag].script = '';
		switch(special) {
			case $scope.selects.special.optionsOrder[1]:		// IPA transcription
				script = 'fonipa';
				$scope.inputSystems[tag].abbreviation = code + 'ipa';
				break;
			case $scope.selects.special.optionsOrder[2]:		// Voice
				script = 'Zxxx';
				$scope.inputSystems[tag].abbreviation = code + 'audio';
				break;
			case $scope.selects.special.optionsOrder[3]:		// Script / Region / Variant
				script = 'unspecified';
				$scope.inputSystems[tag].script = script;
				$scope.inputSystems[tag].abbreviation = code + '-';
				break;
		}
		$scope.inputSystems[tag].code = code;
		$scope.inputSystems[tag].special = special;
		$scope.inputSystems[tag].purpose = '';
		$scope.inputSystems[tag].region = '';
		$scope.inputSystems[tag].variant = '';
		$scope.currentInputSystemTag = tag;
	};
	$scope.removeInputSystem = function(currentInputSystemTag) {
		delete $scope.inputSystems[currentInputSystemTag];
		$scope.inputSystemsList = sortInputSystemsList();
		$scope.configForm.$setDirty();
		// select the first items
		$scope.selectInputSystem($scope.inputSystemsList[0].tag);
	};
	
	$scope.openNewLanguageModal = function() {
		var modalInstance = $modal.open({
			templateUrl: '/angular-app/languageforge/lexicon/views/select-new-language.html',
			controller: function($scope, $modalInstance) {
				$scope.selected = {
					code: '',
					language: {}
				};
				$scope.add = function () {
					$modalInstance.close($scope.selected);
				};
				
			}
		});
		
		modalInstance.result.then(function (selected) {
			$scope.addInputSystem(selected.code, selected.language.name, $scope.selects.special.optionsOrder[0]);
		});

	};
	
	$scope.$watchCollection('inputSystems[currentInputSystemTag]', function(newValue) {
		if (newValue != undefined) {
			var tag = $scope.currentInputSystemTag;
			var newTag = $scope.inputSystems[tag].code;
			switch($scope.inputSystems[tag].special) {
				case $scope.selects.special.optionsOrder[1]:		// IPA transcription
					newTag += '-fonipa';
					newTag += ($scope.inputSystems[tag].purpose) ? '-x-' + $scope.inputSystems[tag].purpose : '';
					break;
				case $scope.selects.special.optionsOrder[2]:		// Voice
					newTag += '-Zxxx-x-audio';
					break;
				case $scope.selects.special.optionsOrder[3]:		// Script / Region / Variant
					if (! $scope.inputSystems[tag].script && ! $scope.inputSystems[tag].region)  {
						$scope.inputSystems[tag].script = 'unspecified';
					}
					newTag += ($scope.inputSystems[tag].script) ? '-' + $scope.inputSystems[tag].script : '';
					newTag += ($scope.inputSystems[tag].region) ? '-' + $scope.inputSystems[tag].region : '';
					newTag += ($scope.inputSystems[tag].variant) ? '-x-' + $scope.inputSystems[tag].variant : '';
					break;
			}
			$scope.inputSystems[tag].name = InputSystems.getName($scope.inputSystems[tag].languageName, newTag);
			if (tag != newTag) {
				if (! (newTag in $scope.inputSystems)) {
					$scope.inputSystems[tag].tag = newTag;
					$scope.inputSystems[newTag] = $scope.inputSystems[tag];
					$scope.configForm.$setDirty();
				}
				delete $scope.inputSystems[tag];
				$scope.selectInputSystem(newTag);
			}
			$scope.inputSystemsList = sortInputSystemsList();
		}
	});

}])
.controller('FieldConfigCtrl', ['$scope', function($scope) {
	$scope.currentField = {
		'name': '',
		'inputSystems': {
			'fieldOrder': [],
			'selecteds': {}
		}
	};
	$scope.selectField = function(fieldName) {
		$scope.currentField.name = fieldName;

		$scope.currentField.inputSystems.selecteds = {};
		angular.forEach($scope.fieldConfig[fieldName].inputSystems, function(tag) {
			$scope.currentField.inputSystems.selecteds[tag] = true;
		});
		
		// if the field uses input systems, add the selected systems first then the unselected systems
		if ($scope.fieldConfig[fieldName].inputSystems) {
			$scope.currentField.inputSystems.fieldOrder = $scope.fieldConfig[fieldName].inputSystems;
			angular.forEach($scope.configDirty.inputSystems, function(inputSystem, tag) {
				if(! (tag in $scope.currentField.inputSystems.selecteds)) {
					$scope.currentField.inputSystems.fieldOrder.push(tag);
				}
			});
		}
	};
	
	$scope.moveUp = function(currentTag) {
		var currentTagIndex = $scope.currentField.inputSystems.fieldOrder.indexOf(currentTag);
		$scope.currentField.inputSystems.fieldOrder[currentTagIndex] = $scope.currentField.inputSystems.fieldOrder[currentTagIndex - 1];
		$scope.currentField.inputSystems.fieldOrder[currentTagIndex - 1] = currentTag;
		$scope.fieldConfig[$scope.currentField.name].inputSystems = [];
		angular.forEach($scope.currentField.inputSystems.fieldOrder, function(tag) {
			if ($scope.currentField.inputSystems.selecteds[tag]) {
				$scope.fieldConfig[$scope.currentField.name].inputSystems.push(tag);
			}
		});
		$scope.configForm.$setDirty();
	};
	$scope.moveDown = function(currentTag) {
		var currentTagIndex = $scope.currentField.inputSystems.fieldOrder.indexOf(currentTag);
		$scope.currentField.inputSystems.fieldOrder[currentTagIndex] = $scope.currentField.inputSystems.fieldOrder[currentTagIndex + 1];
		$scope.currentField.inputSystems.fieldOrder[currentTagIndex + 1] = currentTag;
		$scope.fieldConfig[$scope.currentField.name].inputSystems = [];
		angular.forEach($scope.currentField.inputSystems.fieldOrder, function(tag) {
			if ($scope.currentField.inputSystems.selecteds[tag]) {
				$scope.fieldConfig[$scope.currentField.name].inputSystems.push(tag);
			}
		});
		$scope.configForm.$setDirty();
	};

	$scope.editInputSystems = {
		'collapsed': true,
		'done': function() {
			this.collapsed = true;
		}
	};
	
	$scope.$watch('configDirty', function (newValue) {
		if (angular.isDefined(newValue) && $scope.haveConfig()) {
			// when configDirty is updated select the first field in the list
			$scope.selectField('lexeme');
		}
	});
	$scope.$watchCollection('currentField.inputSystems.selecteds', function(newValue) {
		if (angular.isDefined(newValue) && $scope.haveConfig()) {
			if ($scope.fieldConfig[$scope.currentField.name].inputSystems) {
				$scope.fieldConfig[$scope.currentField.name].inputSystems = [];
				angular.forEach($scope.currentField.inputSystems.fieldOrder, function(tag) {
					if ($scope.currentField.inputSystems.selecteds[tag]) {
						$scope.fieldConfig[$scope.currentField.name].inputSystems.push(tag);
					}
				});
			}
		}
	});
	
}])
.controller('TaskConfigCtrl', ['$scope', function($scope) {
	$scope.selects.timeRange = {
		'optionsOrder': ['30days', '90days', '1year', 'all'],
		'options': {
			'30days': 'Up to 30 days',
			'90days': 'Up to 90 days',
			'1year': 'Up to 1 year',
			'all': 'All'
		}
	};
	$scope.selects.language = {
		'options': {
			'en': 'English',
			'es': 'Spanish',
			'fr': 'French',
			'hi': 'Hindi',
			'id': 'Indonesian',
			'km': 'Central Khmer',
			'ne': 'Nepali',
			'ru': 'Russian',
			'th': 'Thai',
			'ur': 'Urdu',
			'zh-CN': 'Chinese'
		}
	};
	
	$scope.selectTask = function(taskName) {
		$scope.currentTaskName = taskName;
	};

}])
;
