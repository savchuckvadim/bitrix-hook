

export type InfoGroupType = {
    number: number
    name: string
    title: string
    description: string
    descriptionForSale: string
    shortDescription: string
    type: 'infoblocks' | 'free' | 'er' | 'consalting' | 'lt' | 'star'
    productType: InfoGroupProductTypesEnum
}



enum InfoGroupProductTypesEnum {
    GARANT = 'garant',
    LT = 'lt',
    CONSALTING = 'consalting',
    STAR = 'star'
}

