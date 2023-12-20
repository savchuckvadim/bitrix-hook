import { connect } from "react-redux"
import EntityItems from "./EntityItems"
import { compose } from "@reduxjs/toolkit"
import withRouter from "../../../Common/withRouter"
import { useEffect } from "react"
import EntityPage from "../../../../pages/April/Entity/Entity"
import EntityItemsContainer from "./EntityItemsContainer"
import { getEntities } from "../../../../store/april/entity/entity-reducer"
import { API_METHOD, PORTALS_URL } from "../../../../types/app/app-type"
import { ENTITY_QUANTITY } from "../../../../types/entity/entity-types"
import EntityItemContainer from "./EntityItemContainer"


const mapState = (state, own) => {


    return {
        type: own.type,
        itemUrl: own.itemUrl,
        itemsUrl: own.itemsUrl,
        entityName: own.entityName,
        entityTitle: own.entityTitle
    }
}



const EntityContainer = ({
    router, type, itemUrl, itemsUrl, entityName, entityTitle,
}) => {




    return <EntityPage name={entityTitle}>
        {type === ENTITY_QUANTITY.ENTITIES
            ? <EntityItemsContainer
                router={router}
                itemUrl={itemUrl}
                itemsUrl={itemsUrl}
                entityName={entityName}
                entityTitle={entityTitle}
            />
            : <EntityItemContainer
            router={router}
            entityName={entityName}
            itemUrl={itemUrl}
            />

        }
    </EntityPage>
}











// export default connect(mapState, {})(EntityItems)
// const connector = connect(mapState, {});
export default compose(

    connect(mapState, {

    }),
    withRouter,

)(EntityContainer)