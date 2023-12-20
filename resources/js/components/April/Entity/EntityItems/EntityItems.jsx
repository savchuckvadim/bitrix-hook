import React, { useEffect, useState } from "react";

import {
    Table,
    Row,
    Col,
    Card,
    CardBody,
    CardTitle,
    CardSubtitle,
    NavLink,
    NavItem,

} from "reactstrap"
//Import Breadcrumb
import Breadcrumb from "../../../Common/Breadcrumb";






const EntityItems = ({ entityName,entityTitle, items, itemUrl, router, tableHeaders, }) => {
    
    document.title = entityTitle + " | April App"
    const [currentItems, setCurrentItems] = useState(items)
    useEffect(() => {
     setCurrentItems(items)
    }, [items])

    return (
        <React.Fragment>
            <div className="page-content">
                <div className="container-fluid">
                    <Breadcrumb title={entityTitle} breadcrumbItem="Таблица" />

                    <Row>
                        <Col xl={12}>
                            <Card>
                                <CardBody>
                                    <div className="table-responsive">
                                        <h4 className="card-title">Vertical alignment</h4>
                                        <p className="card-title-desc">Table cells in <code>&lt;tbody&gt;</code> inherit their alignment from <code>&lt;table&gt;</code> and are aligned to the the top by default. Use the vertical align classes to re-align where needed.</p>

                                        <div className="table-responsive">
                                            <Table className="align-middle mb-0">

                                                <thead>
                                                    <tr key={`table-header-${entityName}-row`}>
                                                        {tableHeaders && tableHeaders.length > 0 &&
                                                            tableHeaders
                                                                .map((item, i) => <th key={`table-header-${entityName}-cell-${i}`}>{item}</th>)}
                                                        <th>Действие</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {currentItems.map(entity => {

                                                        return <tr key={`${entityName}-row-${entity.id}`}>
                                                            {entity.items.map((prop, i) => prop.name === 'id' || i === 0
                                                                ? <th key={`first-cell-${i}`} scope="row">{prop.value}</th>
                                                                : <td key={`${prop.value}-cell-${i}`}>{prop.value}</td>)}
                                                            <td>
                                                                {/* <NavItem>
                                                                    <NavLink
                                                                        replace
                                                                        to={`../${itemUrl}/${entity.id}`}
                                                                        // className={classnames({
                                                                        //     active: activeTab === "1",
                                                                        // })}
                                                                        onClick={() => {

                                                                        }}
                                                                    > */}
                                                                <button type="button" className="btn btn-light btn-sm"
                                                                    onClick={() => {
                                                                        router.navigate(`/${itemUrl}/${entity.id}`)
                                                                    }}
                                                                >View</button>
                                                                {/* </NavLink>
                                                                </NavItem> */}

                                                            </td>
                                                        </tr>
                                                    })}

                                                </tbody>
                                            </Table>
                                        </div>

                                    </div>
                                </CardBody>
                            </Card>
                        </Col>

                    </Row>
                </div>
            </div>
        </React.Fragment>

    )

}

export default EntityItems