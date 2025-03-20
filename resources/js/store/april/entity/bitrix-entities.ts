import { API_METHOD } from "../../../types/app/app-type";
import { ENTITY_QUANTITY } from "../../../types/entity/entity-types";
import {  RouteInit } from "./measure-routes";

export type BITRIX_ENTITIES = typeof bitrixEntities
export const bitrixEntities = [


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
  
    
] as Array<RouteInit>



export const getRouteDataById = (id: number) => {
    return bitrixEntities.find(routeData => routeData.id == id)
}



