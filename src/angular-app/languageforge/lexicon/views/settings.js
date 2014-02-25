'use strict';

angular.module('settings', ['jsonRpc', 'ui.bootstrap', 'bellows.services', 'palaso.ui.notice', 'palaso.ui.dc.entry', 'ngAnimate'])
.controller('SettingsCtrl', ['$scope', 'userService', 'sessionService', 'silNoticeService', 'lexEntryService', '$filter', 
                             function($scope, userService, ss, notice, lexService, $filter) {
	var projectId = $scope.routeParams.projectId;
	$scope.project = {
		'id': projectId
	};
	
	$scope.showPre = true;		// TODO Remove. Set false to hide <pre>. Remove this and all debug <pre> IJH 2014-02

	$scope.config = {};
	$scope.languageCodes = {};
	$scope.lists = {
		inputSystems: {}
	};
	
	$scope.selects = {
		'special': {
			'optionsOrder': ['none', 'ipaTranscription', 'voice', 'scriptRegionVariant'],
			'options': {
				'none': 'none',
				'ipaTranscription': 'IPA transcription',
				'voice': 'Voice',
				'scriptRegionVariant': 'Script / Region / Variant'
			}
		},
		'purpose': {
			'optionsOrder': ['etic', 'emic'],
			'options': {
				'etic': 'Etic (raw phonetic transcription)',
				'emic': 'Emic (uses the phonology of the language)'
			}
		},
		'script': {
			'options': inputSystems.scripts()
		},
		'region': {
			'options': inputSystems.regions()
		},
	};
	
	$scope.currentInputSystemTag = '';
	$scope.currentInputSystem = {
		'name': '',
		'code': '',
		'abbreviation': '',
		'special': '',
		'purpose': '',
		'script': '',
		'region': '',
		'variant': ''
	};
	$scope.selectInputSystem = function(inputSystemTag) {
		// TODO Add. update old before loading new IJH 2014-02
		$scope.currentInputSystemTag = inputSystemTag;
		$scope.currentInputSystem.name = $scope.lists.inputSystems[inputSystemTag].name;
		$scope.currentInputSystem.code = $scope.lists.inputSystems[inputSystemTag].code;
		$scope.currentInputSystem.abbreviation = $scope.lists.inputSystems[inputSystemTag].abbreviation;
		$scope.currentInputSystem = convertSelects($scope.currentInputSystem, $scope.lists.inputSystems[inputSystemTag]);
	};
	
	$scope.queryProjectSettings = function() {
		lexService.readProjectSettings($scope.project.id, function(result) {
			if (result.ok) {
				$scope.languageCodes = inputSystems.languageCodes();
				$scope.config = result.data.config;
				$scope.lists.inputSystems = $scope.config.inputSystems;
				for (var tag in $scope.lists.inputSystems) {
					var code = inputSystems.getCode(tag);
					var script = inputSystems.getScript(tag);
					var region = inputSystems.getRegion(tag);
					var privateUse = inputSystems.getPrivateUse(tag);
					$scope.lists.inputSystems[tag].code = code;
					$scope.lists.inputSystems[tag].script = script;
					$scope.lists.inputSystems[tag].region = region;
					$scope.lists.inputSystems[tag].privateUse = privateUse;
					$scope.lists.inputSystems[tag].name = inputSystems.getName(code, script, region, privateUse);
				};
				// select the first items
				$scope.selectInputSystem($filter('orderAsArray')($scope.config.inputSystems, 'tag')[0]['tag']);
				$scope.currentTaskName = 'dashboard';
			}
		});
	};

	$scope.settingsApply = function() {
//		console.log("settingsApply");
		lexService.updateProjectSettings($scope.project.id, $scope.config, function(result) {
			if (result.ok) {
				notice.push(notice.SUCCESS, "Project settings updated successfully");
				$scope.settingsForm.$setPristine();
			}
		});
	};
	
	// convert raw config inputSystems to use in selectors
	var convertSelects = function(selectorInputSystem, inputSystem) {
		selectorInputSystem.purpose = '';
		selectorInputSystem.script = '';
		selectorInputSystem.region = '';
		selectorInputSystem.variant = '';
		switch(inputSystem.script) {
			case '':
				selectorInputSystem.special = $scope.selects.special.optionsOrder[0];
				break;
			case 'fonipa':
				selectorInputSystem.special = $scope.selects.special.optionsOrder[1];
				selectorInputSystem.purpose = inputSystem.privateUse;
				break;
			case 'Zxxx':
				if (inputSystem.privateUse == 'audio') {
					selectorInputSystem.special = $scope.selects.special.optionsOrder[2];
					break;
				}
			default:
				selectorInputSystem.special = $scope.selects.special.optionsOrder[3];
				selectorInputSystem.script = inputSystem.script;
				selectorInputSystem.region = inputSystem.region;
				selectorInputSystem.variant = inputSystem.privateUse;
		}
		return selectorInputSystem;
	};
	
	$scope.queryProjectSettings();
	
	$scope.$watchCollection('currentInputSystem', function(newValue) {
//		console.log("current input system watch: ", newValue);
		if (newValue != undefined) {
			$scope.currentInputSystemTag = $scope.currentInputSystem.code;
			switch($scope.currentInputSystem.special) {
				case $scope.selects.special.optionsOrder[1]:		// IPA transcription
					$scope.currentInputSystemTag += '-fonipa';
					$scope.currentInputSystemTag += ($scope.currentInputSystem.purpose) ? '-x-' + $scope.currentInputSystem.purpose : '';
					break;
				case $scope.selects.special.optionsOrder[2]:		// Voice
					$scope.currentInputSystemTag += '-Zxxx-x-audio';
					break;
				case $scope.selects.special.optionsOrder[3]:		// Script / Region / Variant
					$scope.currentInputSystemTag += ($scope.currentInputSystem.script) ? '-' + $scope.currentInputSystem.script : '';
					$scope.currentInputSystemTag += ($scope.currentInputSystem.region) ? '-' + $scope.currentInputSystem.region : '';
					$scope.currentInputSystemTag += ($scope.currentInputSystem.variant) ? '-x-' + $scope.currentInputSystem.variant : '';
					break;
			}
		}
	});

}])
.controller('FieldSettingsCtrl', ['$scope', 'userService', 'sessionService', 'silNoticeService', 'lexEntryService', 
                                  function($scope, userService, ss, notice, lexService) {
	$scope.fieldconfig = {
		'lexeme': $scope.config.entry.fields['lexeme'],
		'definition': $scope.config.entry.fields.senses.fields['definition'],
		'partOfSpeech': $scope.config.entry.fields.senses.fields['partOfSpeech'],
		'semanticDomainValue': $scope.config.entry.fields.senses.fields['semanticDomainValue'],
		'example': $scope.config.entry.fields.senses.fields.examples.fields['example'],
		'translation': $scope.config.entry.fields.senses.fields.examples.fields['translation']
	};
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
		angular.forEach($scope.fieldconfig[fieldName].inputSystems, function(tag) {
			$scope.currentField.inputSystems.selecteds[tag] = true;
		});
		
		$scope.currentField.inputSystems.fieldOrder = $scope.fieldconfig[fieldName].inputSystems;
		angular.forEach($scope.config.inputSystems, function(inputSystem, tag) {
			if(! (tag in $scope.currentField.inputSystems.selecteds)) {
				$scope.currentField.inputSystems.fieldOrder.push(tag);
			}
		});
	};
	
	$scope.moveUp = function(currentTag) {
		var currentTagIndex = $scope.currentField.inputSystems.fieldOrder.indexOf(currentTag);
		$scope.currentField.inputSystems.fieldOrder[currentTagIndex] = $scope.currentField.inputSystems.fieldOrder[currentTagIndex - 1];
		$scope.currentField.inputSystems.fieldOrder[currentTagIndex - 1] = currentTag;
	};
	$scope.moveDown = function(currentTag) {
		var currentTagIndex = $scope.currentField.inputSystems.fieldOrder.indexOf(currentTag);
		$scope.currentField.inputSystems.fieldOrder[currentTagIndex] = $scope.currentField.inputSystems.fieldOrder[currentTagIndex + 1];
		$scope.currentField.inputSystems.fieldOrder[currentTagIndex + 1] = currentTag;
	};

	$scope.editInputSystems = {
		'collapsed': true,
		'done': function() {
			this.collapsed = true;
		}
	};
	
	$scope.$watch('config', function (newValue) {
		console.log("config Fields watch ", newValue);
		if (newValue != undefined) {
			// when config is updated select the first Feild in the list
			$scope.selectField('lexeme');
		}
	});
	$scope.$watchCollection('currentField.inputSystems.selecteds', function(newValue) {
		console.log("currentField.inputSystems.selecteds watch ", newValue);
		if (newValue != undefined) {
			$scope.fieldconfig[$scope.currentField.name].inputSystems = [];
			angular.forEach($scope.currentField.inputSystems.selecteds, function(selected, tag) {
				if (selected) {
					$scope.fieldconfig[$scope.currentField.name].inputSystems.push(tag);
				}
			});
		}
	});
	
}])
.controller('TaskSettingsCtrl', ['$scope', 'userService', 'sessionService', 'silNoticeService', 'lexEntryService', 
                                 function($scope, userService, ss, notice, lexService) {
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
