import * as angular from 'angular';

import {ActivityService} from '../../../bellows/core/api/activity.service';
import {ApplicationHeaderService} from '../../../bellows/core/application-header.service';
import {ModalService} from '../../../bellows/core/modal/modal.service';
import {NoticeService} from '../../../bellows/core/notice/notice.service';
import {EditorDataService} from '../../../bellows/core/offline/editor-data.service';
import {LexiconCommentService} from '../../../bellows/core/offline/lexicon-comments.service';
import {SessionService} from '../../../bellows/core/session.service';
import {InterfaceConfig} from '../../../bellows/shared/model/interface-config.model';
import {LexiconConfigService} from '../core/lexicon-config.service';
import {LexiconEntryApiService} from '../core/lexicon-entry-api.service';
import {LexiconProjectService} from '../core/lexicon-project.service';
import {LexiconRightsService, Rights} from '../core/lexicon-rights.service';
import {LexiconSendReceiveService} from '../core/lexicon-send-receive.service';
import {LexiconUtilityService} from '../core/lexicon-utility.service';
import {LexEntry} from '../shared/model/lex-entry.model';
import {LexPicture} from '../shared/model/lex-picture.model';
import {
  LexConfig,
  LexConfigField,
  LexConfigFieldList,
  LexConfigMultiText, LexConfigOptionList,
  LexiconConfig
} from '../shared/model/lexicon-config.model';
import {LexiconProject} from '../shared/model/lexicon-project.model';
import {FieldControl} from './field/field-control.model';

interface WindowService extends angular.IWindowService {
  semanticDomains_en?: any;
}

class SortOption {
  label: string;
  value: string;
}

class FilterOption {
  inputSystem?: string;
  key: string;
  label: string;
  level?: string;
  type: string;
  value: string;
}

class Show {
  more: () => void;
  emptyFields: boolean = false;
  entryListModifiers: boolean = false;
}

class TypeAhead {
  limit: number;
  matchCountCaption: string;
  searchEntries?: (query: string) => void;
  searchItemSelected: string;
  searchResults: string[];
  searchSelect?: (entry: LexEntry) => void;
}

export class LexiconEditorController implements angular.IController {
  // inherited from parent (convert to component attributes)
  interfaceConfig: InterfaceConfig;
  finishedLoading: boolean;
  project: LexiconProject;
  rights: Rights;

  lastSavedDate = new Date();
  currentEntry: LexEntry = new LexEntry();
  commentContext = {
    contextGuid: ''
  };
  rightPanelVisible = false;
  show: Show = new Show();
  // status is tri-state: unsaved, saving, saved
  saveStatus = 'unsaved';

  autoSaveTimer: angular.IPromise<void>;
  config: LexiconConfig;
  control: FieldControl = new FieldControl();
  typeahead: TypeAhead;

  entries = this.editorService.entries;
  entryListModifiers = this.editorService.entryListModifiers;
  filteredEntries = this.editorService.filteredEntries;
  getEntryCommentCount = this.commentService.getEntryCommentCount;
  getPrimaryListItemForDisplay = this.editorService.getSortableValue;
  visibleEntries = this.editorService.visibleEntries;
  unreadCount = this.activityService.unreadCount;

  private pristineEntry: LexEntry = new LexEntry();
  private warnOfUnsavedEditsId: string;

  static $inject = ['$interval', '$q',
    '$scope', '$state',
    '$window', 'activityService',
    'applicationHeaderService',
    'modalService', 'silNoticeService',
    'sessionService', 'lexCommentService',
    'lexConfigService', 'lexEditorDataService',
    'lexEntryApiService',
    'lexProjectService',
    'lexRightsService',
    'lexSendReceive'
  ];
  constructor(private readonly $interval: angular.IIntervalService, private readonly $q: angular.IQService,
              private readonly $scope: angular.IScope, private readonly $state: angular.ui.IStateService,
              private readonly $window: WindowService, private readonly activityService: ActivityService,
              private readonly applicationHeaderService: ApplicationHeaderService,
              private readonly modal: ModalService, private readonly notice: NoticeService,
              private readonly sessionService: SessionService, private readonly commentService: LexiconCommentService,
              private readonly configService: LexiconConfigService, private readonly editorService: EditorDataService,
              private readonly lexService: LexiconEntryApiService,
              private readonly lexProjectService: LexiconProjectService,
              private readonly rightsService: LexiconRightsService,
              private readonly sendReceive: LexiconSendReceiveService) {}

