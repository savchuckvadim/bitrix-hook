import { connect } from "react-redux"
import App from './setup';
import { initialize } from "./store/april/app-reducer"
import { useEffect } from "react"

const mapStateToProps = (state) => {
    return {
        app: state.app
    }

}

const AppContainer = ({ app, initialize }) => {

    useEffect(() => {
        !app.initialized && initialize()
    }, [app.initialized])


    return <App app={app} />
}

export default connect(mapStateToProps, {
    initialize
})(
    AppContainer
)