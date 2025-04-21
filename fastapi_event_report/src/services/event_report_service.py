from typing import Dict, Any, List, Optional
from datetime import datetime
import aiohttp
import json
from .deal_stages import DealStageService, DealType


class EventReportService:
    def __init__(self, domain: str, hook: str):
        self.domain = domain
        self.hook = hook
        self.now_date = datetime.now().strftime("%d.%m.%Y")
        self.deal_stage_service = DealStageService()

    async def process_event(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Основной метод обработки события, аналогичный getEventFlow из PHP
        """
        try:
            # Инициализация переменных как в PHP
            updated_company = None
            updated_lead = None
            current_smart = None
            current_smart_id = None
            current_deal = None
            current_deal_id = None
            current_deals_ids = None
            result = None

            # Проверка на deal flow
            if data.get("is_deal_flow") and data.get("portal_deal_data"):
                current_deals_ids = await self.get_new_batch_deal_flow(data)

            # Проверка на no call
            if not data.get("is_no_call"):
                if self.domain == "gsirk.bitrix24.ru":
                    if data.get("is_fail"):
                        await self.fail_flow(data)
                    await self.relation_lead_flow(data)

            # Обработка списков
            await self.get_list_batch_flow(data)

            return {
                "success": True,
                "data": {"deals_ids": current_deals_ids, "result": result},
            }

        except Exception as e:
            return {"success": False, "error": str(e)}

    async def get_new_batch_deal_flow(self, data: Dict[str, Any]) -> List[str]:
        """
        Реализация getNEWBatchDealFlow из PHP
        """
        current_deals_ids = []
        current_deal = None
        current_deal_id = None
        result = None

        # Получение данных о сделке
        if data.get("portal_deal_data"):
            current_deal = data["portal_deal_data"]
            current_deal_id = current_deal.get("ID")
            current_deals_ids = [current_deal_id]

        # Обработка сделки
        if current_deal:
            # Определение типа сделки
            deal_type = DealType.BASE
            if data.get("deal_type") == "presentation":
                deal_type = DealType.PRESENTATION

            # Определение типа события
            event_type = "plan" if data.get("is_plan") else "report"

            # Получение текущей стадии
            current_stage = current_deal.get("STAGE_ID")

            # Определение следующей стадии
            next_stage = self.deal_stage_service.get_next_stage(
                current_stage=current_stage,
                deal_type=deal_type,
                event_type=event_type,
                is_fail=data.get("is_fail", False),
            )

            if next_stage:
                # Получение полей для обновления
                stage_fields = self.deal_stage_service.get_stage_fields(next_stage)

                # Добавление дополнительных полей
                if data.get("is_presentation_done"):
                    stage_fields["UF_CRM_PRESENTATION_DONE"] = "Y"
                    stage_fields["UF_CRM_PRESENTATION_DATE"] = self.now_date

                # Генерация команды для обновления сделки
                entity_command = await self._generate_batch_command(
                    current_btx_entity=current_deal,
                    portal_entity_data=data.get("portal_deal_data"),
                    entity_type="deal",
                    entity_id=current_deal_id,
                    report_fields=stage_fields,
                )

                # Выполнение команды
                if entity_command:
                    result = await self.execute_batch_commands(entity_command)

        return current_deals_ids

    async def _generate_batch_command(
        self,
        current_btx_entity: Dict[str, Any],
        portal_entity_data: Dict[str, Any],
        entity_type: str,
        entity_id: str,
        report_fields: Dict[str, Any],
    ) -> str:
        """
        Генерация batch команды для Bitrix24 API
        """
        if not entity_id or not report_fields:
            return ""

        # Формирование команды для обновления сущности
        command = {
            "cmd": {
                f"update_{entity_type}": f"crm.{entity_type}.update?ID={entity_id}&FIELDS={json.dumps(report_fields)}"
            }
        }

        return json.dumps(command)

    async def get_smart_flow(
        self, data: Dict[str, Any], current_smart_id: str, current_deals_ids: List[str]
    ) -> str:
        """
        Реализация getSmartFlow из PHP
        """
        return ""

    async def get_task_flow(
        self, data: Dict[str, Any], current_smart_id: str, current_deals_ids: List[str]
    ) -> str:
        """
        Реализация taskFlow из PHP
        """
        return ""

    async def get_list_flow(self, data: Dict[str, Any]) -> str:
        """
        Реализация getListFlow из PHP
        """
        return ""

    async def execute_batch_commands(self, commands: str) -> Dict[str, Any]:
        """
        Выполнение batch команд в Bitrix24
        """
        async with aiohttp.ClientSession() as session:
            url = f"https://{self.domain}/rest/{self.hook}/batch"
            async with session.post(url, json=commands) as response:
                return await response.json()

    async def fail_flow(self, data: Dict[str, Any]) -> None:
        """
        Реализация failFlow из PHP
        """
        if data.get("fail_type") and data["fail_type"].get("code") == "failure":
            if data.get("fail_reason") and data["fail_reason"].get("code"):
                data["op_fail_reason"] = data["fail_reason"]["code"]

    async def relation_lead_flow(self, data: Dict[str, Any]) -> None:
        """
        Реализация relationLeadFlow из PHP
        """
        if data.get("current_btx_entity") and data["current_btx_entity"].get("LEAD_ID"):
            lead_id = data["current_btx_entity"]["LEAD_ID"]
            # Обработка связи с лидом

    async def get_list_batch_flow(self, data: Dict[str, Any]) -> None:
        """
        Реализация getListBatchFlow из PHP
        """
        if data.get("plan_pres_deal_ids"):
            await self.get_list_presentation_flow_batch(
                data, data["plan_pres_deal_ids"]
            )

    async def get_list_presentation_flow_batch(
        self, data: Dict[str, Any], plan_pres_deal_ids: List[str]
    ) -> None:
        """
        Реализация getListPresentationFlowBatch из PHP
        """
        pass
