import {
    Col,
    Row,
    Card,
    CardBody,
    UncontrolledDropdown,
    DropdownToggle,
    DropdownMenu,
    DropdownItem,
    Input,
    Button,
    Form,
} from "reactstrap";
import { Link } from 'react-router-dom';
import { useFormik } from "formik";

const EntityItemsFilter = ({updateEntities, entityName}) => {

    const validation = useFormik({
        // enableReinitialize : use this flag when initial values needs to be changed
        enableReinitialize: true,
        initialValues: {
            upload: ''
        },

        onSubmit: (values) => {
            debugger
            updateEntities(values.upload, entityName)
            console.log("upload values", values);
        }
    });
    return (
        <Row>
            <Col lg="12">
                <Card>
                    <CardBody className="border-bottom">
                        <Form style={{ width: '100%' }} id="horizontal-firstname-Input" onSubmit={validation.handleSubmit}>
                            <div className="d-flex align-items-center">
                                {/* <h5 className="mb-0 card-title flex-grow-1">Jobs Lists</h5> */}

                                <Input

                                    type={'text'}
                                    className="form-control"
                                    id="horizontal-firstname-Input"
                                    placeholder={'Загрузить через ключ'}
                                    name={'upload'}
                                    onChange={validation.handleChange}
                                    onBlur={validation.handleBlur}
                                    value={validation.values['upload'] || ""}

                                />
                                <div className="flex-shrink-0">
                                    <Button
                                        style={{ marginLeft: '1%' }}
                                        to="#!" color="primary" type="submit"
                                        className="btn btn-primary me-1"
                                    >Загрузить</Button>
                                    {/* <Link to="#!" className="btn btn-light me-1"><i className="mdi mdi-refresh"></i></Link> */}

                                </div>

                            </div>
                        </Form>
                    </CardBody>
                </Card>
            </Col>
        </Row >
    )
}

export default EntityItemsFilter