  $onInit(): void {
    // hack until lexicon.js and this are components
    const deregisterWatchFinishedLoading = this.$scope.$watch(() => (this.$scope as any).finishedLoading, () => {
      this.finishedLoading = (this.$scope as any).finishedLoading;
      this.interfaceConfig = (this.$scope as any).interfaceConfig;
      if (this.finishedLoading) {
        deregisterWatchFinishedLoading();
        this.control = {
          interfaceConfig: this.interfaceConfig,
          commentContext: this.commentContext,
          config: this.config,
          currentEntry: this.currentEntry,
          deleteEntry: this.deleteEntry,
          getContextParts: this.getContextParts,
          hideRightPanel: this.hideRightPanel,
          makeValidModelRecursive: this.makeValidModelRecursive,
          project: this.project,
          saveCurrentEntry: this.saveCurrentEntry,
          setCommentContext: this.setCommentContext,
          show: this.show,
          showCommentsPanel: this.showCommentsPanel,
          rightPanelVisible: this.rightPanelVisible,
          rights: this.rights
        } as FieldControl;
      }
    });

    this.show.more = this.editorService.showMoreEntries;

    this.configService.refresh().then(config => {
      this.config = config;
      // hack until lexicon.js and this are components
      this.control.config = this.config;
    });

    this.$scope.$watch(() => this.config, () => {
      this.setSortAndFilterOptionsFromConfig();
    });

    this.rightsService.getRights(true).then(rights => {
      this.rights = rights;
      this.project = rights.session.project<LexiconProject>();
      this.applicationHeaderService.setPageName(this.project.projectName);
      // hack until lexicon.js and this are components
      this.control.rights = this.rights;
      this.control.project = this.project;

      // conditionally register watch
      if (this.rights.canEditEntry()) {
        this.$scope.$watch(() => this.currentEntry, newValue => {
          if (newValue !== undefined) {
            this.cancelAutoSaveTimer();
            if (this.currentEntryIsDirty()) {
              this.startAutoSaveTimer();
            }
          }
        }, true);
      }
    });

    // watch for when data has been loaded completely, then evaluate state
    this.$scope.$watch(() => this.finishedLoading, newVal => {
      if (newVal) {
        this.evaluateState();
      }
    });

    this.sendReceive.setPollUpdateSuccessCallback(this.pollUpdateSuccess);
    this.sendReceive.setSyncProjectStatusSuccessCallback(this.syncProjectStatusSuccess);

    this.$scope.$on('$locationChangeStart', (event, next, current) => {
      if (current.includes('#!/editor/entry') && !next.includes('#!/editor/entry')) {
        this.cancelAutoSaveTimer();
        this.saveCurrentEntry();
      }
    });

    this.setupTypeAheadSearch();
  }

  $onDestroy(): void {
    this.cancelAutoSaveTimer();
    this.saveCurrentEntry();
  }

  isAtEditorList(): boolean {
    return LexiconUtilityService.isAtEditorList(this.$state);
  }

  isAtEditorEntry(): boolean {
    return LexiconUtilityService.isAtEditorEntry(this.$state);
  }

  sortEntries(args: any): void {
    this.editorService.sortEntries.apply(this, arguments).then(() => {
      this.typeahead.searchEntries(this.typeahead.searchItemSelected);
    });
  }

  filterEntries(args: any): void {
    this.editorService.filterEntries.apply(this, arguments).then(() => {
      this.typeahead.searchEntries(this.typeahead.searchItemSelected);
    });
  }

  currentEntryIsDirty(): boolean {
    if (!this.entryLoaded()) {
      return false;
    }

    return !angular.equals(this.currentEntry, this.pristineEntry);
  }

  private static entryIsNew(entry: LexEntry): boolean {
    return (entry.id && entry.id.includes('_new_'));
  }

  saveNotice(): string {
    switch (this.saveStatus) {
      case 'saving':
        return 'Saving';
      case 'saved':
        return 'Saved';
      default:
        return '';
    }
  }

  saveButtonTitle(): string {
    if (this.currentEntryIsDirty()) {
      return 'Save Entry';
    } else if (LexiconEditorController.entryIsNew(this.currentEntry)) {
      return 'Entry unchanged';
    } else {
      return 'Entry saved';
    }
  }

  private resetEntryLists(id: string, pristineEntry: LexEntry): void {
    const entryIndex = this.editorService.getIndexInList(id, this.entries);
    const entry = this.prepCustomFieldsForUpdate(pristineEntry);
    if (entryIndex != null) {
      this.entries[entryIndex] = entry;
      this.currentEntry = pristineEntry;
      this.control.currentEntry = this.currentEntry;
    }

    const visibleEntryIndex = this.editorService.getIndexInList(id, this.visibleEntries);
    if (visibleEntryIndex != null) {
      this.visibleEntries[visibleEntryIndex] = entry;
    }
  }

  private warnOfUnsavedEdits(entry: LexEntry): void {
    this.warnOfUnsavedEditsId = this.notice.push(this.notice.WARN, 'A synchronize has been started by ' +
      'another user. When the synchronize has finished, please check your recent edits in entry "' +
      this.getWordForDisplay(entry) + '".');
  }

