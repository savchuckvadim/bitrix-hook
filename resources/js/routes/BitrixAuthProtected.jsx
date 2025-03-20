import React from "react";
import { Navigate, useLocation } from "react-router-dom";

const BitrixAuthProtected = (props) => {

  const authUser = sessionStorage.getItem("authUser")
  const bitrixAuth = localStorage.getItem("initialBitrix")
  let location = useLocation();
  if (location.pathname.includes("bitrix")) {
    if (!bitrixAuth) {
      return (
        <Navigate to={{ pathname: "/bitrix/login", state: { from: props.location } }} />
      );
    }
  } else {
    return (
      <Navigate to={{ pathname: "/login", state: { from: props.location } }} />
    );

  }

  return (
    <React.Fragment>
      {props.children}
    </React.Fragment>);
};

export default BitrixAuthProtected;
