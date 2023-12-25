import { FormikInitialValues, InitialEntityData } from "../../types/entity/entity-types";


export const getInitialValues = (initialData: InitialEntityData) => {

    let resultInitialData = {} as FormikInitialValues

    initialData.map(group => {
        const itemsFields = group.fields && group.fields.length
            ? group.fields
            : group.initialField && [group.initialField]


        itemsFields && itemsFields.length && itemsFields.map(field => {

            if (field.type !== 'entity') {
                resultInitialData[field.apiName] = ''
            } else {
                
                if (typeof field.initialValue !== 'string') {
                    
                    field.initialValue &&
                        field.initialValue.length &&
                        //@ts-ignore
                        field.initialValue.forEach(relationGroup => {

                            
                            resultInitialData[field.apiName] = [{}]
                            // resultInitialData[field.apiName] = {}
                            //@ts-ignore
                            relationGroup.fields.forEach(relationField => {


                                //TODO 
                                //refactoring на множество филдов-энтити - так как их может быть много
                                
                                resultInitialData[field.apiName][0][relationField.apiName] = ''
                                // resultInitialData[field.apiName][relationField.apiName] = ''
                            })
                        });
                }


            }

        })
        // group.fieldGroups && group.fieldGroups.map(fieldGroup => {
        //     fieldGroup.map(field => {

        //         resultInitialData[field.apiName] = ''
        //     })
        // })
        // group.relations && group.relations.map(field => {

        //     resultInitialData[field.apiName] = ''
        // })

    })
    
    return resultInitialData
}