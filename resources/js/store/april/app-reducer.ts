
import { InferActionsTypes, ThunkType } from ".."
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
   debugger
    fireBack && dispatch(appActions.setFirebase(fireBack))

    dispatch(appActions.initializedSuccess())



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