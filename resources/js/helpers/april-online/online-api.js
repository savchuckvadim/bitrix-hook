import axios from "axios";


const IS_APRIL_DEV = false


export const online = axios.create({
    withCredentials: true,
    // baseURL: 'https://april-online.ru/api',
    baseURL:
    IS_APRIL_DEV 
    ? 'http://localhost:8000/api'
    : 'https://april-online.ru/api',
    headers: {
        'content-type': 'application/json',
        'accept': 'application/json',
        // 'Access-Control-Allow-Origin': '*',
        'X-Requested-With': 'XMLHttpRequest'
    },

})


export const onlineOldAPI = {


    uploadGeneralDescriptionFile: async (formData) => {
        let result = null
        try {
            const data = await online.post('upload/description/general', formData)
            result = data.data
            return result
        } catch (error) {
            console.log(error)
            return result
        }

    },

    uploadPortalTemplateFile: async (formData) => {


        let result = null
        try {
            const data = await online.post('portal/template', formData)
            result = data.data

            return result
        } catch (error) {
            console.log(error)
            return result
        }

    },

    processFile: async (fileName, fields) => {

        try {
            const response = await online.post('createAprilTemplate', {
                fileName, fields
            })

            if (response && response.data && response.data.resultCode === 0) {
                let link = response.data.file
                // setUpdatedFile(link)
                window.open(link, "_blank")
            }
        } catch (err) {
            console.log(err)
            err && err.message && console.log(err.message)
        }

    },

    getDescription: async (domain, userId, complect, infoblocks) => {
        let result = null
        try {
            const response = await online.post('getDescription', {
                domain, userId, complect, infoblocks
            })
            if (response && response.data && response.data.resultCode === 0) {
                let link = response.data.file
                window.open(link, "_blank")
            }

        } catch (error) {
            console.log(error)
            return result
        }

    },

    getPortal: async (domain) => {
        let result = null
        try {
            const response = await online.post(`getportal`, {
                domain
            })
            if (response) {
                console.log(response)

            }

        } catch (error) {
            console.log(error)
            return result
        }

    },
    setPortal: async (
        number,
        domain,
        key,   //placement key 
        clientId,  //from hook 
        secret,    //from hook 
        hook //hook url

    ) => {

        // $domain  = $request->input('domain');
        // $key = $request->input('key'); //placement key
        // $clientId  = $request->input('clientId');
        // $secret = $request->input('secret');
        // $hook = $request->input('hook');
        // return Portal::setPortal($domain, $key, $clientId, $secret, $hook);

        let result = null
        try {
            let data = {
                number,
                domain: domain,
                key: key,
                clientId: clientId,
                clientSecret: secret,
                hook: hook
            }
            console.log(data)
            const response = await online.post(`portal`, data)

            if (response) {
                console.log(response)

            }

        } catch (error) {
            console.log(error)
            return result
        }

    },

    getPortals: async () => {
        let result = null
        try {
            const response = await online.get(`portals`)
            if (response) {
                console.log(response)

            }

        } catch (error) {
            console.log(error)
            return result
        }

    },

    getDeals: async (prop, value) => {

        let result = null
        try {
            const response = await online.post(`getdeals`, {
                parameter: prop,
                value
            })


            if (response) {
                console.log(response)

            }

        } catch (error) {
            console.log(error)
            return result
        }
    },

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

export const onlineAPI = {


   

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
        debugger
        try {
            
            const response = await online[method](url, data)
            debugger
            if (response && response.data) {
                if (response.data.resultCode === 0) {
                    if(response.data.data){
                        result = response.data.data[model]
                    }else{
                        result = response.data[model]
                    }
                    
                } else {
                    debugger
                    console.log(response.data.message)
                }
            }

            return result
        } catch (error) {
            debugger
            console.log(error)
            debugger
            return result
        }
    }

}