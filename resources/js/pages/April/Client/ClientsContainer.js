import { connect } from "react-redux"
import Clients from "./Clients"
import { getClients } from "../../../store/april/clients/actions"
import { getEntities } from "../../../store/april/entity/entity-reducer"



const mapStateToProps = (state) => {

    return {
        clients: state.entity.items,
        itemUrl: state.clients.itemUrl,
        itemsUrl: state.clients.itemsUrl,
        isFetching: state.clients.isFetching,
        isFetched: state.clients.isFetched
    }
}


export default connect(mapStateToProps, {
    getClients,
    getEntities
})(
    Clients
)