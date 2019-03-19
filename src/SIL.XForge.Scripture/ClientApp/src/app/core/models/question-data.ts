import { JsonData } from 'xforge-common/models/json-data';
import { RealtimeDoc } from 'xforge-common/realtime-doc';
import { RealtimeOfflineStore } from 'xforge-common/realtime-offline-store';
import { Answer } from './answer';
import { Question } from './question';

export class QuestionData extends JsonData<Question, Answer> {
  static readonly TYPE = 'question';

  constructor(doc: RealtimeDoc, store: RealtimeOfflineStore) {
    super(QuestionData.TYPE, doc, store);
  }
}