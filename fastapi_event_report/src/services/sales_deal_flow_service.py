from typing import Dict, Any, Optional, List, Union
from enum import Enum
from datetime import datetime
import json
import aiohttp


class DealStage(Enum):
    """
    Стадии сделок в воронке продаж
    """

    NEW = "NEW"  # Новая
    PREPARATION = "PREPARATION"  # Подготовка
    PRE_MEETING = "PRE_MEETING"  # Предвстреча
    MEETING = "MEETING"  # Встреча
    PRESENTATION = "PRESENTATION"  # Презентация
    MAKE_DECISION = "MAKE_DECISION"  # Принятие решения
    WON = "WON"  # Успешная
    LOSE = "LOSE"  # Неуспешная


class DealType(Enum):
    """
    Типы сделок
    """

    BASE = "base"  # Базовая воронка
    PRESENTATION = "presentation"  # Воронка презентаций
    SMART = "smart"  # Smart-воронка


class SalesDealFlowService:
    """
    Сервис для работы со сделками в воронке продаж
    """

    def __init__(self, domain: str, hook: str):
        """
        Инициализация сервиса

        Args:
            domain (str): Домен Bitrix24 (например, 'gsirk.bitrix24.ru')
            hook (str): Webhook для доступа к API
        """
        self.domain = domain
        self.hook = hook
        self.now_date = datetime.now().strftime("%d.%m.%Y")

    async def get_deal_flow(
        self,
        deal: Dict[str, Any],
        event_type: str,
        is_fail: bool = False,
        is_presentation_done: bool = False,
        is_plan: bool = False,
        deal_type: str = "base",
        base_deal_id: Optional[str] = None,
        deal_event_type: Optional[str] = None,
        current_task: Optional[Dict[str, Any]] = None,
        comment: str = "",
        plan_responsible_id: Optional[str] = None,
        fail_type: Optional[Dict[str, Any]] = None,
        fail_reason: Optional[Dict[str, Any]] = None,
        current_report_event_type: Optional[str] = None,
        current_plan_event_type: Optional[str] = None,
    ) -> Dict[str, Any]:
        """
        Получение потока сделки

        Args:
            deal (Dict[str, Any]): Данные сделки из Bitrix24
            event_type (str): Тип события ('plan' или 'report')
            is_fail (bool): Флаг неудачи
            is_presentation_done (bool): Флаг завершенной презентации
            is_plan (bool): Флаг плана
            deal_type (str): Тип сделки ('base', 'presentation', 'smart')
            base_deal_id (Optional[str]): ID базовой сделки
            deal_event_type (Optional[str]): Тип события сделки
            current_task (Optional[Dict[str, Any]]): Текущая задача
            comment (str): Комментарий
            plan_responsible_id (Optional[str]): ID ответственного за план
            fail_type (Optional[Dict[str, Any]]): Тип неудачи
            fail_reason (Optional[Dict[str, Any]]): Причина неудачи
            current_report_event_type (Optional[str]): Тип события отчета
            current_plan_event_type (Optional[str]): Тип события плана

        Returns:
            Dict[str, Any]: Результат обработки сделки
        """
        try:
            # Инициализация переменных
            current_deal = deal
            current_deal_id = deal.get("ID")
            current_deals_ids = [current_deal_id]
            result = None

            # Получение команды для entity flow
            entity_command = await self.get_entity_batch_flow_command(
                current_btx_entity=current_deal,
                entity_type="deal",
                entity_id=current_deal_id,
                portal_entity_data=deal,
                is_deal=True,
                deal=current_deal,
                deal_type=deal_type,
                base_deal_id=base_deal_id,
                deal_event_type=deal_event_type,
                current_task=current_task,
                comment=comment,
                plan_responsible_id=plan_responsible_id,
                is_fail=is_fail,
                fail_type=fail_type,
                fail_reason=fail_reason,
                is_presentation_done=is_presentation_done,
                current_report_event_type=current_report_event_type,
                current_plan_event_type=current_plan_event_type,
            )

            # Выполнение batch команд
            if entity_command:
                result = await self.execute_batch_commands(entity_command)

            return {
                "success": True,
                "data": {"deals_ids": current_deals_ids, "result": result},
            }

        except Exception as e:
            return {"success": False, "error": str(e)}

    async def get_entity_batch_flow_command(
        self,
        current_btx_entity: Dict[str, Any],
        entity_type: str,
        entity_id: str,
        portal_entity_data: Dict[str, Any],
        is_deal: bool = False,
        deal: Optional[Dict[str, Any]] = None,
        deal_type: str = "base",
        base_deal_id: Optional[str] = None,
        deal_event_type: Optional[str] = None,
        current_task: Optional[Dict[str, Any]] = None,
        comment: str = "",
        plan_responsible_id: Optional[str] = None,
        is_fail: bool = False,
        fail_type: Optional[Dict[str, Any]] = None,
        fail_reason: Optional[Dict[str, Any]] = None,
        is_presentation_done: bool = False,
        current_report_event_type: Optional[str] = None,
        current_plan_event_type: Optional[str] = None,
    ) -> str:
        """
        Получение команды для batch flow сущности

        Args:
            current_btx_entity (Dict[str, Any]): Текущая сущность Bitrix24
            entity_type (str): Тип сущности ('deal', 'company', 'contact')
            entity_id (str): ID сущности
            portal_entity_data (Dict[str, Any]): Данные сущности из портала
            is_deal (bool): Флаг сделки
            deal (Optional[Dict[str, Any]]): Данные сделки
            deal_type (str): Тип сделки ('base', 'presentation', 'smart')
            base_deal_id (Optional[str]): ID базовой сделки
            deal_event_type (Optional[str]): Тип события сделки
            current_task (Optional[Dict[str, Any]]): Текущая задача
            comment (str): Комментарий
            plan_responsible_id (Optional[str]): ID ответственного за план
            is_fail (bool): Флаг неудачи
            fail_type (Optional[Dict[str, Any]]): Тип неудачи
            fail_reason (Optional[Dict[str, Any]]): Причина неудачи
            is_presentation_done (bool): Флаг завершенной презентации
            current_report_event_type (Optional[str]): Тип события отчета
            current_plan_event_type (Optional[str]): Тип события плана

        Returns:
            str: Команда для batch flow
        """
        # Инициализация полей отчета
        report_fields = {
            "manager_op": plan_responsible_id,
            "op_work_status": "",
            "op_prospects_type": "op_prospects_good",
            "op_result_status": "",
            "op_noresult_reason": "",
            "op_fail_reason": "",
            "op_fail_comments": "",
            "op_history": "",
            "op_mhistory": [],
        }

        # Расчет количества презентаций
        current_pres_count = 0
        company_pres_count = 0
        deal_pres_count = 0

        if current_task and current_task.get("presentation"):
            if current_task["presentation"].get("company"):
                company_pres_count = int(current_task["presentation"]["company"])
            if current_task["presentation"].get("deal"):
                deal_pres_count = int(current_task["presentation"]["deal"])

        current_pres_count = company_pres_count

        if is_deal and deal and deal.get("ID"):
            current_pres_count = deal_pres_count
            current_btx_entity = deal
            entity_type = "deal"
            entity_id = deal["ID"]
            portal_entity_data = deal

            if deal_type == "presentation":
                report_fields["to_base_sales"] = base_deal_id
                current_pres_count = 0
                if deal_event_type in ["plan", "fail"]:
                    current_pres_count = -1

        # Получение комментариев
        current_pres_comments = []
        current_fail_comments = []
        current_m_comments = []

        if current_btx_entity:
            if current_btx_entity.get("UF_CRM_PRES_COMMENTS"):
                current_pres_comments = current_btx_entity["UF_CRM_PRES_COMMENTS"]
            if current_btx_entity.get("UF_CRM_OP_FAIL_COMMENTS"):
                current_fail_comments = current_btx_entity["UF_CRM_OP_FAIL_COMMENTS"]
            if current_btx_entity.get("UF_CRM_OP_MHISTORY"):
                current_m_comments = current_btx_entity["UF_CRM_OP_MHISTORY"]

        # Установка дат
        report_fields["next_pres_plan_date"] = None
        report_fields["call_next_date"] = None

        if current_report_event_type:
            report_fields["call_last_date"] = self.now_date

            if current_report_event_type == "xo":
                report_fields["xo_date"] = None

        # Обработка завершенной презентации
        if is_presentation_done:
            report_fields["last_pres_done_date"] = self.now_date
            report_fields["last_pres_done_responsible"] = plan_responsible_id
            report_fields["pres_count"] = current_pres_count + 1

        # Обработка неудачи
        if is_fail:
            if fail_type and fail_type.get("code") == "failure":
                if fail_reason and fail_reason.get("code"):
                    report_fields["op_fail_reason"] = fail_reason["code"]

        # Обновление комментариев
        current_m_comments.insert(0, f"{self.now_date}\n{comment}")

        total_comments_count = 30 if self.domain == "gsirk.bitrix24.ru" else 12
        if len(current_m_comments) > total_comments_count:
            current_m_comments = current_m_comments[:total_comments_count]
        if len(current_pres_comments) > 15:
            current_pres_comments = current_pres_comments[:15]

        report_fields["op_mhistory"] = current_m_comments
        report_fields["pres_comments"] = current_pres_comments

        # Генерация batch команды
        entity_command = await self._generate_batch_command(
            current_btx_entity,
            portal_entity_data,
            entity_type,
            entity_id,
            report_fields,
        )

        return entity_command

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

        Args:
            current_btx_entity (Dict[str, Any]): Текущая сущность Bitrix24
            portal_entity_data (Dict[str, Any]): Данные сущности из портала
            entity_type (str): Тип сущности
            entity_id (str): ID сущности
            report_fields (Dict[str, Any]): Поля для обновления

        Returns:
            str: Строка с batch командой
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

    async def execute_batch_commands(self, commands: str) -> Dict[str, Any]:
        """
        Выполнение batch команд в Bitrix24

        Args:
            commands (str): Строка с batch командами

        Returns:
            Dict[str, Any]: Результат выполнения команд
        """
        async with aiohttp.ClientSession() as session:
            url = f"https://{self.domain}/rest/{self.hook}/batch"
            async with session.post(url, json=commands) as response:
                return await response.json()
