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
                resultInitialData.relations[rltnGroup.groupName] = {}
                resultInitialData.relations[rltnGroup.groupName][relation.apiName] = []
                
                resultInitialData.relations[rltnGroup.groupName][relation.apiName][relationIndex] = {}
                rltnGroup.fields && rltnGroup.fields.length && rltnGroup.fields.map(fld => {
                    resultInitialData.relations[rltnGroup.groupName][relation.apiName][relationIndex][fld.apiName] = ''


                })
                
                // resultInitialData.relations = [...resultInitialData.relations]
                // resultInitialData.relations[relationIndex][relation.apiName] = getInitialValues(relation)


            })
        })

    })

    
    return resultInitialData


}