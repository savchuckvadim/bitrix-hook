import { API_METHOD } from "../../../types/app/app-type";
import { ENTITY_QUANTITY } from "../../../types/entity/entity-types";
import { getEntityRoute, RouteInit } from "./measure-routes";

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
        relations: [1, 5, 11, 12, 13, 14, 15, 16, 21, 22, 23, 24, 25, 27, 28],

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
            7, 8, 9, 10, 15, 17
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
        id: 15,
        item: {
            name: 'qr',
            title: 'qr',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'qr',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'qrs',
            title: 'qrs',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'qrs',
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
        relations: [17, 19],
    },
    {
        id: 14,
        item: {
            name: 'rpa',
            title: 'RPA',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'rpa',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'rpas',
            title: 'RPAS',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'rpas',
                method: API_METHOD.GET
            }

        },
        relations: [17, 19],
    },
    {

        id: 15,
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

        id: 16,
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
        relations: [17],
    },
    {

        id: 17,
        item: {
            name: 'bitrixfield',
            title: 'Поле Битрикс',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'bitrixfield',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'bitrixfields',
            title: 'Поля  Битрикс',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'bitrixfields',
                method: API_METHOD.GET
            }

        },
        relations: [18],
    },
    {

        id: 18,
        item: {
            name: 'bitrixfielditem',
            title: 'Элемент Битрикс Филда',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'bitrixfielditem',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'bitrixfielditems',
            title: 'Элемент Битрикс Филда',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'bitrixfielditems',
                method: API_METHOD.GET
            }

        },
    },
    {

        id: 19,
        item: {
            name: 'category',
            title: 'Воронки, Категории в различных сущностях содержат стадии',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'category',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'categories',
            title: 'Воронки, Категории в различных сущностях содержат стадии',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'categories',
                method: API_METHOD.GET
            }

        },
        relations: [20],

    },
    {
        id: 20,
        item: {
            name: 'stage',
            title: 'Воронки, Категории в различных сущностях содержат стадии',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'stage',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'stages',
            title: 'Воронки, Категории в различных сущностях содержат стадии',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'stages',
                method: API_METHOD.GET
            }

        },

    },

    {
        id: 21,
        item: {
            name: 'deal',
            title: 'Обобщаящая связи модель Сделок',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'deal',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'deals',
            title: 'Обобщаящая связи модель Сделок',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'deals',
                method: API_METHOD.GET
            }

        },
        relations: [17, 19],

    },

    {
        id: 22,
        item: {
            name: 'company',
            title: 'Обобщаящая связи модель Компания',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'company',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'companies',
            title: 'Обобщаящая связи модель Компания',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'companies',
                method: API_METHOD.GET
            }

        },
        relations: [17],

    },
    {
        id: 23,
        item: {
            name: 'lead',
            title: 'Обобщаящая связи модель LEAD',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'lead',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'leads',
            title: 'Обобщаящая связи модель LEAD',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'leads',
                method: API_METHOD.GET
            }

        },
        relations: [17, 19],

    },
    {
        id: 28,
        item: {
            name: 'contact',
            title: 'Обобщаящая связи модель Контакт',
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: 'contact',
                method: API_METHOD.GET
            }

        },
        items: {
            name: 'contacts',
            title: 'Обобщаящая связи модель Контакт',
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: 'contacts',
                method: API_METHOD.GET
            }

        },
        relations: [17],

    },
] as Array<RouteInit>


allEntities.push(
    getEntityRoute(
        24,
        'portalmeasure',
        'Единица измерения',
        []
    )
)


allEntities.push(
    getEntityRoute(
        25,
        'contract',
        'Общая Модель Contract',
        []
    )
)

allEntities.push(
    getEntityRoute(
        26,
        'measure',
        'Единица измерения',
        []
    )
)
allEntities.push(
    getEntityRoute(
        27,
        'portalcontract',
        'Договоры с bitrixId Портала',
        []
    )
)

allEntities.push(
    getEntityRoute(
        100,
        'complect',
        'Комплекты GARANT',
        [101]
    )
)
allEntities.push(
    getEntityRoute(
        101,
        'cinfoblock',
        'Связанные с Комплектом инфоблоки',
        [4]
    )
)
allEntities.push(
    getEntityRoute(
        102,
        'infogroup',
        'Группы инфоблоков',
        [4]
    )
)

allEntities.push(
    getEntityRoute(
        103,
        'supply',
        'Виды поставки комплектов Гарант',
        []
    )
)
export const getRouteDataById = (id: number) => {
    return allEntities.find(routeData => routeData.id == id)
}



