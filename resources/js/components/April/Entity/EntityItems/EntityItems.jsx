import React, { useEffect, useState } from "react";

import {
    Table,
    Row,
    Col,
    Card,
    CardBody,
    Button,

} from "reactstrap"
//Import Breadcrumb
import Breadcrumb from "../../../Common/Breadcrumb";
import EntityItemsFilter from "./Items/ItemsFilter";
import './EntityItems.scss'





const EntityItems = ({ entityName, entityTitle, items, itemUrl, router, tableHeaders,
    getInitialEntityData, updateEntities
}) => {

    document.title = entityTitle + " | April App"
    const [currentItems, setCurrentItems] = useState(items)
    useEffect(() => {
        setCurrentItems(items)
    }, [items])

    const createNewEntityItem = () => {
        const initialUrl = router.location.pathname
        
        getInitialEntityData(itemUrl, router, router.location.pathname, router.navigate)
    }

    return (
        <React.Fragment>
            <EntityItemsFilter updateEntities={updateEntities} entityName={entityName} />
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
                                                        <th>Связать</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    
                                                    {currentItems.map(entity => {
                                                        return <tr
                                                            onClick={() => {
                                                                
                                                                router.navigate(`/${itemUrl}/${entity.id}`)
                                                            }}
                                                            className={`entities-item-link`}
                                                            key={`${entityName}-row-${entity.id}`}>
                                                            {entity.items.map((prop, i) => {
                                                                if (prop.name === 'id' || i === 0) {
                                                                    return <th

                                                                        key={`first-cell-${i}`} scope="row">{prop.value}
                                                                    </th>;


                                                                } else if ((typeof prop.value === 'object' || Array.isArray(prop.value)) && prop.value !== null) {
                                                                    // Пропускаем элементы, чьё значение - объект или массив

                                                                    return <th key={`first-cell-${i}`} scope="row">{'object'}</th>;
                                                                } else if (prop.value === null) {
                                                                    // Пропускаем элементы, чьё значение - объект или массив

                                                                    return <th key={`first-cell-${i}`} scope="row">{''}</th>;
                                                                } else {
                                                                    return <td key={`${prop.value}-cell-${i}`}>{prop.value}</td>;
                                                                }
                                                            })}
                                                            <td>
                                                                <button type="button" className="btn btn-light btn-sm"
                                                                    onClick={() => {

                                                                        router.navigate(`/${itemUrl}/${entity.id}`)
                                                                    }}
                                                                >View</button>
                                                            </td>

                                                          
                                                        </tr>;
                                                    })}
                                                </tbody>

                                            </Table>
                                        </div>

                                    </div>


                                </CardBody>

                            </Card>
                        </Col>
                        <div>
                            <Button className="mb-4" color="primary"
                                onClick={createNewEntityItem}
                            >
                                Добавить
                            </Button>
                        </div>
                    </Row>
                </div>
            </div>
        </React.Fragment>

    )

}

export default EntityItems