import { Button, Col, Dropdown, DropdownItem, DropdownMenu, DropdownToggle, Input, Label } from "reactstrap"
import { EntityFormField } from "../../../../../types/entity/entity-types";
import { FormikProps } from "formik";
import { GetInitialRelationFunction } from "../../../../../store/april/entity/entity-reducer";
import { useState } from "react";
import Select from "react-select";
type DynamicInputProps = {
    field: EntityFormField
    validation: any
    groupName: string

    relationIndex: number
    isFromRelation: boolean
    isRelation: boolean
    isEntitiesGroup: boolean
    fieldIndex: number
    getInitialRelationEntity: GetInitialRelationFunction
    addRelation: (groupName: string, relationIndex: number) => void

}
const EntityItemDynamicInput = ({
    field, fieldIndex, groupName, validation, isRelation, isFromRelation,
    relationIndex, isEntitiesGroup,
    getInitialRelationEntity, addRelation
}: DynamicInputProps) => {
    const [selectedGroup, setselectedGroup] = useState(null);
    // string | text | data | img | entity
    let input = <div></div>
    let width = 12
    const [singlebtn, setSinglebtn] = useState(false)
    function handleSelectGroup(selectedGroup: any) {
        setselectedGroup(selectedGroup);
    }
    const getRelationFieldName = (name: string) => `relations.${groupName}.${relationIndex}.${name}`
    const fieldFormName = (isRelation || isFromRelation) ? getRelationFieldName(field['apiName']) : field['apiName']

    if (!isRelation) {
        switch (field.type) {

            case 'string':
                debugger
                input = <div> <Input
                    type={'text'}
                    className="form-control"
                    id="horizontal-firstname-Input"
                    placeholder={field['title']}
                    name={fieldFormName}
                    onChange={validation.handleChange}
                    onBlur={validation.handleBlur}
                // value={typeof field.initialValue == 'string' ? validation.values[field.initialValue] : ""}

                />
                    {field.isCanAddField && <Button

                        type={'button'}
                        className="ml-30 btn"
                        color="success"

                        name={fieldFormName}
                        onChange={validation.handleChange}
                        onBlur={validation.handleBlur}
                        value={"Добавить"}

                    >+ Добавить</Button>}
                </div>
                break;

            case 'entity':

                input = <div>
                    <Label className="mr-10 btn">{field['title']}</Label>
                    <Button

                        type={'button'}
                        className="ml-30 btn"
                        color="primary"
                        onClick={() => getInitialRelationEntity(groupName, 0)}
                        name={fieldFormName}
                        onChange={validation.handleChange}
                        onBlur={validation.handleBlur}
                        value={"Редактировать"}

                    >Редактировать</Button>
                    {field.isCanAddField && <Button

                        type={'button'}
                        className="ml-30 btn"
                        color="success"

                        name={field['apiName']}
                        onChange={validation.handleChange}
                        onBlur={validation.handleBlur}
                        value={"Добавить"}

                    >+ Добавить</Button>}
                </div>
                break;

            case 'select':
                debugger
                const optionGroup = [
                    {
                        label: "Picnic",
                        options: field.items?.map(item => (
                            { label: item.title, value: item.name }
                        ))
                    },

                ];

                //@ts-ignore
                const value = optionGroup[0].options.find(option => option === validation.values[fieldFormName])
                let tets = validation.initialValues[fieldFormName]
                debugger
                input = <div className="mb-3">
                    <Label>Single Select</Label>
                    <Select
                        name={fieldFormName}
                        // type={'select'}
                        // value={selectedGroup}
                        // onChange={() => {
                        //     handleSelectGroup();
                        // }}
                        //@ts-ignore
                        // value={value}

                        onChange={(option) => {
                            debugger
                            //@ts-ignore
                            validation.setFieldValue(fieldFormName, option.value)
                        }}
                        onBlur={() => validation.setFieldTouched(fieldFormName, true)}
                        // onChange={validation.handleChange}
                        // onBlur={validation.handleBlur}
                        options={optionGroup}
                        className="select2-selection"
                    />

                    {
                        field.isCanAddField && <Button

                            type={'button'}
                            className="ml-30 btn"
                            color="success"

                            name={field['apiName']}
                            onChange={validation.handleChange}
                            onBlur={validation.handleBlur}
                            value={"Добавить"}

                        >+ Добавить</Button>
                    }
                </div >
                break;

            case 'boolean':

                input = <div>  <div className="form-check form-check-right mb-3">
                    <input
                        type="checkbox"
                        className="form-check-input"
                        id="CustomCheck1"
                    />
                    <label
                        className="form-check-label"
                    >
                        {field['title']}
                    </label>
                </div>
                    {field.isCanAddField && <Button

                        type={'button'}
                        className="ml-30 btn"
                        color="success"

                        name={field['apiName']}
                        onChange={validation.handleChange}
                        onBlur={validation.handleBlur}
                        value={"Добавить"}

                    >+ Добавить</Button>}
                </div>
                break;


            case 'img':

                input = <div className="mt-3">
                    <Label htmlFor="formFile" className="form-label">{field['title']}</Label>
                    <Input className="form-control" type="file" id="formFile" />
                </div>



                break;

            default:

                return input = <div> <Input
                    type={'text'}
                    className="form-control"
                    id="horizontal-firstname-Input"
                    placeholder={field['title']}
                    name={field['apiName']}
                    onChange={validation.handleChange}
                    onBlur={validation.handleBlur}
                    value={typeof field.initialValue == 'string' ? validation.values[field.initialValue] : ""}

                />
                    {<Button

                        type={'button'}
                        className="ml-30 btn"
                        color="success"

                        name={field['apiName']}
                        onChange={validation.handleChange}
                        onBlur={validation.handleBlur}
                        value={"Добавить"}

                    >+ Добавить</Button>}
                </div>
        }

    } else {

        input = <div>
            <Label className="mr-10 btn">{field['apiName']}</Label>
            <Button

                type={'button'}
                className="ml-30 btn"
                color="primary"
                onClick={() => getInitialRelationEntity(groupName, relationIndex)}
                name={fieldFormName}
                onChange={validation.handleChange}
                onBlur={validation.handleBlur}
                value={"Редактировать"}

            >Редактировать</Button>
            {<Button

                type={'button'}
                className="ml-30 btn"
                color="success"
                onClick={() => addRelation(groupName, relationIndex)}
                name={field['apiName']}
                onChange={validation.handleChange}
                onBlur={validation.handleBlur}
                value={"Добавить"}

            >+ Добавить</Button>}
        </div>
    }


    return (
        <Col sm={width}>
            {input}
        </Col>
    )
}

export default EntityItemDynamicInput