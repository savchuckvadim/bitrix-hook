// import { inProgress } from "../preloader/preloader-reducer"
import { AppDispatchType, AppStateType, InferActionsTypes } from "../.."
import { onlineAPI } from "../../../helpers/april-online/online-api"
import { googleAPI } from "../../../helpers/google/google-api"
import { API_METHOD } from "../../../types/app/app-type"
import { CreatingEntityType, Entity, EntityField, EntityFormField, EntityStateType, InitialEntity, InitialEntityData, InitialEntityGroup, RelationsState, RelationState, TemplateAddData, TemplateInitialAddData } from "../../../types/entity/entity-types"
import { Template } from "../../../types/entity/template-types"
import { getDataForSetTField, getDataForSetTemplate, getInitialTemplateData, getInitialTemplateFieldData } from "../../../utils/template-utils"



type EntityActionsTypes = InferActionsTypes<typeof entityActions>
type GetStateType = () => AppStateType
export type GetInitialRelationFunction = typeof getInitialRelationEntity



const initialState = {

    items: [] as Array<InitialEntityData>,
    type: null, // 'entity' 'entities'
    current: null,
    creating: {
        formData: null as null | InitialEntityData,
        isInitialized: false as boolean,
        isFetching: false as boolean,
        isHaveGroup: false as boolean,
    } as CreatingEntityType,
    relation: {
        isCreating: false as boolean,
        // entity: null as null | any,
        // formData: null as null | any,
        parrentGroupName: null as string | null,
        // parrentFieldId: null as number | null,

    },
    relations: {
        formData: null as null | InitialEntityData,
        isInitialized: false as boolean,
        isFetching: false as boolean,
        isHaveGroup: false as boolean,
        isActive: false as boolean,
    } as RelationsState
    // fields: [],


} as EntityStateType



//AC
export const entityActions = {
    setEntityItems:
        (items: Array<Template>) =>
            ({ type: 'entity/SET_ENTITIES', items } as const),
    setEntityItem:
        (item: Template | null) =>
            ({ type: 'entity/SET_CURRENT_ENTIY', item } as const),
    // setInitializingAddProcess: () => ({ type: 'entity/SET_INITIALIZING_ADD' } as const),
    setFetchingInitialAdd:
        () =>
            ({ type: 'entity/SET_FETCHING_INITIAL_DATA' } as const),
    setInitialAdd:
        (initialData: InitialEntityData | null) =>
            ({ type: 'entity/SET_INITIAL_CREATE_ENTITY', initialData } as const),
    setCreatingRelation:
        (status: boolean, entity: null | any, groupName: string, relationIndex: number) =>
            ({ type: 'entity/SET_CREATING_RELATION', status, entity, groupName, relationIndex } as const),
    setCreatedRelation:
        (groups: Array<InitialEntityGroup>) =>
            ({ type: 'entity/SET_CREATED_RELATION', groups } as const),

    setEntityInitRelations:
        (initialData: InitialEntityData | null) =>
            ({ type: 'entity/SET_ENTITY_INIT_RELATIONS', initialData } as const),
    setEntityRelationsProp:
        (groupName: string, apiName: string, id: number, value: any) =>
        ({
            type: 'entity/SET_ENTITY_RELATIONS_PROP', groupName,
            apiName,
            id,
            value
        } as const),

    cleanEntityRelationsProp:
        () =>
        ({
            type: 'entity/CLEAN_ENTITY_RELATIONS'
        } as const),

}



