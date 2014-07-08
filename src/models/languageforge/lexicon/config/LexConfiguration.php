<?php

namespace models\languageforge\lexicon\config;

use models\languageforge\lexicon\InputSystem;
use models\languageforge\lexicon\LexiconRoles;
use models\mapper\MapOf;

class LexConfiguration {

	/**
	 * 
	 * @var MapOf <LexiconTask>
	 */
	public $tasks;
	
	/**
	 * 
	 * @var LexiconFieldConfigObj
	 */
	public $entry;
	
	/**
	 * uses LexiconRoles as key
	 * @var MapOf <LexRoleViewConfig>
	 */
	public $roleViews;
	
	function __construct() {
		$this->tasks = new MapOf(
			function($data) {
				switch ($data['type']) {
					case LexiconTask::DASHBOARD:
						return new LexiconDashboardTask();
					case LexiconTask::SEMDOM:
						return new LexiconSemdomTask();
					default:
						return new LexiconTask();
				}
			}	
		);
		
		$this->roleViews = new MapOf(function($data) {
			return new LexRoleViewConfig();
		});
		
		// default tasks values
		$this->tasks[LexiconTask::VIEW] = new LexiconTask();
		$this->tasks[LexiconTask::DASHBOARD] = new LexiconDashboardTask();
		$this->tasks[LexiconTask::GATHERTEXTS] = new LexiconTask();
		$this->tasks[LexiconTask::SEMDOM] = new LexiconSemdomTask();
		$this->tasks[LexiconTask::WORDLIST] = new LexiconTask();
		$this->tasks[LexiconTask::DBE] = new LexiconTask();
		$this->tasks[LexiconTask::ADDMEANINGS] = new LexiconTask();
		$this->tasks[LexiconTask::ADDGRAMMAR] = new LexiconTask();
		$this->tasks[LexiconTask::ADDEXAMPLES] = new LexiconTask();
		$this->tasks[LexiconTask::REVIEW] = new LexiconTask();
		$this->tasks[LexiconTask::IMPORTEXPORT] = new LexiconTask();
		$this->tasks[LexiconTask::CONFIGURATION] = new LexiconTask();
		
		// default entry fields values
		$this->entry = new LexiconFieldListConfigObj();
		$this->entry->fieldOrder[] = LexiconConfigObj::LEXEME;
		$this->entry->fieldOrder[] = LexiconConfigObj::SENSES_LIST;

		$this->entry->fields[LexiconConfigObj::LEXEME] = new LexiconMultitextConfigObj();
		$this->entry->fields[LexiconConfigObj::LEXEME]->label = 'Word';
		$this->entry->fields[LexiconConfigObj::LEXEME]->visible = true;
		$this->entry->fields[LexiconConfigObj::LEXEME]->inputSystems[] = 'th';

		$this->entry->fields[LexiconConfigObj::SENSES_LIST] = new LexiconFieldListConfigObj();
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fieldOrder[] = LexiconConfigObj::DEFINITION;
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fieldOrder[] = LexiconConfigObj::GLOSS;
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fieldOrder[] = LexiconConfigObj::POS;
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fieldOrder[] = LexiconConfigObj::SEMDOM;
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fieldOrder[] = LexiconConfigObj::EXAMPLES_LIST;

		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::DEFINITION] = new LexiconMultitextConfigObj();
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::DEFINITION]->label = 'Meaning';
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::DEFINITION]->visible = true;
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::DEFINITION]->inputSystems[] = 'en';
		
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::GLOSS] = new LexiconMultitextConfigObj();
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::GLOSS]->label = 'Gloss';
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::GLOSS]->visible = false;
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::GLOSS]->inputSystems[] = 'en';
		
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::POS] = new LexiconOptionlistConfigObj();
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::POS]->label = 'Part of Speech';
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::POS]->visible = true;
		$basicPosList = array(
			'Adjective' => 'Adjective',
			'Preposition' => 'Preposition',
			'Adverb' => 'Adverb',
			'Classifier' => 'Classifier',
			'Numeral' => 'Numeral',
			'Noun' => 'Noun',
			'Proper Noun' => 'Proper Noun',
			'Particle' => 'Particle',
			'Pronoun' => 'Pronoun',
			'Verb' => 'Verb'
		);
		$fullPosList = array(
			'Adjective' => 'Adjective',
			'Adposition' => 'Adposition',
			'Postposition' => 'Postposition',
			'Preposition' => 'Preposition',
			'Adverb' => 'Adverb',
			'Classifier' => 'Classifier',
			'Noun classifier' => 'Noun classifier',
			'Connective' => 'Connective',
			'Coordinating connective' => 'Coordinating connective',
			'Correlative connective' => 'Correlative connective',
			'Subordinating connective' => 'Subordinating connective',
			'Adverbializer' => 'Adverbializer',
			'Complementizer' => 'Complementizer',
			'Relativizer' => 'Relativizer',
			'Determiner' => 'Determiner',
			'Article' => 'Article',
			'Definite article' => 'Definite article',
			'Indefinite Article' => 'Indefinite Article',
			'Demonstrative' => 'Demonstrative',
			'Quantifier' => 'Quantifier',
			'Numeral' => 'Numeral',
			'Cardinal numeral' => 'Cardinal numeral',
			'Distributive numeral' => 'Distributive numeral',
			'Multiplicative numeral' => 'Multiplicative numeral',
			'Ordinal numeral' => 'Ordinal numeral',
			'Partitive numeral' => 'Partitive numeral',
			'Existential marker' => 'Existential marker',
			'Expletive' => 'Expletive',
			'Interjective' => 'Interjective',
			'Noun' => 'Noun',
			'Nominal' => 'Nominal',
			'Gerund' => 'Gerund',
			'Proper Noun' => 'Proper Noun',
			'Substantive' => 'Substantive',
			'Participle' => 'Participle',
			'Particle' => 'Particle',
			'Nominal particle' => 'Nominal particle',
			'Question particle' => 'Question particle',
			'Verbal particle' => 'Verbal particle',
			'Prenoun' => 'Prenoun',
			'Preverb' => 'Preverb',
			'Pro-form' => 'Pro-form',
			'Interrogative pro-form' => 'Interrogative pro-form',
			'Pro-adjective' => 'Pro-adjective',
			'Pro-adverb' => 'Pro-adverb',
			'Pronoun' => 'Pronoun',
			'Indefinite pronoun' => 'Indefinite pronoun',
			'Personal pronoun' => 'Personal pronoun',
			'Emphatic pronoun' => 'Emphatic pronoun',
			'Possessive pronoun' => 'Possessive pronoun',
			'Reflexive pronoun' => 'Reflexive pronoun',
			'Reciprocal pronoun' => 'Reciprocal pronoun',
			'Relative pronoun' => 'Relative pronoun',
			'Verb' => 'Verb',
			'Copulative verb' => 'Copulative verb',
			'Ditransitive verb' => 'Ditransitive verb',
			'Intransitive verb' => 'Intransitive verb',
			'Transitive verb' => 'Transitive verb'
		);
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::POS]->values->exchangeArray($fullPosList);
		
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::SEMDOM] = new LexiconOptionlistConfigObj();
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::SEMDOM]->label = 'Semantic Domain';
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::SEMDOM]->visible = true;
		// $this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::SEMDOM]->values is not used for semdom (values are populated in the client itself)

		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST] = new LexiconFieldListConfigObj();
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->visible = true;
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->fieldOrder[] = LexiconConfigObj::EXAMPLE_SENTENCE;
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->fieldOrder[] = LexiconConfigObj::EXAMPLE_TRANSLATION;

		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->fields[LexiconConfigObj::EXAMPLE_SENTENCE] = new LexiconMultitextConfigObj();
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->fields[LexiconConfigObj::EXAMPLE_SENTENCE]->label = 'Example';
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->fields[LexiconConfigObj::EXAMPLE_SENTENCE]->visible = true;
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->fields[LexiconConfigObj::EXAMPLE_SENTENCE]->inputSystems[] = 'th';
		
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->fields[LexiconConfigObj::EXAMPLE_TRANSLATION] = new LexiconMultitextConfigObj();
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->fields[LexiconConfigObj::EXAMPLE_TRANSLATION]->label = 'Translation';
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->fields[LexiconConfigObj::EXAMPLE_TRANSLATION]->visible = true;
		$this->entry->fields[LexiconConfigObj::SENSES_LIST]->fields[LexiconConfigObj::EXAMPLES_LIST]->fields[LexiconConfigObj::EXAMPLE_TRANSLATION]->inputSystems[] = 'en';
		
		// default role views values
		$this->roleViews[LexiconRoles::OBSERVER] = new LexRoleViewConfig();
		$this->roleViews[LexiconRoles::OBSERVER_WITH_COMMENT] = new LexRoleViewConfig();
		$this->roleViews[LexiconRoles::CONTRIBUTOR] = new LexRoleViewConfig();
		$this->roleViews[LexiconRoles::MANAGER] = new LexRoleViewConfig();
		
		$this->roleViews[LexiconRoles::OBSERVER]->isFieldsVisible[LexiconConfigObj::LEXEME] = true;
		$this->roleViews[LexiconRoles::OBSERVER]->isFieldsVisible[LexiconConfigObj::DEFINITION] = true;
		$this->roleViews[LexiconRoles::OBSERVER]->isFieldsVisible[LexiconConfigObj::GLOSS] = false;
		$this->roleViews[LexiconRoles::OBSERVER]->isFieldsVisible[LexiconConfigObj::POS] = true;
		$this->roleViews[LexiconRoles::OBSERVER]->isFieldsVisible[LexiconConfigObj::SEMDOM] = true;
		$this->roleViews[LexiconRoles::OBSERVER]->isFieldsVisible[LexiconConfigObj::EXAMPLE_SENTENCE] = true;
		$this->roleViews[LexiconRoles::OBSERVER]->isFieldsVisible[LexiconConfigObj::EXAMPLE_TRANSLATION] = true;
		$this->roleViews[LexiconRoles::OBSERVER_WITH_COMMENT]->isFieldsVisible = clone $this->roleViews[LexiconRoles::OBSERVER]->isFieldsVisible;
		$this->roleViews[LexiconRoles::CONTRIBUTOR]->isFieldsVisible = clone $this->roleViews[LexiconRoles::OBSERVER]->isFieldsVisible;
		$this->roleViews[LexiconRoles::MANAGER]->isFieldsVisible = clone $this->roleViews[LexiconRoles::OBSERVER]->isFieldsVisible;
		
		$this->roleViews[LexiconRoles::OBSERVER]->isTasksVisible[LexiconTask::VIEW] = true;
		$this->roleViews[LexiconRoles::OBSERVER]->isTasksVisible[LexiconTask::DASHBOARD] = true;
		$this->roleViews[LexiconRoles::OBSERVER]->isTasksVisible[LexiconTask::GATHERTEXTS] = false;
		$this->roleViews[LexiconRoles::OBSERVER]->isTasksVisible[LexiconTask::SEMDOM] = false;
		$this->roleViews[LexiconRoles::OBSERVER]->isTasksVisible[LexiconTask::WORDLIST] = false;
		$this->roleViews[LexiconRoles::OBSERVER]->isTasksVisible[LexiconTask::DBE] = true;
		$this->roleViews[LexiconRoles::OBSERVER]->isTasksVisible[LexiconTask::ADDMEANINGS] = false;
		$this->roleViews[LexiconRoles::OBSERVER]->isTasksVisible[LexiconTask::ADDGRAMMAR] = false;
		$this->roleViews[LexiconRoles::OBSERVER]->isTasksVisible[LexiconTask::ADDEXAMPLES] = false;
		$this->roleViews[LexiconRoles::OBSERVER]->isTasksVisible[LexiconTask::REVIEW] = false;
		
		$this->roleViews[LexiconRoles::OBSERVER_WITH_COMMENT]->isTasksVisible = clone $this->roleViews[LexiconRoles::OBSERVER]->isTasksVisible;
		
		$this->roleViews[LexiconRoles::CONTRIBUTOR]->isTasksVisible = clone $this->roleViews[LexiconRoles::OBSERVER]->isTasksVisible;
		$this->roleViews[LexiconRoles::CONTRIBUTOR]->isTasksVisible[LexiconTask::ADDMEANINGS] = true;
		$this->roleViews[LexiconRoles::CONTRIBUTOR]->isTasksVisible[LexiconTask::ADDGRAMMAR] = true;
		$this->roleViews[LexiconRoles::CONTRIBUTOR]->isTasksVisible[LexiconTask::ADDEXAMPLES] = true;
		
		$this->roleViews[LexiconRoles::MANAGER]->isTasksVisible = clone $this->roleViews[LexiconRoles::CONTRIBUTOR]->isTasksVisible;
		$this->roleViews[LexiconRoles::MANAGER]->isTasksVisible[LexiconTask::GATHERTEXTS] = true;
		$this->roleViews[LexiconRoles::MANAGER]->isTasksVisible[LexiconTask::SEMDOM] = true;
		$this->roleViews[LexiconRoles::MANAGER]->isTasksVisible[LexiconTask::WORDLIST] = true;
		$this->roleViews[LexiconRoles::MANAGER]->isTasksVisible[LexiconTask::REVIEW] = true;
		
	}
}

?>