  saveCurrentEntry = (doSetEntry: boolean = false, successCallback: () => void = () => {},
                      failCallback: (reason?: any) => void = () => {}) => {
    // `doSetEntry` is mainly used for when the save button is pressed, that is when the user is saving the current
    // entry and is NOT going to a different entry (as is the case with editing another entry.
    let isNewEntry = false;
    let newEntryTempId: string;

    if (this.currentEntryIsDirty() && this.rights.canEditEntry()) {
      this.cancelAutoSaveTimer();
      this.sendReceive.setStateUnsynced();
      this.saveStatus = 'saving';
      this.currentEntry = LexiconEditorController.normalizeStrings(this.currentEntry);
      this.control.currentEntry = this.currentEntry;
      const entryToSave = angular.copy(this.currentEntry);
      if (LexiconEditorController.entryIsNew(entryToSave)) {
        isNewEntry = true;
        newEntryTempId = entryToSave.id;
        entryToSave.id = ''; // send empty id to indicate "create new"
      }

      return this.$q.all({
        entry: this.lexService.update(this.prepEntryForUpdate(entryToSave)),
        isSR: this.sendReceive.isSendReceiveProject()
      }).then(data => {
        const entry = data.entry.data;
        if (!entry && data.isSR) {
          this.warnOfUnsavedEdits(entryToSave);
          this.sendReceive.startSyncStatusTimer();
        }

        if (!entry) {
          this.resetEntryLists(this.currentEntry.id, angular.copy(this.pristineEntry));
        }

        if (isNewEntry) {
          // note: we have to reset the show window, because we don't know
          // where the new entry will show up in the list
          // we can solve this problem by implementing a sliding "scroll
          // window" that only shows a few entries at a time (say 30?)
          this.editorService.showInitialEntries();
        }

        /*
        * Reviewed CP 2014-08: It seems that currently the setCurrentEntry
        * will never do anything. Currently it has the side effect of causing
        * the focus to be lost. Given that we save the entire model We will
        * never get data returned other than what we just caused to be saved.
        *
        * One day we hope to send deltas which will fix this problem and give
        * a better real time experience.
        */

        /* Reviewed CJH 2015-03: setCurrentEntry is useful in the case when the entry being
        * saved is a new entry. In this case the new entry is replaced entirely by the one
        * returned from the server (with a proper id, etc).
        * I'm currently unclear on whether the doSetEntry parameter is still necessary
        */

        if (entry) {
          this.pristineEntry = angular.copy(entryToSave);
          this.lastSavedDate = new Date();
        }

        // refresh data will add the new entry to the entries list
        this.editorService.refreshEditorData().then(() => {
          if (entry && isNewEntry) {
            this.setCurrentEntry(this.entries[this.editorService.getIndexInList(entry.id, this.entries)]);
            this.editorService.removeEntryFromLists(newEntryTempId);
            if (doSetEntry) {
              this.$state.go('.', { entryId: entry.id }, { notify: false });
              this.scrollListToEntry(entry.id, 'top');
            }
          }
        });

        this.saveStatus = 'saved';
        successCallback();
      }).catch(reason => {
        this.saveStatus = 'unsaved';
        failCallback(reason);
      });
    } else {
      successCallback();
    }
  }

  private static normalizeStrings(data: any): any {
    return JSON.parse(JSON.stringify(data).normalize());
  }

  private prepEntryForUpdate(entry: LexEntry): LexEntry {
    const entryForUpdate: LexEntry = this.recursiveRemoveProperties(angular.copy(entry),
      ['guid', 'mercurialSha', 'authorInfo', 'dateCreated', 'dateModified', 'liftId', '$$hashKey']);
    return this.prepCustomFieldsForUpdate(entryForUpdate);
  }

  getWordForDisplay(entry: LexEntry): string {
    const lexeme: string = LexiconUtilityService.getLexeme(this.config, this.config.entry, entry);
    if (!lexeme) {
      return '[Empty]';
    }

    return lexeme;
  }

  getMeaningForDisplay(entry: LexEntry): string {
    let meaning = '';
    if (entry.senses && entry.senses[0]) {
      meaning = LexiconUtilityService.getMeaning(this.config, this.config.entry.fields.senses as LexConfigFieldList,
        entry.senses[0]);
    }

    if (!meaning) {
      return '[Empty]';
    }

    return meaning;
  }

  navigateToLiftImport(): void {
    this.$state.go('importExport');
  }

  private static scrollDivToId(containerId: string, divId: string, posOffset: number = 0): void {
    const $containerDiv: any = $(containerId);
    let $div: any = $(divId);
    let foundDiv: boolean = false;
    let offsetTop: number = 0;

    // todo: refactor this spaghetti logic
    if ($div && $containerDiv) {
      if ($div.offsetTop == null) {
        if ($div[0] != null) {
          $div = $div[0];
          foundDiv = true;
        } else {
          console.log('Error: unable to scroll to div with div id ' + divId);
        }
      }

      if (foundDiv) {
        if ($div.offsetTop == null) {
          offsetTop = $div.offset().top - posOffset;
        } else {
          offsetTop = $div.offsetTop - posOffset;
        }

        if (offsetTop < 0) {
          offsetTop = 0;
        }
        $containerDiv.scrollTop(offsetTop);
      }
    }
  }

  private scrollListToEntry(id: string, position: string): void {
    const posOffset = (position === 'top') ? 274 : 487;
    const entryDivId = '#entryId_' + id;
    const listDivId = '#compactEntryListContainer';
    let index;

    // make sure the item is visible in the list
    // todo implement lazy "up" scrolling to make this more efficient

    // only expand the "show window" if we know that the entry is actually in
    // the entry list - a safe guard
    if (this.editorService.getIndexInList(id, this.filteredEntries) != null) {
      while (this.visibleEntries.length < this.filteredEntries.length) {
        index = this.editorService.getIndexInList(id, this.visibleEntries);
        if (index != null) {
          break;
        }

        this.editorService.showMoreEntries();
      }
    } else {
      console.warn('Error: tried to scroll to an entry that is not in the entry list!');
    }

    // note: ':visible' is a JQuery invention that means 'it takes up space on
    // the page'.
    // It may actually not be visible at the moment because it may down inside a
    // scrolling div or scrolled off the view of the page
    if ($(listDivId).is(':visible') && $(entryDivId).is(':visible')) {
      LexiconEditorController.scrollDivToId(listDivId, entryDivId, posOffset);
    } else {
      // wait then try to scroll
      this.$interval(() => {
        LexiconEditorController.scrollDivToId(listDivId, entryDivId, posOffset);
      }, 200, 1);
    }
  }

