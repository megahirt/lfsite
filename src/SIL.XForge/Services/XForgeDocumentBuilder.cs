using System;
using System.Collections;
using System.Collections.Generic;
using System.Linq;
using JsonApiDotNetCore.Builders;
using JsonApiDotNetCore.Extensions;
using JsonApiDotNetCore.Internal;
using JsonApiDotNetCore.Models;
using JsonApiDotNetCore.Services;

namespace SIL.XForge.Services
{
    public class XForgeDocumentBuilder : IDocumentBuilder
    {
        private readonly IJsonApiContext _jsonApiContext;
        private readonly IContextGraph _contextGraph;
        private readonly IRequestMeta _requestMeta;
        private readonly DocumentBuilderOptions _documentBuilderOptions;
        private readonly IScopedServiceProvider _scopedServiceProvider;

        public XForgeDocumentBuilder(
            IJsonApiContext jsonApiContext, 
            IRequestMeta requestMeta = null, 
            IDocumentBuilderOptionsProvider documentBuilderOptionsProvider = null,
            IScopedServiceProvider scopedServiceProvider = null)
        {
            _jsonApiContext = jsonApiContext;
            _contextGraph = jsonApiContext.ContextGraph;
            _requestMeta = requestMeta;
            _documentBuilderOptions = documentBuilderOptionsProvider?.GetDocumentBuilderOptions() ?? new DocumentBuilderOptions();
            _scopedServiceProvider = scopedServiceProvider;
        }

        public Document Build(IIdentifiable entity)
        {
            var contextEntity = _contextGraph.GetContextEntity(entity.GetType());

            var resourceDefinition = _scopedServiceProvider?.GetService(contextEntity.ResourceType) as IResourceDefinition;
            var document = new Document
            {
                Data = GetData(contextEntity, entity, resourceDefinition),
                Meta = GetMeta(entity)
            };

            if (ShouldIncludePageLinks(contextEntity))
                document.Links = _jsonApiContext.PageManager.GetPageLinks(new LinkBuilder(_jsonApiContext));

            document.Included = AppendIncludedObject(document.Included, contextEntity, entity);

            return document;
        }

        public Documents Build(IEnumerable<IIdentifiable> entities)
        {
            var entityType = GetElementType(entities);
            var contextEntity = _contextGraph.GetContextEntity(entityType);
            var resourceDefinition = _scopedServiceProvider?.GetService(contextEntity.ResourceType) as IResourceDefinition;

            var enumeratedEntities = entities as IList<IIdentifiable> ?? entities.ToList();
            var documents = new Documents
            {
                Data = new List<DocumentData>(),
                Meta = GetMeta(enumeratedEntities.FirstOrDefault())
            };

            if (ShouldIncludePageLinks(contextEntity))
                documents.Links = _jsonApiContext.PageManager.GetPageLinks(new LinkBuilder(_jsonApiContext));

            foreach (var entity in enumeratedEntities)
            {
                documents.Data.Add(GetData(contextEntity, entity, resourceDefinition));
                documents.Included = AppendIncludedObject(documents.Included, contextEntity, entity);
            }

            return documents;
        }

        private Dictionary<string, object> GetMeta(IIdentifiable entity)
        {
            var builder = _jsonApiContext.MetaBuilder;
            if (_jsonApiContext.Options.IncludeTotalRecordCount && _jsonApiContext.PageManager.TotalRecords != null)
                builder.Add("total-records", _jsonApiContext.PageManager.TotalRecords);

            if (_requestMeta != null)
                builder.Add(_requestMeta.GetMeta());

            if (entity != null && entity is IHasMeta metaEntity)
                builder.Add(metaEntity.GetMeta(_jsonApiContext));

            var meta = builder.Build();
            if (meta.Count > 0)
                return meta;

            return null;
        }

        private bool ShouldIncludePageLinks(ContextEntity entity) => entity.Links.HasFlag(Link.Paging);

        private List<DocumentData> AppendIncludedObject(List<DocumentData> includedObject, ContextEntity contextEntity, IIdentifiable entity)
        {
            var includedEntities = GetIncludedEntities(includedObject, contextEntity, entity);
            if (includedEntities?.Count > 0)
            {
                includedObject = includedEntities;
            }

            return includedObject;
        }

