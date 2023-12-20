// import { stopSubmit } from "redux-form"
import { toast } from "react-toastify"
import { InferActionsTypes, ThunkType } from "../.."
import { FirebaseAuthBackendInstanceType } from "../../../helpers/firebase/types"
import { loginUser } from "../../auth/login/actions"
import { showToastWithPromise } from "../../../utils/toast-util"
import { getProfile } from "../../../utils/auth-util"
import { Profile } from "../../../types/profile/profile-type"

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
    authUser: null as Profile | null

}



//ACION CREATORS
const actions = {
    setAuthUserData: (authUser: null | Profile, isAuth: boolean = false) =>
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


export const initializeGoogleAuth = (history: any): AuthThunkType => async (dispatch, getState) => {
    const firebaseBackend = getState().app.firebaseBackend
    
    if (firebaseBackend) {
        const fireBaseUser = await firebaseBackend.socialLoginUser('google')
        //@ts-ignore
        const email = fireBaseUser.email as string
        const profile = await getProfile(email, firebaseBackend, history, dispatch, actions.setAuthUserData)
        
      
    } else {
        alert('something wrong with firebase api');
    }

}

export const login = (user: any, history: any): AuthThunkType => async (dispatch, getState) => {

    const firebaseBackend = getState().app.firebaseBackend
    
    if (firebaseBackend) {
        const fireBaseUser = await firebaseBackend.loginUser(user.email, user.password)
        
        //@ts-ignore
        const firebaseEmail = fireBaseUser.email
        const profile = await getProfile(firebaseEmail, firebaseBackend, history, dispatch, actions.setAuthUserData)
        
    } else {
        alert('something wrong with firebase api')
    }

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
const auth = (state: AuthStateType = initialState, action: SetAuthUserDataType): AuthStateType => {
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
export default auth