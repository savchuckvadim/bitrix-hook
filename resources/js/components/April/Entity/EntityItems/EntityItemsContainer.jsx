import { connect } from "react-redux"
import EntityItems from "./EntityItems"
import { compose } from "@reduxjs/toolkit"
import withRouter from "../../../Common/withRouter"
import { useEffect, useState } from "react"
import EntityPage from "../../../../pages/April/Entity/Entity"
import { API_METHOD, PORTALS_URL } from "../../../../types/app/app-type"
import { getEntities, getInitialEntityData, updateEntities } from "../../../../store/april/entity/entity-reducer"


const mapState = (state, own) => {
    const items = state.entity.items
    let itemsArray = []
    let tableHeaders = null


    if (items && items.length > 0) {
        tableHeaders = []
        items.forEach((item, index) => {
            let itemProps = []
            let entityId = item.id || item.number
            if (index === 0) {
                for (const itemHeader in item) {
                    tableHeaders.push(itemHeader)
                }
            }
            for (const itemHeader in item) {

                itemProps.push({

                    name: itemHeader,
                    value: item[itemHeader]
                })
            }
            itemsArray.push({
                id: entityId,
                items: itemProps
            })
        });


    }

    return {
        // app: state.app,
        items: itemsArray,
        tableHeaders,
        // entity: state.entity,
        itemUrl: own.itemUrl,
        entityName: own.entityName
    }
}



const EntityItemsContainer = ({ 
    router, entityName, entityTitle, itemUrl,itemsUrl, items, tableHeaders,
    getEntities, getInitialEntityData, updateEntities,
}) => {

    // const [currentItems, setCurrentItems] = useState(items)

    useEffect(() => {

        getEntities(itemsUrl, API_METHOD.GET, entityName, null)
    }, [router.location.pathname])



    return <EntityItems
        router={router}
        entityName={entityName}
        entityTitle={entityTitle}
        itemUrl={itemUrl}
        items={items}
        tableHeaders={tableHeaders}
        getInitialEntityData={getInitialEntityData}
        updateEntities={updateEntities}
    />

}











// export default connect(mapState, {})(EntityItems)
// const connector = connect(mapState, {});
export default connect(mapState, {
    getEntities,
    getInitialEntityData,
    updateEntities,
})(EntityItemsContainer)