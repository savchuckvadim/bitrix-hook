import { Button, Col, Dropdown, DropdownItem, DropdownMenu, DropdownToggle, Input, Label } from "reactstrap"
import { EntityFormField } from "../../../../../types/entity/entity-types";
import { FormikProps } from "formik";
import { GetInitialRelationFunction } from "../../../../../store/april/entity/entity-reducer";


type DynamicInputProps = {
    field: EntityFormField
    // validation: any
    // groupName: string

    // relationIndex: number
    // isFromRelation: boolean
    // isRelation: boolean
    // isEntitiesGroup: boolean
    // fieldIndex: number
    // relationApiName: undefined | string
    handleChange: (e:any) => void
    // getInitialRelationEntity: GetInitialRelationFunction
    // addRelation: (groupName: string, relationIndex: number) => void
    // handleFileChange: (event: any, inputName: string, validation: any) => void

}
const DynamicInputs = ({
    field,
    handleChange
    // handleFileChange, getInitialRelationEntity, addRelation
}: DynamicInputProps) => {

    // string | text | data | img | entity
    let input = <div></div>
    let width = 12


    // const getRelationFieldName = (name: string) => `relations.${relationApiName}.${relationIndex}.${name}`
    // //   `relations.${name}`
    // // `relations.${relationApiName}.${relationIndex}.${name}`
    // const fieldFormName = relationApiName && (isRelation || isFromRelation) ? getRelationFieldName(field['apiName']) : field['apiName']


    switch (field.type) {

        case 'string':

            input = <div> <textarea
                // type={'text'}
                className="form-control"
                id="horizontal-firstname-Input"
                placeholder={field['title']}
                name={field.title}
                onChange={handleChange}
            // onBlur={validation.handleBlur}
            // value={typeof field.initialValue == 'string' ? validation.values[field.initialValue] : ""}

            ></textarea>

            </div>
            break;



        case 'select':


            input = <div className="mb-3">
                <label className="col-md-2 col-form-label">Select {field.title}</label>
                <div className="col-md-10">
                    <select
                        name={field.title}
                        onChange={handleChange}
                        className="form-control"
                    >
                        {field.items?.map(item => (
                            <option value={item.id}>{item.title}</option>

                        ))}
                    </select>
                </div>



            </div >
            break;

        case 'boolean':
            
            input = <div>  <div className="form-check form-check-right mb-3">
                <input
                    type="checkbox"
                    className="form-check-input"
                    id="CustomCheck1"
                    name={field.title}
                    onClick={(e) =>  handleChange(e)}
                    // value={field.value}
                    checked={Boolean(field.value)}
                />
                <label
                    className="form-check-label"
                >
                    {field['title']}
                </label>
            </div>

            </div>
            break;




        default:

            return input = <div> <textarea
                // type={'text'}
                className="form-control"
                id="horizontal-firstname-Input"
                placeholder={field['title']}
                name={field.title}
                onChange={handleChange}
            // onBlur={validation.handleBlur}
            // value={typeof field.initialValue == 'string' ? validation.values[field.initialValue] : ""}

            ></textarea>

            </div>
    }




    return (
        <Col sm={width}>
            {input}
        </Col>
    )
}

export default DynamicInputs