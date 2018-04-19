import Quill, { TextChangeHandler } from 'quill';

import { SaveState } from './constants';

type DocCallback = (error: any) => void;
type OnOpsFunction = (op: any, source: any) => void;

export declare class RealTimeDoc {
  type: string;
  id: string;
  data: any;
  fetch(callback: DocCallback): void;
  subscribe(callback: DocCallback): void;
  ingestSnapshot(snapshot: any, callback: DocCallback): void;
  destroy(): void;
  on(eventName: string, onOpsFunction: OnOpsFunction): void;
  create(data: any[], type?: string, options?: any, callback?: DocCallback): void;
  submitOp(op: any, options?: any, callback?: DocCallback): void;
  del(options?: any, callback?: DocCallback): void;
  whenNothingPending(callback: DocCallback): void;
  removeListener(eventName: string, onOpsFunction: OnOpsFunction): void;
}

export class RealTimeService {
  private static readonly ShareDB = require('sharedb/lib/client');
  private static readonly richText = require('rich-text');

  private readonly socket: WebSocket;
  private readonly connection: any;

  private docSubs: { [id: string]: RealTimeDoc } = {};
  private onTextChanges: { [id: string]: TextChangeHandler } = {};
  private onOps: { [id: string]: OnOpsFunction } = {};

  private pendingOpCount: { [id: string]: number } = {};

  static $inject = ['$window'];
  constructor(private $window: angular.IWindowService) {
    RealTimeService.ShareDB.types.register(RealTimeService.richText.type);
    // Open WebSocket connection to ShareDB server
    this.socket = new WebSocket(this.getWebSocketDocUrl());
    this.connection = new RealTimeService.ShareDB.Connection(this.socket);
  }

  getSaveState(id: string): SaveState {
    if (!(id in this.pendingOpCount)) {
      return SaveState.Unedited;
    } else if (this.pendingOpCount[id] > 0) {
      return SaveState.Saving;
    } else {
      return SaveState.Saved;
    }
  }

  createAndSubscribeRichTextDoc(collection: string, id: string, quill: Quill) {
    let doc: RealTimeDoc;
    if (id in this.docSubs) {
      doc = this.docSubs[id];
      this.disconnectRichTextDoc(id, quill);
    } else {
      doc = this.connection.get(collection, id);
      doc.fetch(err => {
        if (err) throw err;

        if (doc.type === null) {
          doc.create([{ insert: '' }], RealTimeService.richText.type.name);
        }
      });
    }

    this.docSubs[id] = doc;
    doc.subscribe(err => {
      if (err) throw err;

      quill.setContents(doc.data);
      quill.getModule('history').clear();

      this.onTextChanges[id] = (delta: any, oldDelta: any, source: any) => {
        if (source !== Quill.sources.USER) return;
        if (!(id in this.pendingOpCount)) {
          this.pendingOpCount[id] = 0;
        }
        this.pendingOpCount[id]++;
        doc.submitOp(delta, {source: quill}, error => this.pendingOpCount[id]--);

        // console.log('onTextChange: docId', id, 'data', quill.getText());
      };

      quill.on(Quill.events.TEXT_CHANGE, this.onTextChanges[id]);

      this.onOps[id] = (op: any, source: any) => {
        if (source === quill) return;
        quill.updateContents(op);

        // console.log('onOp: docId', id, 'data', quill.getText());
      };

      doc.on('op', this.onOps[id]);
    });
  }

  updateRichTextDoc(collection: string, id: string, delta: any, source: any) {
    let doc: RealTimeDoc;
    if (id in this.docSubs) {
      doc = this.docSubs[id];
      doc.submitOp(delta, { source });
    } else {
      doc = this.connection.get(collection, id);
      doc.fetch(err => {
        if (err) throw err;

        if (doc.type === null) {
          doc.create([{ insert: '' }], RealTimeService.richText.type.name);
        } else {
          doc.submitOp(delta, { source });
        }
      });
    }
  }

  disconnectRichTextDoc(id: string, quill: Quill) {
    if (id in this.onTextChanges) {
      quill.off(Quill.events.TEXT_CHANGE, this.onTextChanges[id]);
      delete this.onTextChanges[id];
    }

    if (id in this.docSubs) {
      if (id in this.onOps) {
        this.docSubs[id].removeListener('op', this.onOps[id]);
        delete this.onOps[id];
      }

      this.docSubs[id].destroy();
      delete this.docSubs[id];

      delete this.pendingOpCount[id];
    }
    // Quill seems to load a doc faster if the editor is empty, but we don't want to fire any events, which can mess
    // up DocumentEditor state
    quill.setText('', Quill.sources.SILENT);
  }

  private getWebSocketDocUrl() {
    let protocol: string;
    switch (this.$window.location.protocol) {
      case 'http:':
        protocol = 'ws';
        break;
      case 'https:':
        protocol = 'wss';
        break;
    }
    return protocol + '://' + this.$window.location.host + '/sharedb/';
  }

}
