
import React from "react";

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
    InputGroup,
} from "reactstrap";



const ItemGroupTable = () => {

    return (

        <React.Fragment>
            <div className="page-content">
                {/* <Container fluid={true}>
                    <Breadcrumbs title="Forms" breadcrumbItem="Form Layouts" /> */}
                <Row>
                    <Col xl={6}>
                        <Card>
                            <CardBody>
                                <CardTitle className="mb-4">Form Grid Layout</CardTitle>

                                <Form>
                                    <div className="mb-3">
                                        <Label htmlFor="formrow-firstname-Input">First Name</Label>
                                        <Input
                                            type="text"
                                            className="form-control"
                                            id="formrow-firstname-Input"
                                            placeholder="Enter Your First Name"
                                        />
                                    </div>

                                    <Row>
                                        <Col md={6}>
                                            <div className="mb-3">
                                                <Label htmlFor="formrow-email-Input">Email</Label>
                                                <Input
                                                    type="email"
                                                    className="form-control"
                                                    id="formrow-email-Input"
                                                    placeholder="Enter Your Email ID"
                                                />
                                            </div>
                                        </Col>
                                        <Col md={6}>
                                            <div className="mb-3">
                                                <Label htmlFor="formrow-password-Input">Password</Label>
                                                <Input
                                                    type="password"
                                                    className="form-control"
                                                    id="formrow-password-Input"
                                                    placeholder="Enter Your Password"
                                                />
                                            </div>
                                        </Col>
                                    </Row>

                                    <Row>
                                        <Col lg={4}>
                                            <div className="mb-3">
                                                <Label htmlFor="formrow-InputCity">City</Label>
                                                <Input
                                                    type="text"
                                                    className="form-control"
                                                    id="formrow-InputCity"
                                                    placeholder="Enter Your Living City"
                                                />
                                            </div>
                                        </Col>
                                        <Col lg={4}>
                                            <div className="mb-3">
                                                <Label htmlFor="formrow-InputState">State</Label>
                                                <select
                                                    id="formrow-InputState"
                                                    className="form-control"
                                                >
                                                    <option defaultValue>Choose...</option>
                                                    <option>...</option>
                                                </select>
                                            </div>
                                        </Col>

                                        <Col lg={4}>
                                            <div className="mb-3">
                                                <Label htmlFor="formrow-InputZip">Zip</Label>
                                                <Input
                                                    type="text"
                                                    className="form-control"
                                                    id="formrow-InputZip"
                                                    placeholder="Enter Your Zip Code"
                                                />
                                            </div>
                                        </Col>
                                    </Row>

                                    <div className="mb-3">
                                        <div className="form-check">
                                            <Input
                                                type="checkbox"
                                                className="form-check-Input"
                                                id="formrow-customCheck"
                                            />
                                            <Label
                                                className="form-check-Label"
                                                htmlFor="formrow-customCheck"
                                            >
                                                Check me out
                                            </Label>
                                        </div>
                                    </div>
                                    <div>
                                        <button type="submit" className="btn btn-primary w-md">
                                            Submit
                                        </button>
                                    </div>
                                </Form>
                            </CardBody>
                        </Card>
                    </Col>

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
                </Row>
            </div>
        </React.Fragment>
    )
}