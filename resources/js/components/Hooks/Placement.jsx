import { allEntities } from "../../store/april/entity/initial-entities";
import { bitrixEntities } from "../../store/april/entity/bitrix-entities";
import { useLocation } from "react-router-dom";

export const useEntities = () => {
    const bitrixAuth = localStorage.getItem("initialBitrix")
    let location = useLocation();
    if (location.pathname.includes("bitrix") || bitrixAuth) {
        return bitrixEntities
    }
    return allEntities
}

export const useInBitrix= () => {
    const bitrixAuth = localStorage.getItem("initialBitrix")
    let location = useLocation();
    if (location.pathname.includes("bitrix") || bitrixAuth) {
        return false
    }
    return false
}