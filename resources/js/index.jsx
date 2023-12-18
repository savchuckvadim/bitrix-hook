import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from "react-router-dom";
import { Provider } from "react-redux";
import "./i18n";

import store from "./store";
import AppContainer from './AppContainer';

if (document.getElementById('react-app')) {
    const root = ReactDOM.createRoot(document.getElementById("react-app"));
    root.render(
        <Provider store={store}>
            <React.Fragment>
                <BrowserRouter>
                    <AppContainer />
                </BrowserRouter>
            </React.Fragment>
        </Provider>
    );
}
