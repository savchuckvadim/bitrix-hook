
import { onlineAPI } from "../../../helpers/april-online/online-api";
import { API_METHOD } from "../../../types/app/app-type";
import { EntityStateType, InitialEntityData } from "../../../types/entity/entity-types";
import { entityActions } from "./entity-reducer";
import { AppDispatchType, AppStateType } from "../.."

type GetStateType = () => AppStateType


export const getRelationEntityData =
    (router: any, url: string, entityName: string, entityId: number) =>
        async (dispatch: AppDispatchType, getState: GetStateType) => {
            // parentEntityId //entityId
            // router // location  navigate  params


            const entityState = getState().entity as EntityStateType
            let cretingEntity = entityState.creating.formData
            const currentRouter = router
            const fullUrl = `${url}/${entityId}/relation`



            cretingEntity = await onlineAPI.service(
                fullUrl,
                API_METHOD.GET,
                'relation',
                null
            ) as InitialEntityData | null
            if (cretingEntity) {

                dispatch(entityActions.setEntityInitRelations(cretingEntity))
            } else {
                console.log('no relations data')
            }


        }

export const sendEntityRelations =
    () => async (dispatch: AppDispatchType, getState: GetStateType) => {



        const state = getState() as AppStateType
        const entityState = (state.entity as EntityStateType)
        const relations = entityState.relations

        const data = relations.formData
        const entityName = data?.apiName

        const parentEntity = entityState.current
        const parentEntityId = parentEntity.id

        const fullUrl = `${entityName}/${parentEntityId}/relation`

        const result = await onlineAPI.service(
            fullUrl,
            API_METHOD.POST,
            'result',
            data
        ) as any
        dispatch(entityActions.cleanEntityRelationsProp())



    }