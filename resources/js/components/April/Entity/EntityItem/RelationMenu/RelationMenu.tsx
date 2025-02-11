import React, { FC, useState } from 'react';
import { FieldTypes, InitialEntityData } from '../../../../../types/entity/entity-types';
import { Card, CardBody, CardTitle, Col, Form, Modal, Row } from 'reactstrap';
import { useSelector } from 'react-redux';
import { AppStateType } from '../../../../../store';
import DynamicInputs from '../Item/DynamicInputs';
import { useDispatch } from 'react-redux';
import { entityActions } from '../../../../../store/april/entity/entity-reducer';
import { sendEntityRelations } from '../../../../../store/april/entity/entity-relations-thunk';


interface RelationMenu {
    isActive: boolean;
    formData: InitialEntityData
}
type GetStateType = () => AppStateType
const RelationMenu: FC<RelationMenu> = ({ isActive, formData }) => {

    document.title = "Models | Skote React + Laravel 10 Admin And Dashboard Template";

    const [modal_fullscreen, setmodal_fullscreen] = useState(true);
    function removeBodyCss() {
        document.body.classList.add("no_padding");
    }
    function tog_fullscreen() {
        setmodal_fullscreen(!modal_fullscreen);
        removeBodyCss();
    }
    const currentEntity = useSelector((state: AppStateType) => state.entity)
    const dispatch = useDispatch()
    const setCurrentPropsValue = (
        groupName: string,
        apiNmame: string,
        id: number,
        type: FieldTypes,
        event: any
    ) => {

        let value;
        if (type === 'boolean') {
            value = !event.target.checked;
        } else {
            value = event.target.value;
        }

        dispatch(

            entityActions
                .setEntityRelationsProp(groupName, apiNmame, id, value)
        )
    }

    const send = () => {
        dispatch(
            //@ts-ignore
            sendEntityRelations()
        )
    }

    const cancel = () => {
        dispatch(
            entityActions.cleanEntityRelationsProp()
        )
        tog_fullscreen();
    }


    return (
        <Modal
            size="xl"
            isOpen={isActive}
            toggle={() => {
                tog_fullscreen();
            }}
            className="modal-fullscreen"
        >
            <div className="modal-header">
                <h5
                    className="modal-title mt-0"
                    id="exampleModalFullscreenLabel"
                >
                    Добавления связанных моделей из списка
                </h5>
                <button
                    // onClick={() => {
                    //     setmodal_fullscreen(false);
                    // }}
                    type="button"
                    className="close"
                    data-dismiss="modal"
                    aria-label="Close"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div className="modal-body">

                <Row>
                    <Col xl={6}>
                        <Card>
                            <CardBody>
                                <CardTitle className="mb-4">{formData.title}</CardTitle>

                                <Form

                                // onSubmit={validation.handleSubmit}
                                >
                                    {formData.groups.map((group) => {
                                        return (<div> <p>{group.groupName}</p>
                                            <div>
                                                {group.fields.map((field) => {
                                                    return <DynamicInputs
                                                        field={field}
                                                        handleChange={(e) => {
                                                            setCurrentPropsValue(group.groupName, field.apiName, field.id, field.type, e)
                                                        }
                                                        }
                                                    />
                                                })
                                                }
                                            </div>
                                        </div>
                                        )
                                    })}
                                    <div style={{
                                        width: '100%',
                                        display: 'flex',
                                        justifyContent: 'flex-end',
                                        alignItems: 'center'
                                    }}>

                                    </div>

                                </Form>

                            </CardBody>
                        </Card>
                    </Col>
                </Row>
            </div>
            <div className="modal-footer">
                <button
                    type="button"
                    onClick={() => {
                        cancel()
                     
                    }}
                    className="btn btn-secondary "
                    data-dismiss="modal"
                >
                    Close
                </button>
                <button
                    type="button"
                    className="btn btn-primary "
                    onClick={() => send()}
                >
                    Отправить связь
                </button>
            </div>
        </Modal>
    );
}

export default RelationMenu;