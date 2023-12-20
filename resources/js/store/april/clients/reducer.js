import { API_METHOD, PORTALS_URL } from "../../../types/app/app-type"
import { GET_CLIENTS, SET_CLIENTS } from "./actionTypes"


const initialState = {
 
  itemUrl: PORTALS_URL.PORTAL,
  itemsUrl:PORTALS_URL.PORTALS,

  isFetching: false,
  isFetched: false
}

const clients = (state = initialState, action) => {
  switch (action.type) {
    case GET_CLIENTS:
      state = {
        ...state,
        isFetching: action.isFetching,
        
      }
      break
    case SET_CLIENTS:
      state = {
        ...state,
        items: action.clients,
        isFetched: true
      }
      break

    default:
      state = { ...state }
      break
  }
  return state
}

export default clients