//THUNK
//entities items
export const updateEntities = (token = null, entityName: string) => async (dispatch: AppDispatchType, getState: GetStateType) => {
debugger
    const state = getState()
    //получить из гугла массив entities и вставить в firebase и april-online

    // dispatch(inProgress(true, 'component'))
    const fetchedData = await googleAPI.get(token)
    debugger
    if(entityName === 'garant_prof_price'){
        debugger
    }
    let savedfireData = null
    let onlineSavedData = null
    const firebaseAPI = state.app.firebaseBackend

    //@ts-ignore
    if (fetchedData && fetchedData[`${entityName}`]) {
        //@ts-ignore
        const data = fetchedData[`${entityName}`]

        if (entityName === 'tfields') {
            // const firebasedata = tfieldsSetToFirebase(data.fields, data.items)
            // savedfireData = await firebaseAPI?.setCollection(entityName, firebasedata)

            // onlineSavedData = await onlineAPI.setCollection(entityName, data)




        } else {
            // savedfireData = await firebaseAPI?.setCollection(entityName, data)

            onlineSavedData = await onlineAPI.setCollection(entityName, data)

        }


        dispatch(entityActions.setEntityItems(data))
    }

    // dispatch(inProgress(false, 'component'))

}
export const getEntities = (url: string, method: string, collectionName: string, data: any = null) => async (dispatch: AppDispatchType, getState: GetStateType) => {

    if (url) {

        const collection = await onlineAPI.service(url, API_METHOD.GET, collectionName, null)


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
export const setOrupdateEntityItem = (history: (url: string) => void,
    currentUrl: string, url: string, entityName: string, data: FormData, isFormData = true) =>
    async (dispatch: AppDispatchType, getState: GetStateType) => {


        if (url) {

            const formData = data as FormData
            let apiData = {} as { [key: string]: any };
            if (url == 'portal') {

                for (let [key, value] of formData.entries()) {
                    if (key === 'number' ||
                        key === 'domain' ||
                        key === 'key'
                    ) {
                        apiData[key] = value;
                    } else if (
                        key === 'C_REST_CLIENT_SECRET'

                    ) {
                        apiData['clientId'] = value;
                        apiData['clientSecret'] = value;

                    } else if (
                        key === 'C_REST_WEB_HOOK_URL'

                    ) {
                        apiData['hook'] = value;
                    }

                }

            } else {
                apiData = formData

            }


            let targetUrl = currentUrl
            let method = API_METHOD.POST
            if (targetUrl) {
                if (targetUrl.endsWith("/add")) {

                    targetUrl = targetUrl.slice(0, -4);

                    // if (targetUrl.endsWith("ies")) {
                    //     // Удаляем 'ies' и добавляем 'y'
                    //     targetUrl = targetUrl.slice(0, -3) + "y";
                    // } else if (targetUrl.endsWith("s")) {
                    //     // Удаление окончания 's' для обычного множественного числа, например 'lists' -> 'list'
                    //     targetUrl = targetUrl.slice(0, -1);
                    // }


                } else {
                    //update

                    // method = API_METHOD.PUT

                }
            }
            //@ts-ignore
            // if (apiData.number) {
            //     //@ts-ignore
            //     apiData.number = Number(apiData.number)
            // }

            // const item = await onlineAPI.service(url, API_METHOD.POST, entityName, apiData)
            const item = await onlineAPI.service(targetUrl, method, entityName, apiData)

            if (item) {
                dispatch(entityActions.setEntityItem(item))

                if (item.id) {

                    const redirectUrl = `${url}/${item.id}`
                    redirectUrl !== currentUrl
                        && history(`../../${url}/${item.id}`)
                }

            } else {
                console.log('no collection')
            }
        } else {
            console.log('no url')
        }


    }
export const getInitialEntityData = (url: string, router: any, currentUrl: string, history: (url: string) => void) => async (dispatch: AppDispatchType, getState: GetStateType) => {
    // parentEntityId //entityId
    const entityState = getState().entity as EntityStateType
    let cretingEntity = entityState.creating.formData
    debugger
    if (url) {
        let fullUrl = `initial${currentUrl}`
        let targetUrl = currentUrl
        if (fullUrl.endsWith("/add")) {
            fullUrl = fullUrl.slice(0, -4); // Обрезать последние 4 символа ("/add")



        }
        if (fullUrl.endsWith("ies")) {
            // Удаляем 'ies' и добавляем 'y'
            fullUrl = fullUrl.slice(0, -3) + "y";
            targetUrl = targetUrl.slice(0, -3) + "y";
            targetUrl = `${targetUrl}/add`
        }

        if (fullUrl.endsWith("s")) {
            targetUrl = targetUrl.slice(0, -1);
            targetUrl = `${targetUrl}/add`
            fullUrl = fullUrl.slice(0, -1); // Обрезать последние 1 символа ("s")
        }
        if (fullUrl.endsWith("s/")) {
            targetUrl = targetUrl.slice(0, -2);
            targetUrl = `${targetUrl}/add`
            fullUrl = fullUrl.slice(0, -2); // Обрезать последние 2 символа ("s/")
        }

        // let targetRoot = `/${url}/add`

        // if (router) {
        // if (router.params && router.params.entityId) {
        //     //значит инициализируется создание дочерней сущности
        //     let itemCurrentUrl = currentUrl
        //     if (itemCurrentUrl.endsWith('s')) {
        //         itemCurrentUrl = itemCurrentUrl.slice(0, -1);
        //     }
        //     if (!router.params.entityChildrenId
        //         && !itemCurrentUrl.endsWith('add')
        //         && !itemCurrentUrl.endsWith('add/')
        //     ) {
        //         targetUrl = `${itemCurrentUrl}/add`
        //         targetRoot = `${itemCurrentUrl}/add`
        //     } else {
        //         targetUrl = `${itemCurrentUrl}`
        //         targetRoot = `${itemCurrentUrl}`
        //     }

        //     // fullUrl = `initial${itemCurrentUrl}`



        // }

        // }

        dispatch(entityActions.setFetchingInitialAdd())

        if (!cretingEntity) {
            cretingEntity = await onlineAPI.service(fullUrl, API_METHOD.GET, 'initial', null) as InitialEntityData | null
            if (cretingEntity) {
debugger
                dispatch(entityActions.setInitialAdd(cretingEntity))
            } else {
                console.log('no initial data')
            }
        }





        if (currentUrl !== targetUrl) {


            router.navigate(targetUrl, { replace: true })
        }

    } else {
        console.log('no url')
    }
}



export const deleteEntityItem = (history: (url: string) => void, url: string, entityName: string, entityId: number) =>
    async (dispatch: AppDispatchType, getState: GetStateType) => {

        if (url) {
            const fullUrl = `${url}/${entityId}`
            const item = await onlineAPI.service(fullUrl, API_METHOD.DELETE, `${entityName}Id`, null)


            if (item) {

                dispatch(entityActions.setEntityItem(null))
                history(`../../${entityName}s`)
            } else {
                console.log('no collection')
            }
        } else {
            console.log('no url')
        }


    }



//relation
export const getInitialRelationEntity = (groupName: string, relationIndex: number) =>
    async (dispatch: AppDispatchType, getState: GetStateType) => {

        const state = getState()
        const entity = state.entity as EntityStateType

        const formData = entity.creating.formData
        const searchingGroup = formData && formData.groups.find(group => group.groupName === groupName)


        if (searchingGroup) {

            const searchingItem = searchingGroup.relations[relationIndex]

            // if ((searchingItem && fieldId) || (searchingItem && fieldId === 0)) {
            //@ts-ignore
            dispatch(entityActions.setCreatingRelation(true, searchingItem, groupName, relationIndex))
            // }
        }

    }
export const setRelation = (relation: RelationState) =>
    async (dispatch: AppDispatchType, getState: GetStateType) => {
        const entityState = getState().entity as EntityStateType
        const creatingEntity = entityState.creating

        if (creatingEntity && creatingEntity.formData) {
            if (relation && relation.parrentGroupName
                // &&(relation.parrentFieldId || relation.parrentFieldId === 0)
            ) {

                let updatedRelations = [] as Array<EntityFormField | any>
                const updatedGroups = creatingEntity.formData.groups.map((group: InitialEntityGroup) => {

                    if (group.groupName === relation.parrentGroupName) {

                        let count = 0
                        // updatedRelations = group.relations
                        // if (group.fields.find(field => field.id === relation.parrentFieldId)) {
                        let resultPushData = {
                            ...relation.formData,
                            isCreated: true
                        }
                        let resultRelations = [resultPushData]
                        if (group.relations[0].isCreated) {
                            let isUpdated = false
                            resultRelations = group.relations.map((rltn, index) => {
                                if (index === relation.relationIndex) {
                                    isUpdated = true
                                    return resultPushData
                                } else {
                                    return rltn
                                }

                            })

                            if (!isUpdated) {
                                resultRelations.push(resultPushData)
                            }
                        }



                        // const resultRelations = group.relations[0].isCreated
                        //     ? [
                        //         ...group.relations,
                        //         resultPushData
                        //     ]
                        //     :
                        return {
                            ...group,
                            relations: resultRelations
                        }
                        // } else {
                        //     updatedFields.push(relation)
                        // }

                    } else {
                        return group
                    }

                })

                dispatch(entityActions.setCreatedRelation(updatedGroups))
            }




        }
    }




export const addRelation = (groupName: string, relationIndex: number) =>
    async (dispatch: AppDispatchType, getState: GetStateType) => {

        const entity = getState().entity as EntityStateType
        const currentGroups = entity.creating.formData?.groups
        if (currentGroups) {
            const updatedGroups = currentGroups.map(group => {
                if (group.groupName === groupName) {
                    let updtdGroup = { ...group } as InitialEntityGroup
                    updtdGroup.relations.push(group.relations[relationIndex])
                    return updtdGroup
                } else {
                    return group
                }
            }) as Array<InitialEntityGroup>
            dispatch(entityActions.setCreatedRelation(updatedGroups))
        }
    }

//entity old
const initialAddEntity = (entityName: string) => async (dispatch: AppDispatchType, getState: GetStateType) => {

    // let initialData = {
    //     parameters: [],
    //     fields: []
    // } as TemplateAddData


    // switch (entityName) {
    //     case 'template':
    //         initialData = await getInitialTemplateData()
    //         dispatch(entityActions.setInitialAdd(initialData))
    //         break;


    //     case 'field':

    //         initialData = await getInitialTemplateFieldData()
    //         dispatch(entityActions.setInitialAdd(initialData))
    //         break;
    //     default:
    //         break;
    // }

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
                creating: {
                    formData: null,
                    isInitialized: false,
                    isFetching: false,
                },

            }
        case 'entity/SET_INITIAL_CREATE_ENTITY':

            const initialData = action.initialData
            debugger
            return {
                ...state,
                creating: {
                    formData: initialData,
                    isInitialized: true,
                    isFetching: false,
                },


            }
        case 'entity/SET_FETCHING_INITIAL_DATA':
            return {
                ...state,
                creating: {
                    ...state.creating,
                    isInitialized: false,
                    isFetching: true,
                },
            }
        case 'entity/SET_CREATING_RELATION':

            if (action.entity && action.entity.groups[0] && action.entity.groups[0].fields) {
                // const entiyFields = action.entity.fields.length > 0
                //     ? action.entity.fields
                //     : action.entity.initialValue

                return {
                    ...state,
                    relation: {
                        ...state.relation,
                        parrentGroupName: action.groupName,
                        formData: action.entity,
                        isCreating: true as boolean,
                        relationIndex: action.relationIndex
                        // parrentFieldId: action.fieldId,
                        // parrentGroupName: action.groupName

                    },
                }
            } else return state

        case 'entity/SET_CREATED_RELATION':

            return {
                ...state,
                creating: {
                    ...state.creating,
                    formData: {
                        ...state.creating.formData,
                        groups: action.groups
                    }
                },
                relation: {
                    ...state.relation,
                    isCreating: false as boolean,
                    // entity: null as null | any,
                    formData: null as null | any,
                    parrentGroupName: null as string | null,
                    relationIndex: null as number | null,

                },
            }

        case 'entity/SET_ENTITY_INIT_RELATIONS':

            const relationData = action.initialData

            return {
                ...state,
                relations: {
                    formData: relationData,
                    isInitialized: true,
                    isFetching: false,
                    isActive: true
                },


            }
        // 
        // CLEAN_ENTITY_RELATIONS

        case 'entity/SET_ENTITY_RELATIONS_PROP':

            const resultGroups = state.relations.formData?.groups.map(group => {

                if (group.groupName === action.groupName) {

                    return {
                        ...group,
                        fields: group.fields.map(field => {
                            if (field.apiName === action.apiName && field.id === action.id) {

                                return {
                                    ...field,
                                    value: action.value
                                }
                            }
                            return field;
                        })

                    }
                }
                return group
            })

            return {
                ...state,
                relations: {
                    ...state.relations,
                    formData: {
                        ...state.relations.formData,
                        groups: resultGroups
                    }

                },


            }

        case "entity/CLEAN_ENTITY_RELATIONS":
            return {
                ...state,
                relations: {
                    formData: null,
                    isInitialized: false,
                    isFetching: false,
                    isActive: false
                },
            }
        // if (action.entity && action.entity.fields) {
        //     const entiyFields = action.entity.fields.length > 0
        //         ? action.entity.fields
        //         : action.entity.initialValue

        //     return {
        //         ...state,
        //         relation: {
        //             ...state.relation,
        //             isCreating: action.status,
        //             entity: {
        //                 ...action.entity,
        //                 fields: entiyFields,

        //             },
        //             formData: entiyFields,

        //         },
        //     }
        // } else return state

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
