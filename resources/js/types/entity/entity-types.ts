import { Template } from "./template-types"

type GeneralEntity = {

}
export type TemplateInitialAddData = {
    domain: string
    type: 'offer' | 'invoice' | 'contract' | 'other'
    name: string
}
export enum ENTITY_QUANTITY{

    ENTITY='entity',
    ENTITIES='entities'

}

export type EntityField = {
    id: number
    number: number
    items: Array<number> // TODO ItemType
    name: string
    code: string
    type: string

    value: string | null | boolean
    description: string | null
    bitixId: string | null
    bitrixTemplateId: string | null

    isGeneral: boolean
    isDefault: boolean
    isRequired: boolean
    isActive: boolean
    isPlural: boolean

}

export type TemplateAddData = {
    // id: number
    parameters: Array<EntityParameter>
    fields: Array<EntityField>
}

export type Entity = Template | TemplateAddData


export type EntityParameter = {
    // данные для того чтобы можно было запостить сущность
    // например domain или всякое такое
    name: string // 'domain'
    type: string // 'string'
    value: string | null | Array<string>
    items: Array<string>
}