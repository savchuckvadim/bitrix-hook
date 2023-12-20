

export type TemplateInitialAddData = {
    domain: string
    type: 'offer' | 'invoice' | 'contract' | 'other'
    name: string
}

export type TemplateField = {
    id: number
    number: number
    items: Array<number> // TODO ItemType
    name: string
    code: string
    type: string

    value: string | null
    description: string | null
    bitixId: string | null
    bitrixTemplateId: string | null

    isGeneral: boolean
    isDefault: boolean
    isRequired: boolean
    isActive: boolean
    isPlural: boolean

}

export type Template = {
    id: number
    domain: string
    type: 'offer' | 'invoice' | 'contract' | 'other'
    name: string
    fields: Array<TemplateField>
    link: string | null
}


export type SetUpdatingTemplate = {
    domain: string
    fieldIds:Array<number>
    file:string

}