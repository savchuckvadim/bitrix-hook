import React from "react";
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
import TypeEntityItemDynamicInput from "./Item/TypeItemDynamicInputs";
import { getFormik } from "../../../../utils/entity-utils/form-util";


const EntityItem = ({
    router,
    // validation,
    entity, entityName, itemUrl,
    setOrupdateEntityItem, deleteEntityItem }) => {

    //meta title
    document.title = entityName + " | Savchuk April + EntityItem Template";


    // Form validation 
    const validation = getFormik(router, null, itemUrl, entity, setOrupdateEntityItem)

    const getItems = (entity) => {
        let result = []

        for (const key in entity) {


            if (entity.hasOwnProperty(key)) {
                const value = entity[key];





                result.push(
                    <Row className="mb-4">
                        <Label
                            htmlFor="horizontal-firstname-Input"
                            className="col-sm-3 col-form-label"
                        >
                            {key}
                        </Label>

                        <TypeEntityItemDynamicInput
                            field={value}
                            fieldName={key}
                            validation={validation}
                        />
                    </Row>
                )
            }
        }

        return result
    }
    const items = getItems(entity)

    const deleteItem = () => {

        deleteEntityItem(router.navigate, itemUrl, entityName, entity.id)
    }


    return (
        <React.Fragment>
            <div className="page-content">
                <Container fluid={true}>
                    <Breadcrumb title="Forms" breadcrumbItem="Form Layouts" />
                    <Row>


                        <Col xl={6}>
                            <Card>
                                <CardBody>
                                    <CardTitle className="mb-4">{entityName}</CardTitle>

                                    <Form

                                        onSubmit={validation.handleSubmit}>
                                        {items}
                                        <div style={{
                                            width: '100%',
                                            display: 'flex',
                                            justifyContent: 'flex-end',
                                            alignItems: 'center'
                                        }}>
                                            <Button color="primary" type="submit">
                                                Submit form
                                            </Button>

                                            <Button style={{ marginLeft: '5px' }} color="danger" type="button"
                                                onClick={deleteItem}
                                            >
                                                Delete
                                            </Button>
                                        </div>
                              
                                    </Form>

                                </CardBody>
                            </Card>
                        </Col>
                    </Row>
                </Container>
            </div>
        </React.Fragment>
    )
}

export default EntityItem