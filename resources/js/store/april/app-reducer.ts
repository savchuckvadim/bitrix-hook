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
import { hookAPI } from "../../helpers/april-hook/hook-api"
import { onlineAPI } from "../../helpers/april-online/online-api"
import { initFirebaseBackend } from "../../helpers/firebase/firebase_helper"
import { FirebaseAuthBackendClassType, FirebaseAuthBackendInstanceType } from "../../helpers/firebase/types"
import { firebaseConfig } from "../../secret/secret"
import { API_METHOD, AppStatus } from "../../types/app/app-type"


//TYPES
type AppStateType = typeof initialState
type InitialActionType = InferActionsTypes<typeof appActions>
type AuthThunkType = ThunkType<InitialActionType>

// STATE
let initialState = {
    initialized: false as boolean,
    firebaseBackend: null as FirebaseAuthBackendInstanceType | null,
    status: 'off' as AppStatus
}


//ACTION CREATORS
const appActions = {
    initializedSuccess: () => ({ type: 'SP/APP/INITIALIZED_SUCCES' } as const),
    setFirebase: (firebase: FirebaseAuthBackendInstanceType) =>
        ({ type: 'SP/APP/SET_FIREBASE', firebase } as const),
    setAppStatus: (status: AppStatus) =>
        ({ type: 'SP/APP/SET_STATUS', status } as const),
}



//THUNKS
export const initialize = (): AuthThunkType => async (dispatch) => {


    const fireBack = initFirebaseBackend(firebaseConfig) as FirebaseAuthBackendInstanceType
    fireBack && dispatch(appActions.setFirebase(fireBack))

    const response = await onlineAPI.service('portals', 'get', 'portals');
    const infoblocks = await onlineAPI.service('infoblocks', 'get', 'infoblocks', null)
    const templates = await onlineAPI.service('templates/april-garant.bitrix24.ru', 'get', 'templates', null)
    const tryHook = await hookAPI.service('/smart/categories', API_METHOD.POST, 'result', null)
    // await dispatch(getAuthApp())
    dispatch(appActions.initializedSuccess())
    //FROM DIALOGS REDUCER -> get Dialogs
    // dispatch(getDialogs())
    // dispatch(inProgress(false, PreloaderCodesEnum.Global))//inProgress-status
    // await  generalAPI.clientFieldGenerate()




}


//REDUCER
const app = (state: AppStateType = initialState, action: InitialActionType): AppStateType => {

    switch (action.type) {
        case 'SP/APP/INITIALIZED_SUCCES': return { ...state, initialized: true }
        case 'SP/APP/SET_FIREBASE': return { ...state, firebaseBackend: action.firebase }
        case 'SP/APP/SET_STATUS': return { ...state, status: action.status }
        default: return state
    }

}



export default app