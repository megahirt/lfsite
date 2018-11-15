import {
  AttributeFilterSpecifier,
  AttributeSortSpecifier,
  FilterSpecifier,
  FindRecord,
  FindRecords,
  FindRelatedRecord,
  FindRelatedRecords,
  OffsetLimitPageSpecifier,
  PageSpecifier,
  Query,
  QueryExpressionParseError,
  RelatedRecordFilterSpecifier,
  RelatedRecordsFilterSpecifier,
  SortSpecifier
} from '@orbit/data';
import JSONAPISource, { JSONAPIDocument } from '@orbit/jsonapi';
import { merge } from '@orbit/utils';

import { CustomFilterSpecifier } from '../custom-filter-specifier';
import { IndexedPageSpecifier } from '../indexed-page-specifier';
import { buildFetchSettings, customRequestOptions, Filter, RequestOptions } from './request-settings';

export const GetOperators = {
  findRecord(source: JSONAPISource, query: Query): Promise<JSONAPIDocument> {
    const expression = query.expression as FindRecord;
    const { record } = expression;

    const requestOptions = customRequestOptions(source, query);
    const settings = buildFetchSettings(requestOptions);

    return source.fetch(source.resourceURL(record.type, record.id), settings);
  },

  findRecords(source: JSONAPISource, query: Query): Promise<JSONAPIDocument> {
    const expression = query.expression as FindRecords;
    const { type } = expression;

    const requestOptions: RequestOptions = {};

    if (expression.filter) {
      requestOptions.filter = buildFilterParam(source, expression.filter);
    }

    if (expression.sort) {
      requestOptions.sort = buildSortParam(source, expression.sort);
    }

    if (expression.page) {
      requestOptions.page = buildPageParam(expression.page);
    }

    const customOptions = customRequestOptions(source, query);
    if (customOptions) {
      merge(requestOptions, customOptions);
    }

    const settings = buildFetchSettings(requestOptions);

    return source.fetch(source.resourceURL(type), settings);
  },

  findRelatedRecord(source: JSONAPISource, query: Query): Promise<JSONAPIDocument> {
    const expression = query.expression as FindRelatedRecord;
    const { record, relationship } = expression;

    const requestOptions = customRequestOptions(source, query);
    const settings = buildFetchSettings(requestOptions);

    return source.fetch(source.relatedResourceURL(record.type, record.id, relationship), settings);
  },

  findRelatedRecords(source: JSONAPISource, query: Query): Promise<JSONAPIDocument> {
    const expression = query.expression as FindRelatedRecords;
    const { record, relationship } = expression;

    const requestOptions = customRequestOptions(source, query);

    const settings = buildFetchSettings(requestOptions);

    return source.fetch(source.relatedResourceURL(record.type, record.id, relationship), settings);
  }
};

function buildFilterParam(source: JSONAPISource, filterSpecifiers: FilterSpecifier[]): Filter[] {
  const filters: Filter[] = [];

  filterSpecifiers.forEach(filterSpecifier => {
    if (filterSpecifier.kind === 'attribute') {
      const attributeFilter = filterSpecifier as AttributeFilterSpecifier;

      // Note: We don't know the `type` of the attribute here, so passing `null`
      const resourceAttribute = source.serializer.resourceAttribute(null, attributeFilter.attribute);
      let prefix = '';
      switch (attributeFilter.op) {
        case 'equal':
          prefix = 'eq';
          break;
        case 'gt':
          prefix = 'gt';
          break;
        case 'lt':
          prefix = 'lt';
          break;
        case 'gte':
          prefix = 'ge';
          break;
        case 'lte':
          prefix = 'le';
          break;
      }
      filters.push({ [resourceAttribute]: prefix + ':' + attributeFilter.value });
    } else if (filterSpecifier.kind === 'relatedRecord') {
      const relatedRecordFilter = filterSpecifier as RelatedRecordFilterSpecifier;
      if (Array.isArray(relatedRecordFilter.record)) {
        filters.push({ [relatedRecordFilter.relation]: relatedRecordFilter.record.map(e => e.id).join(',') });
      } else {
        filters.push({ [relatedRecordFilter.relation]: relatedRecordFilter.record.id });
      }
    } else if (filterSpecifier.kind === 'relatedRecords') {
      if (filterSpecifier.op !== 'equal') {
        throw new Error(`Operation "${filterSpecifier.op}" is not supported in JSONAPI for relatedRecords filtering`);
      }
      const relatedRecordsFilter = filterSpecifier as RelatedRecordsFilterSpecifier;
      filters.push({ [relatedRecordsFilter.relation]: relatedRecordsFilter.records.map(e => e.id).join(',') });
    } else if (filterSpecifier.kind === 'custom') {
      const customFilter = filterSpecifier as CustomFilterSpecifier;

      const filterName = source.serializer.resourceAttribute(null, customFilter.name);
      filters.push({ [filterName]: customFilter.value });
    } else {
      throw new QueryExpressionParseError(`Filter operation ${filterSpecifier.op} not recognized for JSONAPISource.`,
        filterSpecifier);
    }
  });

  return filters;
}

function buildSortParam(source: JSONAPISource, sortSpecifiers: SortSpecifier[]): string {
  return sortSpecifiers.map(sortSpecifier => {
    if (sortSpecifier.kind === 'attribute') {
      const attributeSort = sortSpecifier as AttributeSortSpecifier;

      // Note: We don't know the `type` of the attribute here, so passing `null`
      const resourceAttribute = source.serializer.resourceAttribute(null, attributeSort.attribute);
      return (sortSpecifier.order === 'descending' ? '-' : '') + resourceAttribute;
    }
    throw new QueryExpressionParseError(`Sort specifier ${sortSpecifier.kind} not recognized for JSONAPISource.`,
      sortSpecifier);
  }).join(',');
}

function buildPageParam(pageSpecifier: PageSpecifier): any {
  const param = { };
  switch (pageSpecifier.kind) {
    case 'offsetLimit':
      const offsetLimit = pageSpecifier as OffsetLimitPageSpecifier;
      param['offset'] = offsetLimit.offset;
      param['limit'] = offsetLimit.limit;
      break;
    case 'indexed':
      const indexed = pageSpecifier as IndexedPageSpecifier;
      param['number'] = indexed.index;
      param['size'] = indexed.size;
      break;
  }
  return param;
}
