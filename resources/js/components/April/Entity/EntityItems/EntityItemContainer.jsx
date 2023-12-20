import { connect } from "react-redux"
import EntityItems from "./EntityItems"
import { compose } from "@reduxjs/toolkit"
import withRouter from "../../../Common/withRouter"
import { useEffect, useState } from "react"
import EntityPage from "../../../../pages/April/Entity/Entity"
import { API_METHOD, PORTALS_URL } from "../../../../types/app/app-type"
import { getEntities, getEntityItem } from "../../../../store/april/entity/entity-reducer"


const mapState = (state) => {


    return {

        current: state.entity.current

    }
}



const EntityItemContainer = ({
    router,
    current,
    itemUrl,
    entityName,
    getEntityItem
}) => {

    // const [currentItems, setCurrentItems] = useState(items)

    useEffect(() => {
        
        if (router.params.entityId) {
            getEntityItem(itemUrl, entityName, Number(router.params.entityId))
        }

    }, [router.location.pathname])



    return <di>{current && current.name}</di>

}











// export default connect(mapState, {})(EntityItems)
// const connector = connect(mapState, {});
export default connect(mapState, {
    getEntityItem
})(EntityItemContainer)