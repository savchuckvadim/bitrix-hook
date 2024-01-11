import React from "react"
import EntityPage from "../../../pages/April/Entity/Entity"
import { SettingsStateType, UpdateFront } from "../../../store/april/settings/settings-reducer"
import { Button } from "reactstrap"

type SettingProps = {
    settings: SettingsStateType
    updateFront: UpdateFront
}


export const Settings: React.FC<SettingProps> = ({ settings, updateFront }) => {


    const items = settings && settings.actions.map(act =>
        <Button color="primary" 
        onClick={() => updateFront(act.type)}
        >
            {act.name}
        </Button>
    )


    return <EntityPage name={'Settings'}>
        {items}
    </EntityPage>
}