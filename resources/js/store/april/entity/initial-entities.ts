import { API_METHOD } from "../../../types/app/app-type";
import { ENTITY_QUANTITY } from "../../../types/entity/entity-types";

export type ALL_ENTITIES = typeof allEntities
export const allEntities = [

    {
        id: 0,
        item: {
            name: 'portal',
            title: 'Клиент',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'portal',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'portals',
            title: 'Клиенты',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'portals',
                method: API_METHOD.GET
            }
        }

    },
    {
        id: 1,
        item: {
            name: 'template',
            title: 'Шаблон',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'template',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'templates',
            title: 'Шаблоны',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'templates',
                method: API_METHOD.GET
            }

        },

    },
    {
        id: 0,
        item: {
            name: 'field',
            title: 'Свойство',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'tfield',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'tfields',
            title: 'Свойства',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'tfields',
                method: API_METHOD.GET
            }

        },

    },

]