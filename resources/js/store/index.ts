import {Action, createStore, applyMiddleware, compose } from "redux";
import createSagaMiddleware from "redux-saga";

import rootReducer from "./reducers";
import rootSaga from "./sagas";

import  { thunk, ThunkAction }  from "redux-thunk"; // Импорт Redux Thunk

const sagaMiddleware = createSagaMiddleware();
//@ts-ignore
const composeEnhancers = window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__ || compose;



export type RootReducerType = typeof rootReducer
export type AppStateType = ReturnType<RootReducerType>
export type AppDispatchType = typeof store.dispatch

type PropertiesTypes<T> = T extends { [key: string]: infer U } ? U : never
export type InferActionsTypes<T extends { [key: string]: (...args: any) => any }> = ReturnType<PropertiesTypes<T>>
export type ThunkType<A extends Action, R = Promise<void>> = ThunkAction<R, AppStateType, unknown, A>


const store = createStore(
  rootReducer,
  //@ts-ignore
  composeEnhancers(applyMiddleware(sagaMiddleware, thunk))
);
sagaMiddleware.run(rootSaga);

export default store;
