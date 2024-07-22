import { number } from "prop-types";
import { API_METHOD } from "../../../types/app/app-type";
import { ENTITY_QUANTITY } from "../../../types/entity/entity-types";


const getMeasureRoutes = () => ({

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
    relations: [1, 5, 11, 12, 13, 14, 15, 16, 21, 22, 23],


})

export const getEntityRoute =
    (id: number,
        model: string,
        name: string,
        relations: Array<number>
    ): RouteInit => ({

        id: id,
        item: {
            name: model,
            title: name,
            type: ENTITY_QUANTITY.ENTITY,
            get: {
                url: model,
                method: API_METHOD.GET
            }

        },
        items: {
            name: `${model}s`,
            title: `${name}s`,
            type: ENTITY_QUANTITY.ENTITIES,
            get: {
                url: `${model}s`,
                method: API_METHOD.GET
            }
        },
        relations: relations,


    })


export type RouteInit = {
    id: number,
    item: {
        name: string,
        title: string,
        type: ENTITY_QUANTITY.ENTITY,
        get: {
            url: string,
            method: API_METHOD.GET
        }

    },
    items: {
        name: string,
        title: string,
        type: ENTITY_QUANTITY.ENTITIES,
        get: {
            url: string,
            method: API_METHOD.GET
        }
    },
    relations: Array<number>,
}