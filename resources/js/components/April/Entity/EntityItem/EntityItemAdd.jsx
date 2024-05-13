import React from "react";

import * as Yup from "yup";
import {
    Card,
    Col,
    Container,
    Row,
    CardBody,
    CardTitle,
    Label,
    Button,
    Form,
    Input,

} from "reactstrap";

import EntityItemDynamicInput from "./Item/FieldItemDynamicInputs";
import RelationAdd from "./Item/RelationAdd";
import { getFormik } from "../../../../utils/entity-utils/form-util";


const EntityItemAdd = ({
    // validation,
    router, creating, relation, entityName, itemUrl,
    isFromRelation = false, relationIndex,
    setOrupdateEntityItem, getInitialRelationEntity, setRelation, addRelation,
    // handleFileChange,
}) => {

    //meta title
    document.title = entityName + " | Skote React + Laravel 10 Admin And Dashboard Template";

    const validation = getFormik(router, creating, itemUrl, null, setOrupdateEntityItem)

    const handleFileChange = (event, inputName, formik) => {

        formik.setFieldValue(inputName, event.target.files);
    };
    

    


    const getItems = (creatingEntity) => {
        let result = []
        
        creatingEntity.groups.forEach((group, index) => {

            const isEntitiesGroup = group.type === 'entities'
            const fields = group.fields
            const relations = group.relations

            result.push(
                <div>
                    <h4>{group.groupName}</h4>

                    {fields.map(field => {
                        if(field.type === 'img'){
                            

                        }
                        
                        return (
                            <Row className="mb-4">
                                <Label
                                    htmlFor="horizontal-firstname-Input"
                                    className="col-sm-12 col-form-label"
                                >
                                    <Col sm={12}>
                                        <EntityItemDynamicInput
                                            field={field}
                                            isRelation={false}
                                            fieldIndex={index}
                                            relationIndex={creating.relationIndex}
                                            relationApiName={creatingEntity.apiName}
                                            groupName={group.groupName}
                                            isEntitiesGroup={isEntitiesGroup}
                                            validation={validation}
                                            isFromRelation={isFromRelation}
                                            getInitialRelationEntity={getInitialRelationEntity}
                                            addRelation={addRelation}
                                            handleFileChange={handleFileChange}

                                        />

                                    </Col>
                                </Label>
                            </Row>
                        )
                    })}
                    {/* {relations.map((relation, relationIndex) => {
                        return relation.groups.map(relationGroup => {
                            let firstField = relationGroup.fields[0]
                            
                            return <Row className="mb-4">
                                <Label
                                    htmlFor="horizontal-firstname-Input"
                                    className="col-sm-12 col-form-label"
                                >
                                    <Col sm={12}>
                                        <EntityItemDynamicInput
                                            field={firstField}
                                            fieldIndex={index}
                                            groupName={group.groupName}
                                            relationIndex={relationIndex}
                                            relationApiName={relation.apiName}
                                            isEntitiesGroup={isEntitiesGroup}
                                            validation={validation}
                                            isRelation={true}
                                            isFromRelation={isFromRelation}
                                            getInitialRelationEntity={getInitialRelationEntity}
                                            addRelation={addRelation}
                                            handleFileChange={handleFileChange}

                                        />

                                    </Col>
                                </Label>
                            </Row>
                        })

                    })} */}

                </div>

            )

        })


        return result
    }
    console.log(validation.values)
    const items = creating.formData && getItems(creating.formData)
    
    return (
        <React.Fragment>
           
            <Row>
                <Col xl={6}>
                    <Form

                        onSubmit={validation.handleSubmit}>
                        {items}
                        <div>
                            <Button color="primary" type="submit">
                                Submit form
                            </Button>
                        </div>

                    </Form>


                </Col>
            </Row>

        </React.Fragment>
    )
}

export default EntityItemAdd