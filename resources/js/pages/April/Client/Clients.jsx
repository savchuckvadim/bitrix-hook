import React, { useEffect } from 'react';
import { Container } from "reactstrap";
import Breadcrumb from '../../../components/Common/Breadcrumb';

//Import Breadcrumb


    const Clients = ({clients, isFetching, isFetched, getClients}) => {
        //meta title
        document.title="Clients | April web app";

        useEffect(() => {

            !isFetching && !isFetched && getClients(!isFetching)


        }, [])


        return (
            <>
                <div className="page-content">
                    <Container fluid={true}>
                        <Breadcrumb title="April App" breadcrumbItem="Clients" />
                        {/* write Html code or structure */}
                        {clients && clients.length &&
                        clients.map(client => <p>{client.domain}</p>)
                        }
                    </Container>
                </div>
            </>
        );
    }

export default Clients;