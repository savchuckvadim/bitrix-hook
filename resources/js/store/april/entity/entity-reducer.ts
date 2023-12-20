// import { inProgress } from "../preloader/preloader-reducer"
import { AppDispatchType, AppStateType, InferActionsTypes } from "../.."
import { onlineAPI } from "../../../helpers/april-online/online-api"
import { googleAPI } from "../../../helpers/google/google-api"
import { API_METHOD } from "../../../types/app/app-type"
import { Entity, EntityField, TemplateAddData, TemplateInitialAddData } from "../../../types/entity/entity-types"
import { Template } from "../../../types/entity/template-types"
import { tfieldsSetToFirebase } from "../../../utils/service-utils/service-utils"
import { getDataForSetTField, getDataForSetTemplate, getInitialTemplateData, getInitialTemplateFieldData } from "../../../utils/template-utils"


type EntityStateType = typeof initialState
type EntityActionsTypes = InferActionsTypes<typeof entityActions>
type GetStateType = () => AppStateType


const initialState = {

    items: [] as Array<any>,
    type: null, // 'entity' 'entities'
    current: null,
    fields: [],
    adding: {
        parameters: [],
        fields: [
            // {
            //     name: 'название шаблона',
            //     type: 'array',
            //     items: []
            // },
            // {
            //     name: 'название шаблона',
            //     type: 'array',
            //     items: []
            // },
        ]
    },

    isInitializingAdd: false


}



//AC
export const entityActions = {
    setEntityItems: (items: Array<Template>) => ({ type: 'entity/SET_ENTITIES', items } as const),
    setEntityItem: (item: Template) => ({ type: 'entity/SET_CURRENT_ENTIY', item } as const),
    // setInitializingAddProcess: () => ({ type: 'entity/SET_INITIALIZING_ADD' } as const),
    setInitialAdd: (initialData: TemplateAddData) => ({ type: 'entity/SET_INITIAL_ADD', initialData } as const)
}



//THUNK
//entities items
export const updateEntities = (token = null, entityName: string) => async (dispatch: AppDispatchType, getState: GetStateType) => {

    const state = getState()
    //получить из гугла массив entities и вставить в firebase и april-online

    // dispatch(inProgress(true, 'component'))
    const fetchedData = await googleAPI.get(token)
    let savedfireData = null
    let onlineSavedData = null
    const firebaseAPI = state.app.firebaseBackend

    //@ts-ignore
    if (fetchedData && fetchedData[`${entityName}`]) {
        //@ts-ignore
        const data = fetchedData[`${entityName}`]

        if (entityName === 'tfields') {
            const firebasedata = tfieldsSetToFirebase(data.fields, data.items)
            savedfireData = await firebaseAPI?.setCollection(entityName, firebasedata)

            onlineSavedData = await onlineAPI.setCollection(entityName, data)




        } else {
            savedfireData = await firebaseAPI?.setCollection(entityName, data)
            onlineSavedData = await onlineAPI.setCollection(entityName, data)

        }


        dispatch(entityActions.setEntityItems(data))
    }

    // dispatch(inProgress(false, 'component'))

}


export const getEntities = (url: string, method: string, collectionName: string, data: any = null) => async (dispatch: AppDispatchType, getState: GetStateType) => {

    if (url) {
        const collection = await onlineAPI.getCollection(url, method, collectionName, data)


        if (collection) {
            dispatch(entityActions.setEntityItems(collection))
        } else {
            console.log('no collection')
        }
    } else {
        console.log('no url')
    }


}
export const getEntityItem = (url: string, entityName: string, entityId: number) => async (dispatch: AppDispatchType, getState: GetStateType) => {

    if (url) {
        const fullUrl = `${url}/${entityId}`
        const item = await onlineAPI.service(fullUrl, API_METHOD.GET, entityName, null)

        
        if (item) {
            dispatch(entityActions.setEntityItem(item))
        } else {
            console.log('no collection')
        }
    } else {
        console.log('no url')
    }


}

//entity
export const initialAddEntity = (entityName: string) => async (dispatch: AppDispatchType, getState: GetStateType) => {

    let initialData = {
        parameters: [],
        fields: []
    } as TemplateAddData


    switch (entityName) {
        case 'template':
            initialData = await getInitialTemplateData()
            dispatch(entityActions.setInitialAdd(initialData))
            break;


        case 'field':

            initialData = await getInitialTemplateFieldData()
            dispatch(entityActions.setInitialAdd(initialData))
            break;
        default:
            break;
    }

}

export const setUpdatingEntity = (url: string, model: string, values: Array<any>) => async (dispatch: AppDispatchType, getState: GetStateType) => {



    const state = getState() as AppStateType

    switch (model) {
        case 'template':
            const dataT = getDataForSetTemplate(state, values)
            await onlineAPI.service(url, 'post', model, dataT)
            break;


        case 'field':
            const dataTF = getDataForSetTField(values, 'templateId')
            await onlineAPI.service(url, 'post', model, dataTF)
            break;
        default:
            break;
    }





}



export const setNewEntity = (entity: Entity) => async (dispatch: AppDispatchType, getState: GetStateType) => { }


// export const getEntityItems = (entityName: string) => async (dispatch: AppDispatchType, getState: GetStateType) => {


//     dispatch(inProgress(true, 'component'))

//     let entityItems = await generalAPI.getCollection(entityName)

//     if (entityItems) {

//         dispatch(entityActions.setEntityItems(entityItems))
//     }

//     dispatch(inProgress(false, 'component'))

// }




const entity = (state: EntityStateType = initialState, action: EntityActionsTypes) => {

    switch (action.type) {

        case 'entity/SET_ENTITIES':

            return {
                ...state,
                items: action.items,

            }
        case 'entity/SET_CURRENT_ENTIY':

            return {
                ...state,
                current: action.item,

            }
        case 'entity/SET_INITIAL_ADD':

            const initialData = action.initialData

            return {
                ...state,
                adding: {
                    ...initialData
                },
                isInitializingAdd: true

            }

        // case 'entity/SET_INITIALIZING_ADD':
        //     
        //     return {
        //         ...state,
        //         adding: {
        //             id: null,
        //             name: '',
        //             domain: '',
        //             type: '',
        //             fields: [
        //                 {
        //                     name: 'name',
        //                     type: 'string',
        //                     value: null,
        //                     items: []
        //                 },
        //                 {
        //                     name: 'domain',
        //                     type: 'string',
        //                     value: null,
        //                     items: []
        //                 },
        //                 {
        //                     name: 'type',       //offer | invoice | contract
        //                     type: 'string',
        //                     value: null,
        //                     items: []
        //                 },

        //             ]
        //         },
        //         isInitializingAdd: true

        //     }


        default:
            return state
    }


}

export default entity