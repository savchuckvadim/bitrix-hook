import axios from "axios"

export const api = axios.create({
    withCredentials: true,
    baseURL: 'https://april-server.ru/api',
    headers: {
        'content-type': 'application/json',
        'accept': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'X-Requested-With': 'XMLHttpRequest'
    },

})
//@ts-ignore
api.defaults.redirect = "follow";

export const addPlacementApp = async (domain: string, key: string, hook: string) => {
    try {
        let res = await api.post("/client", {
            domain, key, hook
        });

        if (res && res.data) {
            return res.data
        }

    } catch (error) {
        //@ts-ignore
        console.log(error?.message)
        return error
    }


}

export const updatePlacements = async (type: DEPLOY_TYPE) => {
    
    try {
        let res = await api.post(`/refresh`, {
            type
        });
        
        if (res && res.data) {
            return res.data
        }

    } catch (error) {
        
        console.log(error)
        return error
    }


}


export enum ResultCodesEnum {
    Error = 1,
    Success = 0
}

export enum DEPLOY_TYPE {
    CLIENT = 'client',
    PUBLIC = 'public',
    TEST = 'test',
    DEV = 'dev'
} 