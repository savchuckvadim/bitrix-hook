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


const EntityItem = ({ 
    router, 
    validation,
    entity, entityName, itemUrl, 
    setOrupdateEntityItem, deleteEntityItem }) => {

    //meta title
    document.title = entityName + " | Skote React + Laravel 10 Admin And Dashboard Template";


    // Form validation 
    // const validation = useFormik({
    //     // enableReinitialize : use this flag when initial values needs to be changed
    //     enableReinitialize: true,

    //     initialValues: {
    //         ...entity
    //     },
    //     // validationSchema: Yup.object({
    //     //     firstname: Yup.string().required("Please Enter Your First Name"),
    //     //     lastname: Yup.string().required("Please Enter Your Last Name"),
    //     //     city: Yup.string().required("Please Enter Your City"),
    //     //     state: Yup.string().required("Please Enter Your State"),
    //     //     zip: Yup.string().required("Please Enter Your Zip"),
    //     // }),
    //     onSubmit: (values) => {
    //         console.log("values", values);



    //         setOrupdateEntityItem(router.navigate, router.location.pathname, 'portal', 'portal', {
    //             number: values.number,
    //             clientId: values.C_REST_CLIENT_ID,
    //             clientSecret: values.C_REST_CLIENT_SECRET,
    //             hook: values.C_REST_WEB_HOOK_URL,
    //             domain: values.domain,
    //             key: values.key
    //         })
    //         console.log("values", values);
    //     }
    // });

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
                        {/* <Col sm={9}>
                        <Input
                            type="text"
                            className="form-control"
                            id="horizontal-firstname-Input"
                            placeholder={key}
                            name={key}
                            onChange={validation.handleChange}
                            onBlur={validation.handleBlur}
                            value={validation.values[key] || ""}

                        />
                    </Col> */}
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
                                        {/* <Row className="mb-4">
                                            <Label
                                                htmlFor="horizontal-firstname-Input"
                                                className="col-sm-3 col-form-label"
                                            >
                                                First name
                                            </Label>
                                            <Col sm={9}>
                                                <Input
                                                    type="text"
                                                    className="form-control"
                                                    id="horizontal-firstname-Input"
                                                    placeholder="Enter Your"
                                                />
                                            </Col>
                                        </Row>
                                        <Row className="mb-4">
                                            <Label
                                                htmlFor="horizontal-email-Input"
                                                className="col-sm-3 col-form-label"
                                            >
                                                Email
                                            </Label>
                                            <Col sm={9}>
                                                <Input
                                                    type="email"
                                                    className="form-control"
                                                    id="horizontal-email-Input"
                                                    placeholder="Enter Your Email ID"
                                                />
                                            </Col>
                                        </Row>
                                        <Row className="mb-4">
                                            <Label
                                                htmlFor="horizontal-password-Input"
                                                className="col-sm-3 col-form-label"
                                            >
                                                Password
                                            </Label>
                                            <Col sm={9}>
                                                <Input
                                                    type="password"
                                                    className="form-control"
                                                    id="horizontal-password-Input"
                                                    placeholder="Enter Your Password"
                                                />
                                            </Col>
                                        </Row>

                                        <Row className="justify-content-end">
                                            <Col sm={9}>
                                                <div className="form-check mb-4">
                                                    <Input
                                                        type="checkbox"
                                                        className="form-check-Input"
                                                        id="horizontal-customCheck"
                                                    />
                                                    <Label
                                                        className="form-check-label"
                                                        htmlFor="horizontal-customCheck"
                                                    >
                                                        Remember me
                                                    </Label>
                                                </div>

                                                <div>
                                                    <Button
                                                        type="submit"
                                                        color="primary"
                                                        className="w-md"
                                                    >
                                                        Submit
                                                    </Button>
                                                </div>
                                            </Col>
                                        </Row> */}
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