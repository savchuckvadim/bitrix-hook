import { connect } from "react-redux"
import { initializeGoogleAuth } from "../../store/april/auth/auth-reducer"
import Login from "./Login"


const mapStateToProps = (state) => ({
    app: state.app
})

export default connect(mapStateToProps, {
    initializeGoogleAuth
})(Login)