import { connect } from "react-redux"
import App from './setup';
import { initialize } from "./store/april/app-reducer"
import { useEffect } from "react"
import { useInBitrix } from "./components/Hooks/Placement";

const mapStateToProps = (state) => {
    return {
        app: state.app
    }

}

const AppContainer = ({ app, initialize }) => {
const inBitrix = useInBitrix()
    useEffect(() => {
        !app.initialized && initialize(inBitrix)
    }, [app.initialized])


    return <App app={app} />
}

export default connect(mapStateToProps, {
    initialize
})(
    AppContainer
)