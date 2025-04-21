from typing import Dict, Any, Optional
from enum import Enum
from datetime import datetime


class DealType(Enum):
    BASE = "base"  # Базовая воронка
    PRESENTATION = "presentation"  # Воронка презентаций
    SMART = "smart"  # Smart-воронка


class DealStage(Enum):
    # Стадии базовой воронки
    BASE_NEW = "NEW"
    BASE_PREPARATION = "PREPARATION"
    BASE_PRE_MEETING = "PRE_MEETING"
    BASE_MEETING = "MEETING"
    BASE_PRESENTATION = "PRESENTATION"
    BASE_MAKE_DECISION = "MAKE_DECISION"
    BASE_WON = "WON"
    BASE_LOSE = "LOSE"

    # Стадии воронки презентаций
    PRES_NEW = "PRES_NEW"
    PRES_PREPARATION = "PRES_PREPARATION"
    PRES_PRESENTATION = "PRES_PRESENTATION"
    PRES_MAKE_DECISION = "PRES_MAKE_DECISION"
    PRES_WON = "PRES_WON"
    PRES_LOSE = "PRES_LOSE"


class DealStageService:
    def __init__(self):
        # Маппинг стадий для разных типов сделок
        self.stage_mapping = {
            DealType.BASE: {
                "NEW": DealStage.BASE_NEW,
                "PREPARATION": DealStage.BASE_PREPARATION,
                "PRE_MEETING": DealStage.BASE_PRE_MEETING,
                "MEETING": DealStage.BASE_MEETING,
                "PRESENTATION": DealStage.BASE_PRESENTATION,
                "MAKE_DECISION": DealStage.BASE_MAKE_DECISION,
                "WON": DealStage.BASE_WON,
                "LOSE": DealStage.BASE_LOSE,
            },
            DealType.PRESENTATION: {
                "NEW": DealStage.PRES_NEW,
                "PREPARATION": DealStage.PRES_PREPARATION,
                "PRESENTATION": DealStage.PRES_PRESENTATION,
                "MAKE_DECISION": DealStage.PRES_MAKE_DECISION,
                "WON": DealStage.PRES_WON,
                "LOSE": DealStage.PRES_LOSE,
            },
        }

    def get_next_stage(
        self,
        current_stage: str,
        deal_type: DealType,
        event_type: str,
        is_fail: bool = False,
    ) -> Optional[DealStage]:
        """
        Определяет следующую стадию сделки на основе текущей стадии,
        типа сделки и типа события
        """
        if deal_type not in self.stage_mapping:
            return None

        stage_mapping = self.stage_mapping[deal_type]
        current_stage_enum = stage_mapping.get(current_stage)

        if not current_stage_enum:
            return None

        # Логика перехода стадий для базовой воронки
        if deal_type == DealType.BASE:
            if is_fail:
                return DealStage.BASE_LOSE

            if event_type == "plan":
                if current_stage_enum == DealStage.BASE_NEW:
                    return DealStage.BASE_PREPARATION
                elif current_stage_enum == DealStage.BASE_PREPARATION:
                    return DealStage.BASE_PRE_MEETING
                elif current_stage_enum == DealStage.BASE_PRE_MEETING:
                    return DealStage.BASE_MEETING
                elif current_stage_enum == DealStage.BASE_MEETING:
                    return DealStage.BASE_PRESENTATION
                elif current_stage_enum == DealStage.BASE_PRESENTATION:
                    return DealStage.BASE_MAKE_DECISION
                elif current_stage_enum == DealStage.BASE_MAKE_DECISION:
                    return DealStage.BASE_WON

            elif event_type == "report":
                if current_stage_enum == DealStage.BASE_PREPARATION:
                    return DealStage.BASE_PRE_MEETING
                elif current_stage_enum == DealStage.BASE_PRE_MEETING:
                    return DealStage.BASE_MEETING
                elif current_stage_enum == DealStage.BASE_MEETING:
                    return DealStage.BASE_PRESENTATION
                elif current_stage_enum == DealStage.BASE_PRESENTATION:
                    return DealStage.BASE_MAKE_DECISION
                elif current_stage_enum == DealStage.BASE_MAKE_DECISION:
                    return DealStage.BASE_WON

        # Логика перехода стадий для воронки презентаций
        elif deal_type == DealType.PRESENTATION:
            if is_fail:
                return DealStage.PRES_LOSE

            if event_type == "plan":
                if current_stage_enum == DealStage.PRES_NEW:
                    return DealStage.PRES_PREPARATION
                elif current_stage_enum == DealStage.PRES_PREPARATION:
                    return DealStage.PRES_PRESENTATION
                elif current_stage_enum == DealStage.PRES_PRESENTATION:
                    return DealStage.PRES_MAKE_DECISION
                elif current_stage_enum == DealStage.PRES_MAKE_DECISION:
                    return DealStage.PRES_WON

            elif event_type == "report":
                if current_stage_enum == DealStage.PRES_PREPARATION:
                    return DealStage.PRES_PRESENTATION
                elif current_stage_enum == DealStage.PRES_PRESENTATION:
                    return DealStage.PRES_MAKE_DECISION
                elif current_stage_enum == DealStage.PRES_MAKE_DECISION:
                    return DealStage.PRES_WON

        return None

    def get_stage_fields(self, stage: DealStage) -> Dict[str, Any]:
        """
        Возвращает поля, которые нужно обновить при переходе на новую стадию
        """
        fields = {}

        if stage in [DealStage.BASE_WON, DealStage.PRES_WON]:
            fields["STAGE_ID"] = stage.value
            fields["CLOSED"] = "Y"
            fields["CLOSEDATE"] = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        elif stage in [DealStage.BASE_LOSE, DealStage.PRES_LOSE]:
            fields["STAGE_ID"] = stage.value
            fields["CLOSED"] = "Y"
            fields["CLOSEDATE"] = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        else:
            fields["STAGE_ID"] = stage.value

        return fields
