import * as angular from 'angular';
import { QuillMoreComponent } from './quill-more.component';
import { QuillSuggestionComponent } from './quill-suggestion.component';
import { registerSuggestionsTheme } from './suggestions-theme';

export const QuillModule = angular
  .module('translate.quill', [])
  .run(registerSuggestionsTheme)
  .component('qlMore', QuillMoreComponent)
  .component('qlSuggestion', QuillSuggestionComponent)
  .name;