  editEntryAndScroll(id: string): void {
    this.editEntry(id);
    this.scrollListToEntry(id, 'middle');
  }

  private setCurrentEntry(entry: LexEntry = new LexEntry()): void {
    // align custom fields into model
    entry = this.alignCustomFieldsInData(entry);

    // auto-make a valid model but stop at the examples array
    entry = this.makeValidModelRecursive(this.config.entry, entry, 'examples');

    this.currentEntry = entry;
    this.control.currentEntry = this.currentEntry;
    this.pristineEntry = angular.copy(entry);
    this.saveStatus = 'unsaved';
  }

  private alignCustomFieldsInData(data: any): any {
    if (data.customFields != null) {
      for (const key in data.customFields) {
        if (data.customFields.hasOwnProperty(key)) {
          data[key] = data.customFields[key];
        }
      }
    }

    if (data.senses != null) {
      for (const sense of data.senses) {
        this.alignCustomFieldsInData(sense);
      }
    }

    if (data.examples != null) {
      for (const example of data.examples) {
        this.alignCustomFieldsInData(example);
      }
    }

    return data;
  }

  private prepCustomFieldsForUpdate(data: any): any {
    data.customFields = {};
    for (const fieldName in data) {
      if (data.hasOwnProperty(fieldName)) {
        if (/^customField_/.test(fieldName)) {
          data.customFields[fieldName] = data[fieldName];
        }

        if (fieldName === 'senses' || fieldName === 'examples') {
          data[fieldName] = this.prepCustomFieldsForUpdate(data[fieldName]);
        }
      }
    }

    return data;
  }

  editEntry(id: string): void {
    if (this.currentEntry.id !== id) {
      this.saveCurrentEntry();
      this.setCurrentEntry(this.entries[this.editorService.getIndexInList(id, this.entries)]);
      this.commentService.loadEntryComments(id);
      if (this.rightPanelVisible === true && this.commentContext.contextGuid !== '') {
        this.showComments();
        this.setCommentContext('');
      }
    }

    this.goToEntry(id);
  }

  newEntry(): void {
    this.saveCurrentEntry(false, () => {
      const d = new Date();
      const uniqueId = '_new_' + d.getSeconds() + d.getMilliseconds();
      const newEntry = new LexEntry();
      newEntry.id = uniqueId;
      this.setCurrentEntry(newEntry);
      this.commentService.loadEntryComments(newEntry.id);
      this.editorService.addEntryToEntryList(newEntry);
      this.editorService.showInitialEntries().then(() => {
        this.scrollListToEntry(newEntry.id, 'top');
      });

      this.goToEntry(newEntry.id);
      this.hideRightPanel();
    });
  }

  private goToEntry(entryId: string): void {
    if (this.$state.is('editor.entry')) {
      this.$state.go('.', { entryId }, { notify: false });
    } else {
      this.$state.go('editor.entry', { entryId });
    }
  }

  entryLoaded(): boolean {
    return this.currentEntry.id != null;
  }

  returnToList(): void {
    this.saveCurrentEntry();
    this.setCurrentEntry();
    this.$state.go('editor.list');
  }

  makeValidModelRecursive = (config: LexConfigField, data: any = {}, stopAtNodes: string | string[] = []): any => {
    if (typeof stopAtNodes === 'string') {
      stopAtNodes = [stopAtNodes];
    }

    switch (config.type) {
      case 'fields':
        const configFieldList = config as LexConfigFieldList;
        for (const fieldName of configFieldList.fieldOrder) {
          if (data[fieldName] == null) {
            if (configFieldList.fields[fieldName].type === 'fields' ||
              configFieldList.fields[fieldName].type === 'pictures'
            ) {
              data[fieldName] = [];
            } else {
              data[fieldName] = {};
            }
          }

          // only recurse if the field is not in our node stop list or if it contains data
          if (stopAtNodes.indexOf(fieldName) === -1 || data[fieldName].length !== 0) {
            if (configFieldList.fields[fieldName].type === 'fields') {
              if (data[fieldName].length === 0) {
                data[fieldName].push({});
              }

              for (let i = 0; i < data[fieldName].length; i++) {
                data[fieldName][i] =
                  this.makeValidModelRecursive(configFieldList.fields[fieldName], data[fieldName][i], stopAtNodes);
              }
            } else {
              data[fieldName] =
                this.makeValidModelRecursive(configFieldList.fields[fieldName], data[fieldName], stopAtNodes);
            }
          }
        }
        break;
      case 'multitext':
        // when a multitext is completely empty for a field, and sent down the wire, it will come as a [] because of the
        // way that the PHP JSON default encode works. We change this to be {} for an empty multitext
        if (angular.isArray(data)) {
          data = {};
        }

        for (const inputSystemTag of (config as LexConfigMultiText).inputSystems) {
          if (data[inputSystemTag] == null) {
            data[inputSystemTag] = {
              value: ''
            };
          }
        }
        break;
      case 'optionlist':
        if (data.value == null || data.value === null) {
          data.value = '';
          const configOptionList = config as LexConfigOptionList;
          if (this.config.optionlists != null && configOptionList.listCode != null &&
            (configOptionList.listCode in this.config.optionlists) &&
            this.config.optionlists[configOptionList.listCode].defaultItemKey != null
          ) {
            data.value = this.config.optionlists[configOptionList.listCode].defaultItemKey;
          }
        }

        break;
      case 'multioptionlist':
        if (data.values == null) {
          data.values = [];
        }

        break;
      case 'pictures':
        const captionConfig = angular.copy(config);
        captionConfig.type = 'multitext';
        if (data == null) {
          data = [];
        }

        for (const picture of data as LexPicture[]) {
          if (picture.caption == null) {
            picture.caption = {};
          }

          picture.caption = this.makeValidModelRecursive(captionConfig, picture.caption);
        }
        break;
      case 'multiparagraph':
        if (data.type == null) {
          data.type = 'multiparagraph';
        }

        if (data.paragraphsHtml == null) {
          data.paragraphsHtml = '';
        }

        break;
    }

    // console.log('end data: ', data);
    return data;
  }

