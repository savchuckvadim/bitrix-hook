

export const tfieldsSetToFirebase = (fields, items) => {
    let result =
        fields.map(field => {
            
            let resultField = { ...field }
            if (field.type === 'array') {

                resultField = {
                    ...field,
                    items: items.filter(item => item.fieldNumber === field.number)
                }
            }
            return resultField
        })

    return result

}