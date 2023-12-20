import { onlineAPI } from "../helpers/april-online/online-api";
import { AppStateType } from "../store";
import { EntityField, EntityParameter, TemplateAddData } from "../types/entity/entity-types";




export const getInitialTemplateData = async () => {

    let fields = []
    fields = await onlineAPI.getCollection(`fields/general`, 'get', 'fields')
    fields = (fields && fields.length)
        ? fields.map((f: EntityField) => { f.value = true; return f })
        : []

    let parameters = [
        {
            name: 'name',
            type: 'string',
            value: null,
            items: []
        },
        {
            name: 'domain',
            type: 'string',
            value: null,
            items: []
        },
        {
            name: 'type',       //offer | invoice | contract
            type: 'string',
            value: null,
            items: []
        },
        {
            name: 'file',       //offer | invoice | contract
            type: 'file',
            value: null,
            items: []
        },
    ] as Array<EntityParameter>

    const data = {
        parameters,
        fields
    } as TemplateAddData

    return data
}


export const getDataForSetTemplate = (state: AppStateType, values: Array<any>) => {
    const fieldIds = [] as Array<number>
    //@ts-ignore
    let statefields = state.entity.adding.fields
    //@ts-ignore
    statefields && statefields.length && statefields.forEach(f => {

        for (const key in values) {
            //@ts-ignore
            // fields.push(values.fields[key])
            if (key === f.name && values[key] === true) {

                fieldIds.push(f.id)
            }
        }
    });

    const formData = new FormData();
    //@ts-ignore
    formData.append('file', values.file);
    formData.append('fieldIds', JSON.stringify(fieldIds));
    //@ts-ignore
    formData.append('domain', JSON.stringify(values.domain));
    //@ts-ignore
    formData.append('name', JSON.stringify(values.name));
    //@ts-ignore
    formData.append('type', JSON.stringify(values.type));



    return formData
}






//TEMPLATE-FIELDS
export const getInitialTemplateFieldData = async () => {

    let fetchedParameters = await onlineAPI.getCollection(`initial/field`, 'get', 'initialField')

    let parameters = fetchedParameters as Array<EntityParameter>

    const data = {
        parameters,
        fields: []
    } as TemplateAddData

    return data
}

export const getDataForSetTField = (values: Array<any>, templateId: string | null) => {

    const data = {
        templateId,
        field: values
    }

    return data
}