  deleteEntry = (entry: LexEntry): void => {
    const deleteMsg = 'Are you sure you want to delete the entry <b>\'' +
      LexiconUtilityService.getLexeme(this.config, this.config.entry, entry) + '\'</b>';
    this.modal.showModalSimple('Delete Entry', deleteMsg, 'Cancel', 'Delete Entry').then(() => {
      let iShowList = this.editorService.getIndexInList(entry.id, this.visibleEntries);
      this.editorService.removeEntryFromLists(entry.id);
      if (this.entries.length > 0) {
        if (iShowList !== 0) {
          iShowList--;
        }
        this.setCurrentEntry(this.visibleEntries[iShowList]);
        this.$state.go('.', { entryId: this.visibleEntries[iShowList].id }, { notify: false });
      } else {
        this.returnToList();
      }

      if (!LexiconEditorController.entryIsNew(entry)) {
        this.sendReceive.setStateUnsynced();
        this.lexService.remove(entry.id, () => {
          this.editorService.refreshEditorData();
        });
      }

      this.hideRightPanel();
    }, () => {});
  }

  getCompactItemListOverlay(entry: LexEntry): string {
    let title;
    let subtitle;
    title = this.getWordForDisplay(entry);
    subtitle = this.getMeaningForDisplay(entry);
    if (title.length > 19 || subtitle.length > 25) {
      return title + '         ' + subtitle;
    } else {
      return '';
    }
  }

  private evaluateState(): void {
    this.editorService.loadEditorData().then(() => {
      // if entry not found go to first visible entry
      let entryId = this.$state.params.entryId;
      if (this.editorService.getIndexInList(entryId, this.entries) == null) {
        entryId = '';
        if (this.visibleEntries[0] != null) {
          entryId = this.visibleEntries[0].id;
        }
      }

      if (this.$state.is('editor.entry')) {
        this.editEntryAndScroll(entryId);
      }
    });
  }

    // Comments View
  showComments(): void {
    if (this.rightPanelVisible === true && this.commentContext.contextGuid === '') {
      this.showCommentsPanel();

      // Reset the comment context AFTER the panel starts hiding
      this.setCommentContext('');
    } else {
      // Reset the comment context BEFORE we start showing the panel
      this.setCommentContext('');
      const commentsRightPanel = document.querySelector('.comments-right-panel') as HTMLElement;
      commentsRightPanel.style.paddingTop = '0';
      if (this.rightPanelVisible === false) {
        this.showCommentsPanel();
      }
    }
  }

  showCommentsPanel = (): void => {
    this.showRightPanel('#lexAppCommentView');
  }

  showActivityFeed = (): void => {
    this.showRightPanel('#lexAppActivityFeed');
  }

  showRightPanel(element: string): void {
    const currentElement = document.querySelector(element);
    if (this.rightPanelVisible === false) {
      this.rightPanelVisible = true;
      this.control.rightPanelVisible = this.rightPanelVisible;
      currentElement.classList.add('panel-visible');
    } else if (this.rightPanelVisible === true) {
      if (currentElement.classList.contains('panel-visible')) {
        this.hideRightPanel();
      } else {
        const visibleElement = document.querySelector('.panel-visible');
        visibleElement.classList.remove('panel-visible');
        visibleElement.classList.add('panel-closing', 'panel-switch');
        currentElement.classList.add('panel-visible');
        this.$interval(() => {
          document.querySelector('.panel-closing').classList.remove('panel-closing', 'panel-switch');
        }, 500, 1);
      }
    }
  }

