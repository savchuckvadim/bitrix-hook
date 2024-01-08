import { Button, Col, Dropdown, DropdownItem, DropdownMenu, DropdownToggle, Input, Label } from "reactstrap"
import { EntityFormField } from "../../../../../types/entity/entity-types";
import { FormikProps } from "formik";
import { GetInitialRelationFunction } from "../../../../../store/april/entity/entity-reducer";
import { useState } from "react";
import Select from "react-select";
import { NavLink } from "react-router-dom";
type DynamicInputProps = {
    field: EntityFormField
    fieldName: string //key
    validation: any
    // groupName: string

    // relationIndex: number
    // isFromRelation: boolean
    // isRelation: boolean
    // isEntitiesGroup: boolean
    // fieldIndex: number
    // relationApiName: undefined | string
    getInitialRelationEntity: GetInitialRelationFunction
    // addRelation: (groupName: string, relationIndex: number) => void
    handleFileChange: (event: any, inputName: string) => void

}
const TypeEntityItemDynamicInput = ({
    field, fieldName, validation,
    handleFileChange, getInitialRelationEntity
}: DynamicInputProps) => {

    // ожидает что field будет примитивом input если
    // это массив или объект - то ссылка на дочернюю компоненту

    let input = <div></div>
    let width = 12


    // const getRelationFieldName = (name: string) => `relations.${relationApiName}.${relationIndex}.${name}`
    //   `relations.${name}`
    // `relations.${relationApiName}.${relationIndex}.${name}`
    // const fieldFormName = relationApiName && (isRelation || isFromRelation) ? getRelationFieldName(field['apiName']) : field['apiName']
    if (Array.isArray(field)) {
        input = <NavLink to={`${fieldName}`}>{fieldName}</NavLink>
    } else if (typeof field === 'object' && field !== null) {
        input = <NavLink to={`${fieldName}`}>{fieldName}</NavLink>
    } else {
        
        input = <Input
            type={'text'}
            className="form-control"
            id="horizontal-firstname-Input"
            placeholder={fieldName}
            name={fieldName}
            onChange={validation.handleChange}
            onBlur={validation.handleBlur}
            value={validation.values[fieldName]}
            disabled={fieldName === 'id'}
       

        />
    }


   



    return (
        <Col sm={width}>
            {input}
        </Col>
    )
}

export default TypeEntityItemDynamicInput