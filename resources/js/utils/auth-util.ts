import { Profile } from "../types/profile/profile-type";
import { showToastWithPromise } from "./toast-util";




export const getProfile = async (email: string, firebase: any, history: any, dispatch: any, setAuthUserData: any): Promise<Profile | null> => {
    let result = null
    
    if (email) {
       await showToastWithPromise("Вы успешно зарегестрированы", 'success', {
            position: "top-right",
            autoClose: 3000
        })

        const profile = await firebase.getDocByProp('profile', 'email', email) as Profile | null

        if (profile) {
            sessionStorage.setItem('user', JSON.stringify(profile));
            sessionStorage.setItem('authUser', JSON.stringify(profile));
            result = profile

            


            dispatch(setAuthUserData(profile))
        }

        history('/dashboard');
    } else {
        
        await showToastWithPromise("Вы не зарегестрированы", 'error', {
            position: "top-right",
            autoClose: 3000
        })
    }


    return result

}