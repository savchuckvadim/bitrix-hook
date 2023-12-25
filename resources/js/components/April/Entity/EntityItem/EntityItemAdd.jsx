import React, { useState } from "react";
import { useFormik } from "formik";
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

import Breadcrumb from "../../../Common/Breadcrumb";
import { getInitialValues } from "../../../../utils/entity-utils/entity-util";
import EntityItemDynamicInput from "./Item/ItemDynamicInputs";
import RelationAdd from "./Item/RelationAdd";


const EntityItemAdd = ({
    validation,
    router, creating, relation, entityName, itemUrl,
    isRelation = false,
    setOrupdateEntityItem, getInitialRelationEntity, setRelation
}) => {

    //meta title
    document.title = entityName + " | Skote React + Laravel 10 Admin And Dashboard Template";







    const getItems = (creatingEntity) => {
        let result = []

        creatingEntity.forEach((group, index) => {

            const items = group.type === 'entities' && group.fields.length < 1
                ? [group.initialField]
                : group.fields

            result.push(
                <div>
                    <h4>{group.groupName}</h4>

                    {items.map(field => {
                        return (
                            <Row className="mb-4">
                                <Label
                                    htmlFor="horizontal-firstname-Input"
                                    className="col-sm-12 col-form-label"
                                >
                                    <Col sm={12}>
                                        <EntityItemDynamicInput
                                            field={field}
                                            fieldIndex={index}
                                            groupName={group.groupName}
                                            validation={validation}
                                            isRelation={isRelation}
                                            getInitialRelationEntity={getInitialRelationEntity}

                                        />

                                    </Col>
                                </Label>
                            </Row>
                        )
                    })}

                </div>

            )

        })


        return result
    }
    console.log(validation.values)
    const items = creating.formData && getItems(creating.formData)
    return (
        <React.Fragment>
            {relation && <RelationAdd
                validation={validation}
                relation={relation}
                router={router}
                creating={relation.entity}

                entityName={entityName}
                itemUrl={itemUrl}
                setOrupdateEntityItem={setOrupdateEntityItem}
                getInitialRelationEntity={getInitialRelationEntity}
                setRelation={setRelation}

            />}
            {/* <div className="page-content"> */}
            {/* <Container fluid={true} className="mt-0">

                    <Breadcrumb title="Forms" breadcrumbItem="Form Layouts" /> */}
            <Row>


                <Col xl={6}>
                    {/* <Card>
                                <CardBody>
                                    <CardTitle className="mb-4">{entityName}</CardTitle> */}

                    <Form

                        onSubmit={validation.handleSubmit}>
                        {items}
                        <div>
                            <Button color="primary" type="submit">
                                Submit form
                            </Button>
                        </div>

                    </Form>

                    {/* </CardBody>
                            </Card> */}
                </Col>
            </Row>
            {/* </Container> */}
            {/* </div> */}
        </React.Fragment>
    )
}

export default EntityItemAdd