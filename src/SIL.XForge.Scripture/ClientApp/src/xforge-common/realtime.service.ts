import { Injectable } from '@angular/core';
import { RecordIdentity } from '@orbit/data';
import { underscore } from '@orbit/utils';
import * as localforage from 'localforage';
import ReconnectingWebSocket from 'reconnecting-websocket';
import * as RichText from 'rich-text';
import { Connection, types } from 'sharedb/lib/client';

import { environment } from '../environments/environment';
import { LocationService } from './location.service';
import { DomainModel } from './models/domain-model';
import { RealtimeData } from './models/realtime-data';
import { SharedbRealtimeDoc } from './realtime-doc';
import { RealtimeOfflineStore } from './realtime-offline-store';

types.register(RichText.type);

@Injectable({
  providedIn: 'root'
})
export class RealtimeService {
  private readonly ws: ReconnectingWebSocket;
  private readonly connection: Connection;
  private readonly connectedData = new Map<RecordIdentity, Promise<any>>();
  private readonly stores = new Map<string, RealtimeOfflineStore>();

  constructor(private readonly domainModel: DomainModel, private readonly locationService: LocationService) {
    const protocol = this.locationService.protocol === 'https:' ? 'wss:' : 'ws:';
    let url = `${protocol}//${this.locationService.hostname}`;
    if ('realtimePort' in environment && environment.realtimePort != null && environment.realtimePort !== 0) {
      url += `:${environment.realtimePort}`;
    }
    url += environment.realtimeUrl;

    this.ws = new ReconnectingWebSocket(url);
    this.connection = new Connection(this.ws);
  }

  connect<T extends RealtimeData>(identity: RecordIdentity): Promise<T> {
    if (!this.connectedData.has(identity)) {
      const sharedbDoc = this.connection.get(underscore(identity.type) + '_data', identity.id);
      const store = this.getStore(identity.type);
      const RealtimeDocType = this.domainModel.getRealtimeDataType(identity.type);
      const realtimeDoc = new RealtimeDocType(new SharedbRealtimeDoc(sharedbDoc), store);
      this.connectedData.set(
        identity,
        new Promise<any>((resolve, reject) => {
          realtimeDoc.subscribe().then(() => resolve(realtimeDoc), err => reject(err));
        })
      );
    }

    return this.connectedData.get(identity);
  }

  disconnect(data: RealtimeData): Promise<void> {
    this.connectedData.delete(data);
    return data.dispose();
  }

  private getStore(type: string): RealtimeOfflineStore {
    if (!this.stores.has(type)) {
      this.stores.set(
        type,
        new RealtimeOfflineStore(localforage.createInstance({ name: 'xforge-realtime', storeName: type }))
      );
    }
    return this.stores.get(type);
  }
}
