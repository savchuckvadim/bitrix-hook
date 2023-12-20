import axios from "axios";


const IS_APRIL_DEV = false


export const online = axios.create({
    withCredentials: true,
    // baseURL: 'https://april-online.ru/api',
    baseURL:'https://april-hook.ru/api',
    headers: {
        'content-type': 'application/json',
        'accept': 'application/json',
        'Access-Control-Allow-Origin': '*',
        'X-Requested-With': 'XMLHttpRequest'
    },

})



export const hookAPI = {


   

    getCollection: async (url, method, collectionName, data = null) => {
        let result = null
        
        try {
            const response = !data 
            ? await online[method](url)
            : await online[method](url, data)


            if (response) {
                console.log(response)
                
                if (response.data && response.data.resultCode === 0) {
                    if (response.data[collectionName]) {
                        
                        if (response.data.isCollection) {
                             
                            result = response.data[collectionName].data
                        } else {
                            
                            result = response.data[collectionName]
                        }

                    }

                }

                
                return result

            }

        } catch (error) {
            console.log(error)
            
            
            return result
        }

    },
    setCollection: async (name, items) => {
        let result = null
        try {
            const data = {
                [name]: items
            }
            const response = await online.post(name, data)
            if (response) {
                if (response.data.resultCode === 0) {
                    result = response.data.data
                } else {
                    console.log(response.data.message)
                }
            }
            return result
        } catch (error) {
            console.log('error')
            return result
        }

    },
    service: async (url, method, model, data) => {
        let result = null
        
        try {
            
            const response = await online[method](url, data)
            debugger
            if (response && response.data) {
                if (response.data.resultCode === 0) {
                    result = response.data[model]
                } else {
                    console.log(response.data.message)
                }
            }

            return result
        } catch (error) {
            console.log('error')
            
            return result
        }
    }

}