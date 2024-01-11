import { connect } from "react-redux"
import { updateFront } from "../../../store/april/settings/settings-reducer"
import { Settings } from "./Settings"


const mapState = (state) => {

    return{
        settings:state.settings
    }
}


export default connect(mapState, {
    updateFront
})(Settings)