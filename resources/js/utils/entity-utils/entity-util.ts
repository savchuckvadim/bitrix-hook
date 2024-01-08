import { EntityFormField, FormikInitialValues, InitialEntityData } from "../../types/entity/entity-types";


export const getInitialValues = (initialData: InitialEntityData) => {

    let resultInitialData = {

    } as FormikInitialValues

    initialData && initialData.groups && initialData.groups.map(group => {
        let itemsFields = [...group.fields]


        group.fields && group.fields.length && group.fields.map(field => {

            if (field.type !== 'entity') {
                resultInitialData[field.apiName] = ''
            }

        })
        resultInitialData.relations = {}

        group.relations && group.relations.length && group.relations.map((relation, relationIndex) => {



            relation.groups.map(rltnGroup => {
                resultInitialData.relations[relation.apiName] = []
                // resultInitialData.relations[rltnGroup.groupName][relation.apiName] = []

                resultInitialData.relations[relation.apiName][relationIndex] = {}
                rltnGroup.fields && rltnGroup.fields.length && rltnGroup.fields.map(fld => {
                    resultInitialData.relations[relation.apiName][relationIndex][fld.apiName] = ''


                })

                // resultInitialData.relations = [...resultInitialData.relations]
                // resultInitialData.relations[relationIndex][relation.apiName] = getInitialValues(relation)


            })
        })

    })

    
    return resultInitialData


}

// export const getInitialValuesFromEntity = (entity: { [key: string]: any }) => {

//     let resultInitialData = {

//     } as FormikInitialValues

//     for (const key in entity) {
//         if (entity[key] !== null) {
//             if (typeof entity[key] === 'object') {

//             } else if (Array.isArray(entity[key])) {

//             } else {
//                 resultInitialData[key] = entity[key]
//             }
//         } else {

//             resultInitialData[key] = entity[key]
//         }

//     }
//     initialData && initialData.groups && initialData.groups.map(group => {
//         let itemsFields = [...group.fields]


//         group.fields && group.fields.length && group.fields.map(field => {

//             if (field.type !== 'entity') {
//                 resultInitialData[field.apiName] = ''
//             }

//         })
//         resultInitialData.relations = {}

//         group.relations && group.relations.length && group.relations.map((relation, relationIndex) => {



//             relation.groups.map(rltnGroup => {
//                 resultInitialData.relations[relation.apiName] = []
//                 // resultInitialData.relations[rltnGroup.groupName][relation.apiName] = []

//                 resultInitialData.relations[relation.apiName][relationIndex] = {}
//                 rltnGroup.fields && rltnGroup.fields.length && rltnGroup.fields.map(fld => {
//                     resultInitialData.relations[relation.apiName][relationIndex][fld.apiName] = ''


//                 })

//                 // resultInitialData.relations = [...resultInitialData.relations]
//                 // resultInitialData.relations[relationIndex][relation.apiName] = getInitialValues(relation)


//             })
//         })

//     })

//     
//     return resultInitialData


// }