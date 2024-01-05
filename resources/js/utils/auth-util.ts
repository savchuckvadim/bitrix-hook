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
            const logged_user = {
                login: true,
                user_id: 0,
                name: profile.name,
            }
            sessionStorage.setItem('user', JSON.stringify(logged_user));
            sessionStorage.setItem('authUser', JSON.stringify(logged_user));
            result = profile

            


            dispatch(setAuthUserData(profile))
        }

        history('/dashboard');
    } else {
        
        await showToastWithPromise("Вы не Admin", 'error', {
            position: "top-right",
            autoClose: 3000
        })
    }


    return result

}