import { connect } from "react-redux"
import { useEffect, useState } from "react"
import { addRelation, deleteEntityItem, getEntityItem, getInitialEntityData, getInitialRelationEntity, setOrupdateEntityItem, setRelation } from "../../../../store/april/entity/entity-reducer"
import EntityItem from "./EntityItem"
import EntityItemAdd from "./EntityItemAdd"




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
    const [formik, setFormik] = useState(null)

    useEffect(() => {
        const isCurrentCreating = router.params.entityId === 'add'
            || (router.params.entityChildrenId
                && router.params.entityChildrenId === 'add')
        if (router.params.entityId) {

            !isCurrentCreating
                ? getEntityItem(itemUrl, entityName, Number(router.params.entityId))
                : getInitialEntityData(itemUrl, router, router.location.pathname, router.navigate)
        }
        setIsCreating(isCurrentCreating)


        // setHandleFile(handleFileChange)



    }, [router.location.pathname])


    useEffect(() => {
        setCreating(creating)
    }, [creating])

    // const validation = getFormik(router, creating, itemUrl, current, setOrupdateEntityItem)

    // // setFormik(validation)

    // // Form validation 
    // // const validation = getFormik(router, creating, itemUrl, current, setOrupdateEntityItem)
    // const handleFileChange = (event, inputName, formik) => {
    //     // Добавляем все выбранные файлы в массив
    //     // setFiles([...files, ...event.target.files]);
    //     // Обновляем стейт Formik (необязательно)
    //     formik.setFieldValue(inputName, event.target.files);
    // };
    // setFormik(validation)
    // setHandleFile(handleFileChange)


    return !isCreating ? <EntityItem
        // validation={validation}
        router={router}
        entity={current}
        entityName={entityName}
        itemUrl={itemUrl}
        setOrupdateEntityItem={setOrupdateEntityItem}
        deleteEntityItem={deleteEntityItem}

    />
        : creatingData.formData && <EntityItemAdd
            // validation={validation}
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
            // handleFileChange={handleFileChange}

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