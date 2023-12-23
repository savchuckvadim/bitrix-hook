import { Button, Col, Dropdown, DropdownItem, DropdownMenu, DropdownToggle, Input, Label } from "reactstrap"
import { EntityFormField } from "../../../../../types/entity/entity-types";
import { FormikProps } from "formik";
import { GetInitialRelationFunction } from "../../../../../store/april/entity/entity-reducer";
import { useState } from "react";

type DynamicInputProps = {
    field: EntityFormField
    validation: any
    groupName: string
    relationIndex: number
    isRelation: boolean
    getInitialRelationEntity: GetInitialRelationFunction

}
const EntityItemDynamicInput = ({ field, groupName, validation, isRelation, relationIndex, getInitialRelationEntity }: DynamicInputProps) => {

    // string | text | data | img | entity
    let input = <div></div>
    let width = 12
    const [singlebtn, setSinglebtn] = useState(false)
    const getRelationFieldName = (name: string) => `field.${name}`
    const fieldFormName = isRelation ? getRelationFieldName(field['apiName']) : field['apiName']
    switch (field.type) {

        case 'string':

            input = <div> <Input
                type={'text'}
                className="form-control"
                id="horizontal-firstname-Input"
                placeholder={field['title']}
                name={fieldFormName}
                onChange={validation.handleChange}
                onBlur={validation.handleBlur}
                value={typeof field.initialValue == 'string' ? validation.values[field.initialValue] : ""}

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
                    onClick={() => getInitialRelationEntity(groupName, field.id, null)}
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

            input = <div> <Dropdown
                isOpen={singlebtn}
                toggle={() => setSinglebtn(!singlebtn)}
            >
                <DropdownToggle className="btn btn-info" caret>
                    {field['title']}
                    <i className="mdi mdi-chevron-down" />
                </DropdownToggle>
                <DropdownMenu>
                    {field.items && field.items.length && field.items.map(item => (
                        <DropdownItem>{item.title}</DropdownItem>
                    ))}
                </DropdownMenu>
            </Dropdown>
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

    return (
        <Col sm={width}>
            {input}
        </Col>
    )
}

export default EntityItemDynamicInput