import React from "react";
import { useSelector } from "react-redux";
import { Routes, Route } from "react-router-dom";
import { layoutTypes } from "./constants/layout";

import { publicRoutes, authProtectedRoutes, bitrixRoutes } from "./routes/allRoutes";

import VerticalLayout from "./components/VerticalLayout/";
import HorizontalLayout from "./components/HorizontalLayout/";
import NonAuthLayout from "./components/NonAuthLayout";

import fakeBackend from "./helpers/AuthType/fakeBackend";
import AuthProtected from './routes/AuthProtected';
import { createSelector } from "reselect";
import BitrixAuthProtected from "./routes/BitrixAuthProtected";
import { useInBitrix } from "./components/Hooks/Placement";

// Activating fake backend
fakeBackend();

const getLayout = (layoutType) => {
    let Layout = VerticalLayout;
    switch (layoutType) {
        case layoutTypes.VERTICAL:
            Layout = VerticalLayout;
            break;
        case layoutTypes.HORIZONTAL:
            Layout = HorizontalLayout;
            break;
        default:
            break;
    }
    return Layout;
};

const Index = ({ app }) => {
    const selectLayoutData = createSelector(
        (state) => state.Layout,
        (layoutType) => layoutType
    );
    const { layoutType } = useSelector(selectLayoutData);
    const Layout = getLayout(layoutType);
    const storedData = JSON.parse(localStorage.getItem("initialData"));
    console.log("Данные из localStorage:", storedData);
    console.log('initial')
    const inBitrix = useInBitrix()

    return (
        <React.Fragment>
            <Routes>
                <Route>
                    {publicRoutes.map((route, idx) => (
                        <Route
                            path={route.path}
                            element={
                                <NonAuthLayout>
                                    {route.component}
                                </NonAuthLayout>
                            }
                            key={idx}
                            exact={true}
                        />
                    ))}
                </Route>

                {!inBitrix
                    ? <Route>
                        {authProtectedRoutes.map((route, idx) => (
                            <Route
                                path={route.path}
                                element={
                                    <AuthProtected>
                                        <Layout>{route.component}</Layout>
                                    </AuthProtected>
                                }
                                key={idx}
                                exact={true}
                            />
                        ))}
                    </Route>

                    : <Route>
                        {bitrixRoutes.map((route, idx) => (
                            <Route
                                path={route.path}
                                element={
                                    <BitrixAuthProtected>
                                        <Layout>{route.component}</Layout>
                                    </BitrixAuthProtected>
                                }
                                key={idx}
                                exact={true}
                            />
                        ))}
                    </Route>}
            </Routes>
        </React.Fragment>
    );
};

export default Index;