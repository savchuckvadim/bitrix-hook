import { takeEvery, fork, put, all } from "redux-saga/effects"

// Login Redux States
import {  GET_CLIENTS, SET_CLIENTS } from "./actionTypes"


//Toast
import { toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import { onlineAPI } from "../../../helpers/april-online/online-api";

//If user is send successfully send mail link then dispatch redux action's are directly from here.
function* workerGetClients({ payload: {  } }) {
  debugger
  try {
    const response = yield onlineAPI.service('portals', 'get', 'portals');
    const data = response;
    debugger
    if (data) {
      toast.success('Password reset link send in your email.');
    } else {
      if (!data) {
        toast.error(data.message, {
          position: "top-right",
          autoClose: 3000,
        });
      }
    }
  } catch (error) {
    yield put(sagaGetClient(error))
  }
}

export function* watcherGetClients() {
  yield takeEvery(GET_CLIENTS, workerGetClients)
  debugger
}

function* sagaGetClient() {
  yield all([fork(watcherGetClients)])
}

export default sagaGetClient
