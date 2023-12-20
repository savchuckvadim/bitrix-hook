import React, { useEffect, useState } from 'react';
import { Container } from "reactstrap";
import Breadcrumb from '../../../components/Common/Breadcrumb';
import EntityItemsContainer from '../../../components/April/Entity/EntityItems/EntityItemsContainer';
import { API_METHOD, PORTALS_URL } from '../../../types/app/app-type';

//Import Breadcrumb


const Clients = ({ clients, isFetching, isFetched, itemUrl, itemsUrl, getEntities }) => {
    //meta title
    document.title = "Clients | April web app";
    const [currentItems, setCurrentItems] = useState(clients)

    
    useEffect(() => {

        getEntities(PORTALS_URL.PORTALS, API_METHOD.GET, PORTALS_URL.PORTALS, null)


    }, [])
    useEffect(() => {
        
        setCurrentItems(clients)
    }, [clients])

    return (
        <>
            <div className="page-content">
                <Container fluid={true}>
                    <Breadcrumb title="April App" breadcrumbItem="Clients" />

                    {clients && clients.length && <EntityItemsContainer
                        entityName={'client'}
                        items={currentItems}
                        itemUrl={itemUrl}
                        itemsUrl={itemsUrl}
                    />}
                </Container>
            </div>
        </>
    );
}

export default Clients;