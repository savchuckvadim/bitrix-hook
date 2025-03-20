import React from "react";
import { Navigate, useLocation } from "react-router-dom";

const AuthProtected = (props) => {

  const authUser = sessionStorage.getItem("authUser")
  // const bitrixAuth = localStorage.getItem("initialBitrix")
  // let location = useLocation();
  // if (location.pathname.includes("bitrix")) {
  //   <Navigate to={{ pathname: "/bitrix", state: { from: props.location } }} />

  // }
  if (!authUser ) {
    return (
      <Navigate to={{ pathname: "/login", state: { from: props.location } }} />
    );
  }
  return (
    <React.Fragment>
      {props.children}
    </React.Fragment>);
};

export default AuthProtected;
