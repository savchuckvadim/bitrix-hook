import { connect } from "react-redux"
import Clients from "./Clients"
import { getClients } from "../../../store/april/clients/actions"



const mapStateToProps = (state) => {

    return {
        clients: state.clients.items,
        isFetching: state.clients.isFetching,
        isFetched: state.clients.isFetched
    }
}


export default connect(mapStateToProps, {
    getClients
})(
    Clients
)