        [Obsolete("You should specify an IResourceDefinition implementation using the GetData/3 overload.")]
        public DocumentData GetData(ContextEntity contextEntity, IIdentifiable entity)
            => GetData(contextEntity, entity, resourceDefinition: null);

        public DocumentData GetData(ContextEntity contextEntity, IIdentifiable entity, IResourceDefinition resourceDefinition = null)
        {
            var data = new DocumentData
            {
                Type = contextEntity.EntityName,
                Id = entity.StringId
            };

            if (_jsonApiContext.IsRelationshipPath)
                return data;

            data.Attributes = new Dictionary<string, object>();

            var resourceAttributes = resourceDefinition?.GetOutputAttrs(entity) ?? contextEntity.Attributes;
            resourceAttributes.ForEach(attr =>
            {
                var attributeValue = attr.GetValue(entity);
                if (ShouldIncludeAttribute(attr, attributeValue))
                {
                    data.Attributes.Add(attr.PublicAttributeName, attributeValue);
                }
            });

            if (contextEntity.Relationships.Count > 0)
                AddRelationships(data, contextEntity, entity);

            return data;
        }
        private bool ShouldIncludeAttribute(AttrAttribute attr, object attributeValue)
        {
            return OmitNullValuedAttribute(attr, attributeValue) == false
                   && ((_jsonApiContext.QuerySet == null
                       || _jsonApiContext.QuerySet.Fields.Count == 0)
                       || _jsonApiContext.QuerySet.Fields.Contains(attr.InternalAttributeName));
        }

        private bool OmitNullValuedAttribute(AttrAttribute attr, object attributeValue)
        {
            return attributeValue == null && _documentBuilderOptions.OmitNullValuedAttributes;
        }

        private void AddRelationships(DocumentData data, ContextEntity contextEntity, IIdentifiable entity)
        {
            data.Relationships = new Dictionary<string, RelationshipData>();
            contextEntity.Relationships.ForEach(r =>
                data.Relationships.Add(
                    r.PublicRelationshipName,
                    GetRelationshipData(r, contextEntity, entity)
                )
            );
        }

        private RelationshipData GetRelationshipData(RelationshipAttribute attr, ContextEntity contextEntity, IIdentifiable entity)
        {
            var linkBuilder = new LinkBuilder(_jsonApiContext);

            var relationshipData = new RelationshipData();

            if (attr.DocumentLinks.HasFlag(Link.None) == false)
            {
                relationshipData.Links = new Links();
                if (attr.DocumentLinks.HasFlag(Link.Self))
                    relationshipData.Links.Self = linkBuilder.GetSelfRelationLink(contextEntity.EntityName, entity.StringId, attr.PublicRelationshipName);

                if (attr.DocumentLinks.HasFlag(Link.Related))
                    relationshipData.Links.Related = linkBuilder.GetRelatedRelationLink(contextEntity.EntityName, entity.StringId, attr.PublicRelationshipName);
            }

            // this only includes the navigation property, we need to actually check the navigation property Id
            var navigationEntity = _jsonApiContext.ContextGraph.GetRelationship(entity, attr.InternalRelationshipName);
            if (navigationEntity == null)
                relationshipData.SingleData = attr.IsHasOne
                    ? GetIndependentRelationshipIdentifier((HasOneAttribute)attr, entity)
                    : null;
            else if (navigationEntity is IEnumerable)
                relationshipData.ManyData = GetRelationships((IEnumerable<object>)navigationEntity);
            else
                relationshipData.SingleData = GetRelationship(navigationEntity);

            return relationshipData;
        }

        private List<DocumentData> GetIncludedEntities(List<DocumentData> included, ContextEntity contextEntity, IIdentifiable entity)
        {
            contextEntity.Relationships.ForEach(r =>
            {
                if (!RelationshipIsIncluded(r.PublicRelationshipName)) return;

                var navigationEntity = _jsonApiContext.ContextGraph.GetRelationship(entity, r.InternalRelationshipName);

                if (navigationEntity is IEnumerable hasManyNavigationEntity)
                    foreach (IIdentifiable includedEntity in hasManyNavigationEntity)
                        included = AddIncludedEntity(included, includedEntity);
                else
                    included = AddIncludedEntity(included, (IIdentifiable)navigationEntity);
            });

            return included;
        }

