
import axios from 'axios';


export const api = axios.create({
    // withCredentials: false,
    baseURL: 'https://script.google.com/macros/s/',

    headers: {
        "Content-Type": "text/plain;charset=utf-8",
        'Access-Control-Allow-Origin' : '*',
    },
    // withCredentials:false

})
// @ts-ignore
api.defaults.redirect = "follow";

// type FieldsDataType = {
//     fields: Array<FieldType>
// }


// type ComplectsDataType = {
//     complects: Array<ComplectType>
// }

// type SuppliesDataType = {
//     supplies: Array<SupplyType>
// }

// type RegionsDataType = {
//     regions: Array<RegionType>
// }

// type ConsaltingDataType = {
//     consalting: Array<ConsaltingType>
// }

// export type LegalTechDataType = {
//     legalTech: {
//         packages: Array<LegalTechType>
//         services: Array<LegalTechType>
//     }

// }
    

type ContractsDataType = {
    // contracts: Array<ContractType>

}

export const googleAPI = {
    async get(token = null) {
        let googleToken = token || ''
        try {
            const res = await api.get(`${googleToken}/exec`);

            return res.data;
        } catch (error) {
            console.error(error);
        }
    },


}