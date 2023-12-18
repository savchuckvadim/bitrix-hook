import { GET_CLIENTS, SET_CLIENTS } from "./actionTypes"


export const setClients = (clients) => {
  return {
    type: SET_CLIENTS,
    payload: { clients },
  }
}

export const getClients = (isFetching) => {
  return {
    type: GET_CLIENTS,
    payload: { isFetching },
  }
}