  hideRightPanel = (): void => {
    if (this.rightPanelVisible === true) {
      this.rightPanelVisible = null;
      this.control.rightPanelVisible = this.rightPanelVisible;

      // Delay relates to the CSS timer for mobile vs > tablet
      const delay = (screen.availWidth >= 768) ? 1500 : 500;
      const visibleElement = document.querySelector('.panel-visible');
      visibleElement.classList.add('panel-closing');
      visibleElement.classList.remove('panel-visible');
      this.$interval(() => {
        const closingPanels = document.querySelectorAll('.panel-closing');
        for (const index in closingPanels) {
          if (typeof (closingPanels[index]) === 'object') {
            closingPanels[index].classList.remove('panel-closing');
          }
        }

        this.rightPanelVisible = false;
        this.control.rightPanelVisible = this.rightPanelVisible;
        this.setCommentContext('');
      }, delay, 1);
    }
  }

  private pollUpdateSuccess = (): void => {
    if (this.currentEntryIsDirty()) {
      if (this.sendReceive.isInProgress()) {
        this.cancelAutoSaveTimer();
        this.warnOfUnsavedEdits(this.currentEntry);
        this.resetEntryLists(this.currentEntry.id, angular.copy(this.pristineEntry));
      }
    } else {
      this.setCurrentEntry(this.entries[this.editorService.getIndexInList(this.currentEntry.id, this.entries)]);
    }
  }

  private syncProjectStatusSuccess = (): void => {
    this.editorService.refreshEditorData().then(() => {
      this.setCurrentEntry(this.entries[this.editorService.getIndexInList(this.currentEntry.id, this.entries)]);
      this.sessionService.getSession(true).then(this.configService.refresh);
      this.notice.removeById(this.warnOfUnsavedEditsId);
    });
  }

  private startAutoSaveTimer(): void {
    if (this.autoSaveTimer != null) {
      return;
    }

    this.autoSaveTimer = this.$interval(() => {
      this.saveCurrentEntry(true);
    }, 5000, 1);
  }

  private cancelAutoSaveTimer(): void {
    if (this.autoSaveTimer != null) {
      this.$interval.cancel(this.autoSaveTimer);
      this.autoSaveTimer = undefined;
    }
  }

  resetEntryListFilter(): void {
    this.entryListModifiers.filterBy = null;
    this.filterEntries(true);
  }

  private getInputSystemAbbreviation(inputSystemTag: string): string {
    if (this.config == null || this.config.inputSystems == null || !(inputSystemTag in this.config.inputSystems)) {
      return inputSystemTag;
    }

    return this.config.inputSystems[inputSystemTag].abbreviation;
  }

  private setSortAndFilterOptionsFromConfig(): void {
    if (this.config == null) {
      return;
    }

    const sortOptions: SortOption[] = [];
    const filterOptions: FilterOption[] = [];
    for (const entryFieldName of this.config.entry.fieldOrder) {
      const entryField = this.config.entry.fields[entryFieldName];

      // TODO: do I need to check if user can see field (view settings).
      // Is this handled somewhere else? - cjh 2017-07-20
      if (entryField.hideIfEmpty) {
        return;
      }
      if (entryFieldName === 'senses') {
        const configSenses = this.config.entry.fields.senses as LexConfigFieldList;
        for (const senseFieldName of configSenses.fieldOrder) {
          const senseField = configSenses.fields[senseFieldName];
          if (senseField.hideIfEmpty || senseField.type === 'fields') {
            return;
          }
          sortOptions.push({ label: senseField.label, value: senseFieldName });
          if (senseField.type === 'multitext') {
            for (const inputSystemTag of (senseField as LexConfigMultiText).inputSystems) {
              const abbreviation = this.getInputSystemAbbreviation(inputSystemTag);
              filterOptions.push({ label: senseField.label + ' [' + abbreviation + ']',
                level: 'sense', value: senseFieldName, type: 'multitext',
                inputSystem: inputSystemTag, key: senseFieldName + '-' + inputSystemTag });
            }
          } else {
            filterOptions.push({ label: senseField.label, level: 'sense', value: senseFieldName,
              type: senseField.type, key: senseFieldName });
          }
        }
      } else {
        sortOptions.push({ label: entryField.label, value: entryFieldName });
        if (entryField.type === 'multitext') {
          for (const inputSystemTag of (entryField as LexConfigMultiText).inputSystems) {
            const abbreviation = this.getInputSystemAbbreviation(inputSystemTag);
            filterOptions.push({ label: entryField.label + ' [' + abbreviation + ']',
              level: 'entry', value: entryFieldName, type: 'multitext',
              inputSystem: inputSystemTag, key: entryFieldName + '-' + inputSystemTag });
          }
        } else {
          filterOptions.push({ label: entryField.label, level: 'entry', value: entryFieldName,
            type: entryField.type, key: entryFieldName });
        }
      }
    }

    filterOptions.push({ label: 'Comments', value: 'comments', type: 'comments', key: 'comments' });
    filterOptions.push({ label: 'Example Sentences', value: 'exampleSentences', type: 'exampleSentences',
      key: 'exampleSentences' });
    filterOptions.push({ label: 'Pictures', value: 'pictures', type: 'pictures', key: 'pictures' });
    let hasAudioInputSystem = false;
    for (const inputSystemsTag in this.config.inputSystems) {
      if (this.config.inputSystems.hasOwnProperty(inputSystemsTag) && LexiconUtilityService.isAudio(inputSystemsTag)) {
        hasAudioInputSystem = true;
        break;
      }
    }

    if (hasAudioInputSystem) {
      filterOptions.push({ label: 'Audio', value: 'audio', type: 'audio', key: 'audio' });
    }

    LexiconUtilityService.arrayCopyRetainingReferences(sortOptions, this.entryListModifiers.sortOptions);
    LexiconUtilityService.arrayCopyRetainingReferences(filterOptions, this.entryListModifiers.filterOptions);
  }

