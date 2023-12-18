// // import { googleAPI } from '../../services/google-api/google-api'
// import { onlineAPI } from '../../services/april-online-api/online-api'
// import { generalAPI } from '../../services/firebase-api/firebase-api'
// import { aitest } from '../../services/openai-api/openai-api'
// import { PreloaderCodesEnum } from '../../types/types'
// import { InferActionsTypes, ThunkType } from '../store'
// import { getAuthApp } from './auth/auth-reducer'
// // import { getDialogs } from './dialogs/dialogs-reducer'
// import { inProgress, InProgressType } from './preloader/preloader-reducer'

import { InferActionsTypes, ThunkType } from ".."
import { onlineAPI } from "../../helpers/april-online/online-api"
import { initFirebaseBackend } from "../../helpers/firebase/firebase_helper"
import { firebaseConfig } from "../../secret/secret"
import { getAuthApp } from "./auth/auth-reducer"

//TYPES
type AppStateType = typeof initialState
type InitialActionType = InferActionsTypes<typeof initialActions>
type AuthThunkType = ThunkType<InitialActionType>

// STATE
let initialState = {
    initialized: false as boolean,
}


//ACTION CREATORS
const initialActions = {
    initializedSuccess: () => ({ type: 'SP/APP/INITIALIZED_SUCCES' } as const)
}



//THUNKS
export const initialize = (): AuthThunkType => async (dispatch) => {
    
    const response = await onlineAPI.service('portals', 'get', 'portals');
    const infoblocks = await onlineAPI.service('infoblocks', 'get', 'infoblocks', null)
    const fire = initFirebaseBackend(firebaseConfig)
    debugger
    // await dispatch(getAuthApp())
    dispatch(initialActions.initializedSuccess())
    //FROM DIALOGS REDUCER -> get Dialogs
    // dispatch(getDialogs())
    // dispatch(inProgress(false, PreloaderCodesEnum.Global))//inProgress-status
    // await  generalAPI.clientFieldGenerate()


    

}


//REDUCER
 const app = (state: AppStateType = initialState, action: InitialActionType): AppStateType => {

    switch (action.type) {
        case 'SP/APP/INITIALIZED_SUCCES': return { ...state, initialized: true }
        default: return state
    }

}



export default app