import React, { useEffect, useState } from 'react';
import { Container } from "reactstrap";
import Breadcrumb from '../../../components/Common/Breadcrumb';
import EntityItemsContainer from '../../../components/April/Entity/EntityItems/EntityItemsContainer';
import { API_METHOD, PORTALS_URL } from '../../../types/app/app-type';

//Import Breadcrumb


const EntityPage = ({ children, name }) => {
    //meta title
    document.title = name +"  | April web app";





    return (
        <>
            <div className="page-content">
                <Container fluid={true}>
                    <Breadcrumb title="April App" breadcrumbItem={name}/>

                    {children}
                </Container>
            </div>
        </>
    );
}

export default EntityPage;