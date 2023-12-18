// import { stopSubmit } from "redux-form"
import { InferActionsTypes, ThunkType } from "../.."
// import { authAPI } from "../../../services/auth-api";
// import { PreloaderCodesEnum } from "../../../types/types"
// import { InferActionsTypes, ThunkType } from "../../store"
// import { inProgress } from "../preloader/preloader-reducer"
// import { authApi } from "../../../services/firebase-api/firebase-api";
// import { User } from "firebase/auth";

//TYPES
type AuthStateType = typeof initialState
type AuthThunkType = ThunkType<SetAuthUserDataType>
type SetAuthUserDataType = InferActionsTypes<typeof actions>

//STATE
let initialState = {
    isAuth: false as boolean,
    authUser: null as null

}



//ACION CREATORS
const actions = {
    setAuthUserData: (authUser:  null, isAuth: boolean = false) =>
        ({ type: 'SP/AUTH/SET_USER_DATA', authUser, isAuth } as const)
}


//THUNKS
export const getAuthApp = (): AuthThunkType => async (dispatch) => {

    // dispatch(inProgress(true, PreloaderCodesEnum.Global))

    // let response = await authAPI.getAuthAppUser()
   
  
    // let authUser = await authApi.getAuth()
    
   

    // if (authUser && (authUser.email === 'savchuckvadim@gmail.com' || authUser.email === 'laravelsamvel@gmail.com')) {
        
    //     dispatch(actions.setAuthUserData(authUser, true))
    //     // await socket.reconnect(authUser.id, dispatch)


    // } else {
    //     dispatch(actions.setAuthUserData(null, false))
    // }
    // dispatch(inProgress(false, PreloaderCodesEnum.Global))

}
export const login = (email: string, password: string): AuthThunkType => async (dispatch) => {
    // dispatch(inProgress(true, PreloaderCodesEnum.Global))

    // await authAPI.login(email, password)
    //     .then(res => {

    //         dispatch(getAuthApp())

    //     })
    //     .catch((e) => {
    //         let message = 'Email or Password was wrong !'

    //         let action = stopSubmit('login', {
    //             _error: message
    //         })
    //         dispatch(action)
    //         dispatch(inProgress(false, PreloaderCodesEnum.Global))
    //     })



}
export const logout = (): AuthThunkType => async (dispatch) => {
    // dispatch(inProgress(true, PreloaderCodesEnum.Global))
    // authAPI.logout()
    //     .then(res => {
    //         dispatch(actions.setAuthUserData(null, false))

    //     })
    // dispatch(inProgress(false, PreloaderCodesEnum.Global))
}

// export const setNewUser = ( //registration
    // name: string, surname: string, email: string,
    // password: string, password_confirmation: string) => async (dispatch: any) => {

    //     dispatch(inProgress(true, PreloaderCodesEnum.Global))


    //     try {
    //         let res = await authAPI.register(name, surname, email, password, password_confirmation)
    //         if (res.statusText === 'Created') {
    //             // dispatch(registrationSuccess())
    //             dispatch(getAuthApp())           //from auth reducer

    //         } else {

    //             if (res.data.error) {
    //                 alert(res.data.error)

    //             }
    //         }
    //         // dispatch(inProgress(false, PreloaderCodesEnum.Global))
    //     } catch (error) {

    //         dispatch(inProgress(false, PreloaderCodesEnum.Global))  //from preloader-reducer
    //     }


    // }

//REDUCER
const authReducer = (state: AuthStateType = initialState, action: SetAuthUserDataType): AuthStateType => {
    let result = state

    switch (action.type) {
        case "SP/AUTH/SET_USER_DATA":
            
            result = { ...state, }
            result.isAuth = action.isAuth
            result.authUser = action.authUser //запоминаем аутентифицированного пользователя в state
            return result


        default:
            return result
    }

}
export default authReducer