  private recursiveRemoveProperties(startAt: any, properties: string[]): any {
    for (const fieldName in startAt) {
      if (startAt.hasOwnProperty(fieldName)) {
        let isPropertyDeleted = false;
        for (const property of properties) {
          if (fieldName === property) {
            delete startAt[fieldName];
            isPropertyDeleted = true;
            break;
          }
        }

        if (!isPropertyDeleted && angular.isObject(startAt[fieldName])) {
          this.recursiveRemoveProperties(startAt[fieldName], properties);
        }
      }
    }

    return startAt;
  }

  private setupTypeAheadSearch(): void {
    this.typeahead = {
      searchItemSelected: '',
      searchResults: [],
      limit: 50,
      matchCountCaption: ''
    };

    this.typeahead.searchEntries = (query = '') => {
      const blacklistKeys = [
        'isDeleted',
        'id',
        'guid',
        'translationGuid',
        '$$hashKey',
        'dateModified',
        'dateCreated',
        'projectId',
        'authorInfo',
        'fileName'
      ];

      const isBlacklisted = (key: string): boolean => {
        const audio = '-audio';
        return blacklistKeys.includes(key) || key.includes(audio, key.length - audio.length);
      };

      // TODO consider whitelisting all properties under customFields

      const isMatch = (value: any): boolean => {
        // toUpperCase is better than toLowerCase, but still has issues,
        // e.g. 'ß'.toUpperCase() === 'SS'
        const queryCapital = query.toUpperCase();
        switch (value == null ? 'null' : typeof value) {
          // Array.prototype.some tests whether some element satisfies the function
          case 'object':
            return Object.keys(value).some(key => !isBlacklisted(key) && isMatch(value[key]));
          case 'string':
            return value.toUpperCase().includes(queryCapital);
          case 'null':
            return false;
          case 'boolean':
            return false;
          default:
            console.error('Unexpected type ' + (typeof value) + ' on entry.');
            return false;
        }
      };
      const filteredEntries = this.filteredEntries.filter(isMatch);

      const prioritizedEntries = {
        wordBeginning: [] as LexEntry[],
        word: [] as LexEntry[],
        meaningBeginning: [] as LexEntry[],
        meaning: [] as LexEntry[],
        everythingElse: [] as LexEntry[]
      };

      for (const entry of filteredEntries) {
        const word = this.getPrimaryListItemForDisplay(this.config, entry);
        const meaning = this.getMeaningForDisplay(entry);
        if (word.startsWith(query)) {
          prioritizedEntries.wordBeginning.push(entry);
        } else if (word.includes(query)) {
          prioritizedEntries.word.push(entry);
        } else if (meaning.startsWith(query)) {
          prioritizedEntries.meaningBeginning.push(entry);
        } else if (meaning.includes(query)) {
          prioritizedEntries.meaning.push(entry);
        } else {
          prioritizedEntries.everythingElse.push(entry);
        }
      }

      this.typeahead.searchResults = [].concat(
        prioritizedEntries.wordBeginning,
        prioritizedEntries.word,
        prioritizedEntries.meaningBeginning,
        prioritizedEntries.meaning,
        prioritizedEntries.everythingElse
      );
      this.typeahead.matchCountCaption = '';
      const numMatches = this.typeahead.searchResults.length;
      if (numMatches > this.typeahead.limit) {
        this.typeahead.matchCountCaption = this.typeahead.limit + ' of ' + numMatches + ' matches';
      } else if (numMatches > 1) {
        this.typeahead.matchCountCaption = numMatches + ' matches';
      } else if (numMatches === 1) {
        this.typeahead.matchCountCaption = numMatches + ' match';
      }
    };

    this.typeahead.searchSelect = (entry: LexEntry) => {
      this.typeahead.searchItemSelected = '';
      this.typeahead.searchResults = [];
      if (entry.id) {
        this.editEntryAndScroll(entry.id);
      }
    };
  }

  setCommentContext = (contextGuid: string): void => {
    this.commentContext.contextGuid = contextGuid;
  }

