import EntityContainer from "../components/April/Entity/EntityContainer";
import { getRouteDataById } from "../store/april/entity/initial-entities";

export const generateRoutes = (entities, basePath = '') => {
  // const entity = getRouteDataById(entityId);
  // if (!entity) return [];
  let routes = []

  if (entities && entities.length) {
    entities.map((entity) => {

      if (entity.item) {
        routes.push({
          path: `${basePath}/${entity.item.get.url}/:entityId`,
          component: <EntityContainer
            type={entity.item.type}
            itemUrl={entity.item.get.url}
            itemsUrl={entity.items.get.url}
            entityName={entity.item.name}
            entityTitle={entity.item.title}
          />
        })

        
      }

      if (entity.items) {

        routes.push({
          path: `${basePath}/${entity.items.get.url}`,
          component: <EntityContainer
            type={entity.items.type}
            itemUrl={entity.item.get.url}
            itemsUrl={entity.items.get.url}
            entityName={entity.items.name}
            entityTitle={entity.items.title}
          />
        })
      }



    })
  } else {
    console.log(entities)
  }


  // Создаем маршруты для текущей сущности (item и items)


  // Рекурсивно добавляем маршруты для связанных сущностей
  // if (entity.relations && entity.relations.length > 0) {
  //   entity.relations.forEach(relatedEntityId => {
  //     const relatedEntity = getRouteDataById(relatedEntityId);
  //     if (relatedEntity && relatedEntity.item) {
  //       const newBasePath = `${basePath}/${entity.items.get.url}/:parentEntityId`;
  //       routes.push(...generateRoutes(relatedEntityId, newBasePath));
  //     }
  //   });
  // }

  return routes;
};

export const generateChildrenRoutes = (entities) => {

  const routes = [];
  entities.forEach(entity => {
    if (entity.relations && entity.relations.length > 0) {

      const childrenEntities = entities.filter((ent) => {
        return entity.relations.includes(ent.id)
      })
      
      console.log(childrenEntities)
      if (childrenEntities && childrenEntities.length) {
        const newBasePath = `${entity.item.get.url}/:parentEntityId`;
        generateRoutes(childrenEntities, newBasePath).map(cheldrenRoute => {
          routes.push(cheldrenRoute)
        })
      }

    }
  });

  console.log('children routes')
  console.log(routes)

  return routes;
};