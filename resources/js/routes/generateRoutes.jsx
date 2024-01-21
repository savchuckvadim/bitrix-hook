import EntityContainer from "../components/April/Entity/EntityContainer";
import { getRouteDataById } from "../store/april/entity/initial-entities";

export const generateRoutes = (entityId, basePath = '') => {
    const entity = getRouteDataById(entityId);
    if (!entity) return [];
  
    const routes = [];
  
    // Создаем маршруты для текущей сущности
    if (entity.item) {
      routes.push({
        path: `${basePath}/${entity.item.get.url}`,
        component: <EntityContainer
          type={entity.item.type}
          itemUrl={entity.item.get.url}
          entityName={entity.item.name}
          entityTitle={entity.item.title}
        />
      });
    }
  
    if (entity.items) {
      routes.push({
        path: `${basePath}/${entity.items.get.url}`,
        component: <EntityContainer
          type={entity.items.type}
          itemsUrl={entity.items.get.url}
          entityName={entity.items.name}
          entityTitle={entity.items.title}
        />
      });
    }
  
    // Рекурсивно добавляем маршруты для связанных сущностей
    if (entity.relations && entity.relations.length > 0) {
      entity.relations.forEach(relatedEntityId => {
        routes.push(...generateRoutes(relatedEntityId, `${basePath}/${entity.items.get.url}`));
      });
    }
  
    return routes;
  };
  