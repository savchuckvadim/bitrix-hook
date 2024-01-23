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
        },
        relations: [1, 11, 12, 13, 14],

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
            },


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
        relations: [
            2, 5, 10,
        ]
    },
    {
        id: 2,
        item: {
            name: 'field',
            title: 'Свойство',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'field',
                method: API_METHOD.GET
            },


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
        relations: [
            3

        ]

    },
    {
        id: 3,
        item: {
            name: 'item',
            title: 'Элемент свойства',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'item',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'items',
            title: 'Свойства',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'items',
                method: API_METHOD.GET
            }

        },
    },
    {
        id: 4,
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
        id: 5,
        item: {
            name: 'provider',
            title: 'Поставщик',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'provider',
                method: API_METHOD.GET
            },


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
        relations: [
            6
        ]


    },
    {
        id: 6,
        item: {
            name: 'rq',
            title: 'Реквизиты',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'rq',
                method: API_METHOD.GET
            },


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
        relations: [
            7, 8, 9
        ]




    },
    {
        id: 7,
        item: {
            name: 'logo',
            title: 'Логотип',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'logo',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'logos',
            title: 'Логотипы',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'logos',
                method: API_METHOD.GET
            }

        },
    },
    {
        id: 8,
        item: {
            name: 'signature',
            title: 'Подпись',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'signature',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'signatures',
            title: 'Подписи',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'signatures',
                method: API_METHOD.GET
            }

        },
    },
    {
        id: 9,
        item: {
            name: 'stamp',
            title: 'Печать',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'stamp',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'stamps',
            title: 'Печати',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'stamps',
                method: API_METHOD.GET
            }

        },
    },
    {
        id: 10,
        item: {
            name: 'counter',
            title: 'Счетчик',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'counter',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'counters',
            title: 'Счетчики',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'counters',
                method: API_METHOD.GET
            }

        },
    },
    {
        id: 11,
        item: {
            name: 'timezone',
            title: 'Часовой пояс',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'timezone',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'timezones',
            title: 'Часовые пояса',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'timezones',
                method: API_METHOD.GET
            }

        },
    },
    {
        id: 12,
        item: {
            name: 'callingGroup',
            title: 'Группа задач со звонками',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'callingGroup',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'callingGroups',
            title: 'Группы задач со звонками',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'callingGroups',
                method: API_METHOD.GET
            }

        },
    },
    {
        id: 13,
        item: {
            name: 'smart',
            title: 'Смарт процесс',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'smart',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'smarts',
            title: 'Смарт процессы',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'smarts',
                method: API_METHOD.GET
            }

        },
    },
    {

        id: 13,
        item: {
            name: 'departament',
            title: 'Целевой отдел компании',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'departament',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'departaments',
            title: 'Целевые отделы компании',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'departaments',
                method: API_METHOD.GET
            }

        },
    },
    {

        id: 14,
        item: {
            name: 'bitrixlist',
            title: 'Универсальные списки Битрикс',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'bitrixlist',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'bitrixlists',
            title: 'Универсальные списки Битрикс',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'bitrixlists',
                method: API_METHOD.GET
            }

        },
    }
]

export const getRouteDataById = (id: number) => {
    return allEntities.find(routeData => routeData.id == id)
}



