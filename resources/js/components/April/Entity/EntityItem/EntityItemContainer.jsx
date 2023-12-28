import { connect } from "react-redux"
import EntityItems from "../EntityItems/EntityItems"
import { compose } from "@reduxjs/toolkit"
import withRouter from "../../../Common/withRouter"
import { useEffect, useState } from "react"
import EntityPage from "../../../../pages/April/Entity/Entity"
import { API_METHOD, PORTALS_URL } from "../../../../types/app/app-type"
import { addRelation, deleteEntityItem, getEntities, getEntityItem, getInitialEntityData, getInitialRelationEntity, setOrupdateEntityItem, setRelation } from "../../../../store/april/entity/entity-reducer"
import EntityItem from "./EntityItem"
import EntityItemAdd from "./EntityItemAdd"
import { getInitialValues } from "../../../../utils/entity-utils/entity-util"
import { useFormik } from "formik"
import { appendFormData } from "../../../../utils/entity-utils/form-util"


const mapState = (state) => {


    return {

        current: state.entity.current,
        creating: state.entity.creating,
        relation: state.entity.relation,

    }
}



const EntityItemContainer = ({
    router,
    current,
    creating,
    relation,
    itemUrl,
    entityName,
    getEntityItem,
    setOrupdateEntityItem,
    getInitialEntityData,
    deleteEntityItem,
    getInitialRelationEntity,
    setRelation,
    addRelation
}) => {

    // const [currentItems, setCurrentItems] = useState(items)
    const [isCreating, setIsCreating] = useState(router.params.entityId === 'add')
    const [creatingData, setCreating] = useState(creating)
    const [files, setFiles] = useState([]);
    useEffect(() => {

        if (router.params.entityId) {
            router.params.entityId !== 'add'
                ? getEntityItem(itemUrl, entityName, Number(router.params.entityId))
                : getInitialEntityData(itemUrl, router.location.pathname, router.navigate)
        }
        setIsCreating(router.params.entityId === 'add')
    }, [router.location.pathname])

    useEffect(() => {

        setCreating(creating)
    }, [creating])

    const dataInitialValues = creating.formData && getInitialValues(creating.formData)

    // Form validation 
    const validation = useFormik({
        // enableReinitialize : use this flag when initial values needs to be changed
        enableReinitialize: true,

        initialValues: {
            ...dataInitialValues
        },


        onSubmit: (values) => {
            console.log("values", values);
            const formData = new FormData();


            for (const key in values) {
                appendFormData(formData, key, values[key]);
            }

            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }
            
            setOrupdateEntityItem(router.navigate, router.location.pathname, itemUrl, itemUrl, formData)
           
        }

    });
    const handleFileChange = (event, inputName) => {
        // Добавляем все выбранные файлы в массив
        // setFiles([...files, ...event.target.files]);
        // Обновляем стейт Formik (необязательно)
        validation.setFieldValue(inputName, event.target.files);
    };
    return !isCreating ? <EntityItem
        router={router}
        entity={current}
        entityName={entityName}
        itemUrl={itemUrl}
        setOrupdateEntityItem={setOrupdateEntityItem}
        deleteEntityItem={deleteEntityItem}

    />
        : creatingData.formData && <EntityItemAdd
            validation={validation}
            router={router}
            creating={creatingData}
            relation={relation}
            isFromRelation={false}
            entityName={entityName}
            itemUrl={itemUrl}
            setOrupdateEntityItem={setOrupdateEntityItem}
            getInitialRelationEntity={getInitialRelationEntity}
            setRelation={setRelation}
            addRelation={addRelation}
            handleFileChange={handleFileChange}

        />

}











// export default connect(mapState, {})(EntityItems)
// const connector = connect(mapState, {});
export default connect(mapState, {
    getEntityItem,
    setOrupdateEntityItem,
    getInitialEntityData,
    deleteEntityItem,
    getInitialRelationEntity,
    setRelation,
    addRelation,
})(EntityItemContainer)