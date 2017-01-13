import { Component, OnInit, Output, EventEmitter, ViewChild} from '@angular/core';
import { NameListService } from '../shared/name-list/name-list.service';
import { SemanticDomainListService } from '../shared/main-view/main-view.service';
import { WordDetailsComponent } from '../word-details/word-details.component';

@Component({
  moduleId: module.id,
  selector: 'sd-home',
  templateUrl: 'home.component.html',
  styleUrls: ['home.component.css'],
})
export class HomeComponent implements OnInit {
  semanticDomains: any[] = [];
  words: any[] = [];
  numberOfEntries: number = 0;
  @ViewChild(WordDetailsComponent)
  private detailToggle: WordDetailsComponent;

  /**
   * Creates an instance of the HomeComponent with the injected
   * SemanticDomainListService.
   *
   * 
   * @param {SemanticDomainListService} semanticDomainListService
   */

  constructor(public semanticDomainListService: SemanticDomainListService) { }
  multitextShowDetails(){
    this.detailToggle.toggleShowDetails();
  }
  ngOnInit() {
    this.getSemanticDomains();
    this.getWords();
    this.getNumberOfEntries();
  }
  getNumberOfEntries() {
    this.numberOfEntries = this.words.length;
  }
  getWords() {
    this.words = ["Nysha", "grape", "carrot", "dragon eye", "jicama"];
  }
  getSemanticDomains() {
    this.semanticDomainListService.get()
      .subscribe(
      semanticDomains => this.semanticDomains = semanticDomains,
    );
  }
}