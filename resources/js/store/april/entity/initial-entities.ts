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
        id: 2,
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
            name: 'fields',
            title: 'Свойства',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'fields',
                method: API_METHOD.GET
            }

        },


    },
    {
        id: 3,
        item: {
            name: 'infoblock',
            title: 'Инфоблок',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'infoblock',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'infoblocks',
            title: 'Инфоблоки',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'infoblocks',
                method: API_METHOD.GET
            }

        },



    },

    {
        id: 4,
        item: {
            name: 'provider',
            title: 'Поставщик',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'provider',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'providers',
            title: 'Поставщики',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'providers',
                method: API_METHOD.GET
            }

        },



    },
    {
        id: 5,
        item: {
            name: 'rq',
            title: 'Реквизиты',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'rq',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'rqs',
            title: 'Реквизиты',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'rqs',
                method: API_METHOD.GET
            }

        },



    },

]