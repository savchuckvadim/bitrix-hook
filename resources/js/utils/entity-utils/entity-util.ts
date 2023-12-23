import { FormikInitialValues, InitialEntityData } from "../../types/entity/entity-types";


export const getInitialValues = (initialData: InitialEntityData) => {

    let resultInitialData = {} as FormikInitialValues

    initialData.map(group => {

        group.fields && group.fields.map(field => {

            if (field.type !== 'entity') {
                resultInitialData[field.apiName] = ''
            } else {
                if (typeof field.initialValue !== 'string') {
                    field.initialValue &&
                        field.initialValue.length &&
                        field.initialValue.forEach(relationGroup => {
                            resultInitialData[field.apiName] = [{    }]
                            relationGroup.fields.forEach(relationField => {
                                
                                resultInitialData[field.apiName][0][relationField.apiName] = ''
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