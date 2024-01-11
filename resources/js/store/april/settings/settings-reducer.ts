import { AppDispatchType, AppStateType, InferActionsTypes } from "../.."
import { DEPLOY_TYPE, updatePlacements } from "../../../helpers/april-server-front-api/api-server-front"

type SettingsActionsTypes = InferActionsTypes<typeof settingsActions>
type GetStateType = () => AppStateType
// export type GetInitialRelationFunction = typeof getInitialRelationEntity

export type SettingsStateType = typeof initialState
export type UpdateFront = typeof updateFront

const initialState = {

    actions: [
        {
            name: 'update client',
            type: DEPLOY_TYPE.CLIENT
        },
        {
            name: 'update public',
            type: DEPLOY_TYPE.PUBLIC
        },
        {
            name: 'update test',
            type: DEPLOY_TYPE.TEST
        },
        {
            name: 'update dev',
            type: DEPLOY_TYPE.DEV
        },
    ]

}



//AC
export const settingsActions = {
    setEntityItems:
        (items: Array<any>) =>
            ({ type: 'settings/UPDATE_FRONT', items } as const),


}

//THUNKS

export const updateFront = (type: DEPLOY_TYPE) =>
    async (dispatch: AppDispatchType, getState: GetStateType) => {

        await updatePlacements(type)

    }

const settings = (state: SettingsStateType = initialState, action: SettingsActionsTypes) => {

    switch (action.type) {
        case 'settings/UPDATE_FRONT':

            return state;

        default:
            return state;
    }
}

export default settings