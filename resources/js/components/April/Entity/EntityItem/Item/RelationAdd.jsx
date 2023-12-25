import { useState } from "react";
import { Card, CardBody, CardTitle, Col, Modal, Row } from "reactstrap";
import EntityItemAdd from "../EntityItemAdd";


const RelationAdd = ({ 
    validation, relation, router, entityName, itemUrl, 
    setOrupdateEntityItem, getInitialRelationEntity, setRelation
 }) => {

    document.title = "Models | Skote React + Laravel 10 Admin And Dashboard Template";

    const [modal_fullscreen, setmodal_fullscreen] = useState(true);
    function removeBodyCss() {
        document.body.classList.add("no_padding");
    }
    function tog_fullscreen() {
        setmodal_fullscreen(!modal_fullscreen);
        removeBodyCss();
    }


    return (



        <Modal
            size="xl"
            isOpen={relation.isCreating}
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
                    Fullscreen Modal
                </h5>
                <button
                    onClick={() => {
                        setmodal_fullscreen(false);
                    }}
                    type="button"
                    className="close"
                    data-dismiss="modal"
                    aria-label="Close"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div className="modal-body">
                {/* <h5>Overflowing text to show scroll behavior</h5> */}
                <EntityItemAdd
                    isRelation={true}
                    validation={validation}
                    router={router}
                    creating={relation}
                    relation={null}
                    entityName={entityName}
                    itemUrl={itemUrl}
                    setOrupdateEntityItem={setOrupdateEntityItem}
                    getInitialRelationEntity={getInitialRelationEntity}
                    setRelation={setRelation}
                />
            </div>
            <div className="modal-footer">
                <button
                    type="button"
                    onClick={() => {
                        tog_fullscreen();
                    }}
                    className="btn btn-secondary "
                    data-dismiss="modal"
                >
                    Close
                </button>
                <button
                    type="button"
                    className="btn btn-primary "
                    onClick={() => setRelation(relation)}
                >
                    Save changes
                </button>
            </div>
        </Modal>

    )
}

export default RelationAdd