        private List<DocumentData> AddIncludedEntity(List<DocumentData> entities, IIdentifiable entity)
        {
            var includedEntity = GetIncludedEntity(entity);

            if (entities == null)
                entities = new List<DocumentData>();

            if (includedEntity != null && entities.Any(doc =>
                string.Equals(doc.Id, includedEntity.Id) && string.Equals(doc.Type, includedEntity.Type)) == false)
            {
                entities.Add(includedEntity);
            }

            return entities;
        }

        private DocumentData GetIncludedEntity(IIdentifiable entity)
        {
            if (entity == null) return null;

            var contextEntity = _jsonApiContext.ContextGraph.GetContextEntity(entity.GetType());
            var resourceDefinition = _scopedServiceProvider.GetService(contextEntity.ResourceType) as IResourceDefinition;

            var data = GetData(contextEntity, entity, resourceDefinition);

            data.Attributes = new Dictionary<string, object>();

            contextEntity.Attributes.ForEach(attr =>
            {
                data.Attributes.Add(attr.PublicAttributeName, attr.GetValue(entity));
            });

            return data;
        }

        private bool RelationshipIsIncluded(string relationshipName)
        {
            return _jsonApiContext.IncludedRelationships != null &&
                _jsonApiContext.IncludedRelationships.SelectMany(r => r.Split('.')).Contains(relationshipName);
        }

        private List<ResourceIdentifierObject> GetRelationships(IEnumerable<object> entities)
        {
            var objType = GetElementType(entities);

            var typeName = _jsonApiContext.ContextGraph.GetContextEntity(objType);

            var relationships = new List<ResourceIdentifierObject>();
            foreach (var entity in entities)
            {
                relationships.Add(new ResourceIdentifierObject
                {
                    Type = typeName.EntityName,
                    Id = ((IIdentifiable)entity).StringId
                });
            }
            return relationships;
        }

        private ResourceIdentifierObject GetRelationship(object entity)
        {
            var objType = entity.GetType();
            var contextEntity = _jsonApiContext.ContextGraph.GetContextEntity(objType);

            if(entity is IIdentifiable identifiableEntity)
                return new ResourceIdentifierObject
                {
                    Type = contextEntity.EntityName,
                    Id = identifiableEntity.StringId
                };

            return null;
        }

        private ResourceIdentifierObject GetIndependentRelationshipIdentifier(HasOneAttribute hasOne, IIdentifiable entity)
        {
            var independentRelationshipIdentifier = GetIdentifiablePropertyValue(hasOne, entity);
            if (independentRelationshipIdentifier == null)
                return null;

            var relatedContextEntity = _jsonApiContext.ContextGraph.GetContextEntity(hasOne.Type);
            if (relatedContextEntity == null) // TODO: this should probably be a debug log at minimum
                return null;

            return new ResourceIdentifierObject
            {
                Type = relatedContextEntity.EntityName,
                Id = independentRelationshipIdentifier.ToString()
            };
        }


        private static Type GetElementType(IEnumerable enumerable)
        {
            var enumerableTypes = enumerable.GetType()
                .GetInterfaces()
                .Where(t => t.IsGenericType == true && t.GetGenericTypeDefinition() == typeof(IEnumerable<>))
                .ToList();

            var numberOfEnumerableTypes = enumerableTypes.Count;

            if (numberOfEnumerableTypes == 0)
            {
                throw new ArgumentException($"{nameof(enumerable)} of type {enumerable.GetType().FullName} does not implement a generic variant of {nameof(IEnumerable)}");
            }

            if (numberOfEnumerableTypes > 1)
            {
                throw new ArgumentException($"{nameof(enumerable)} of type {enumerable.GetType().FullName} implements more than one generic variant of {nameof(IEnumerable)}:\n" +
                    $"{string.Join("\n", enumerableTypes.Select(t => t.FullName))}");
            }

            var elementType = enumerableTypes[0].GenericTypeArguments[0];

            return elementType;
        }

        private object GetIdentifiablePropertyValue(HasOneAttribute hasOne, object resource) => resource
                .GetType()
                .GetProperty(hasOne.IdentifiablePropertyName)
                ?.GetValue(resource);
    }
}