  getContextParts = (contextGuid: string) => {
    const parts = {
      value: '',
      option: { key: '', label: '' },
      field: '',
      fieldConfig: {},
      inputSystem: '',
      sense: { index: '', guid: '' },
      example: { index: '', guid: '' }
    };
    if (contextGuid == null || this.config == null) {
      return parts;
    }

    const contextParts = contextGuid.split(/(sense#.+?\s)|(example#.+?\s)/);
    let exampleGuid = '';
    let senseGuid = '';
    let field = '';
    let fieldConfig: LexConfig = new LexConfig();
    let inputSystem = '';
    let optionKey = '';
    let optionLabel = '';
    let senseIndex = null;
    let exampleIndex = null;
    const currentEntry = this.currentEntry;
    let currentValue = '';
    let currentField = null;
    let contextPart = '';

    for (const i in contextParts) {
      if (contextParts.hasOwnProperty(i) && contextParts[i] != null && contextParts[i] !== '') {
        contextPart = contextParts[i].trim();
        if (contextPart.includes('sense#')) {
          senseGuid = contextPart.substr(6);
        } else if (contextPart.includes('example#')) {
          exampleGuid = contextPart.substr(8);
        } else if (contextPart.includes('#')) {
          field = contextPart.substr(0, contextPart.indexOf('#'));
          optionKey = contextPart.substr(contextPart.indexOf('#') + 1);
          if (optionKey.includes('.')) {
            inputSystem = optionKey.substr(optionKey.indexOf('.') + 1);
          }
        } else if (contextPart.includes('.')) {
          field = contextPart.substr(0, contextPart.indexOf('.'));
          inputSystem = contextPart.substr(contextPart.indexOf('.') + 1);
        } else {
          field = contextPart;
        }
      }
    }

    if (senseGuid) {
      for (const a in currentEntry.senses) {
        if (currentEntry.senses.hasOwnProperty(a) && currentEntry.senses[a].guid === senseGuid) {
          senseIndex = a;
          if (exampleGuid) {
            for (const b in currentEntry.senses[a].examples) {
              if (currentEntry.senses[a].examples.hasOwnProperty(b) &&
                currentEntry.senses[a].examples[b].guid === exampleGuid
              ) {
                exampleIndex = b;
              }
            }
          }
        }
      }
    }

    const senses = this.config.entry.fields.senses as LexConfigFieldList;
    const examples = senses.fields.examples as LexConfigFieldList;
    if (exampleGuid && exampleIndex) {
      if (currentEntry.senses[senseIndex].examples[exampleIndex].hasOwnProperty(field)) {
        currentField = currentEntry.senses[senseIndex].examples[exampleIndex][field];
        if (examples.fields.hasOwnProperty(field)) {
          fieldConfig = examples.fields[field];
        }
      }
    } else if (senseGuid && senseIndex) {
      if (currentEntry.senses[senseIndex].hasOwnProperty(field)) {
        currentField = currentEntry.senses[senseIndex][field];
        if (senses.fields.hasOwnProperty(field)) {
          fieldConfig = senses.fields[field];
        }
      }
    } else if (currentEntry.hasOwnProperty(field)) {
      currentField = currentEntry[field];
      if (this.config.entry.fields.hasOwnProperty(field)) {
        fieldConfig = this.config.entry.fields[field];
      }
    }

    if (currentField !== null) {
      if (currentField.hasOwnProperty(inputSystem)) {
        currentValue = currentField[inputSystem].value;
      } else if (currentField.hasOwnProperty('value')) {
        currentValue = currentField.value;
      } else {
        currentValue = optionKey;
      }

      // Option lists only get their key saved on the comment so we need to find the value
      if (fieldConfig !== null &&
        (fieldConfig.type === 'multioptionlist' || fieldConfig.type === 'optionlist')
        ) {
        if (field === 'semanticDomain') {
          // Semantic domains are in the global scope and appear to be English only
          // Will need to be updated once the system provides support for other languages
          for (const i in this.$window.semanticDomains_en) {
            if (this.$window.semanticDomains_en.hasOwnProperty(i) &&
              this.$window.semanticDomains_en[i].key === optionKey
            ) {
              optionLabel = this.$window.semanticDomains_en[i].value;
            }
          }
        } else {
          const optionlists = this.config.optionlists;
          for (const listCode in optionlists) {
            if (optionlists.hasOwnProperty(listCode) && listCode === (fieldConfig as LexConfigOptionList).listCode) {
              for (const i in optionlists[listCode].items) {
                if (optionlists[listCode].items.hasOwnProperty(i)) {
                  const item = optionlists[listCode].items[i];
                  if (
                    (item.key === optionKey && fieldConfig.type === 'multioptionlist') ||
                    (item.key === currentValue && fieldConfig.type === 'optionlist')
                  ) {
                    optionKey = item.key;
                    optionLabel = item.value;
                  }
                }
              }
            }
          }
        }
      }
    }

    parts.value = currentValue;
    parts.option.key = optionKey;
    parts.option.label = optionLabel;
    parts.field = field;
    parts.fieldConfig = fieldConfig;
    parts.inputSystem = inputSystem;
    parts.sense.index = senseIndex;
    parts.sense.guid = senseGuid;
    parts.example.index = exampleIndex;
    parts.example.guid = exampleGuid;
    return parts;
  }

}

export class LexiconEditorListController implements angular.IController {
  static $inject = ['lexProjectService'];
  constructor(private readonly lexProjectService: LexiconProjectService) {}

  $onInit(): void {
    this.lexProjectService.setBreadcrumbs('editor/list', 'List');
    this.lexProjectService.setupSettings();
  }

}

export class LexiconEditorEntryController implements angular.IController {
  static $inject = ['lexProjectService'];
  constructor(private readonly lexProjectService: LexiconProjectService) {}

  $onInit(): void {
    this.lexProjectService.setBreadcrumbs('editor/entry', 'Edit');
    this.lexProjectService.setupSettings();
  }

}
