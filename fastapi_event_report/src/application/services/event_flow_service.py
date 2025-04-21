from typing import Dict, Any, Optional, List
from datetime import datetime
from domain.entities.event_flow import (
    EventFlow,
    EventFlowType,
    EventFlowStatus,
    WorkStatusCode,
)
from domain.repositories.event_repository import EventRepository
from domain.services.event_service import EventService


class EventFlowService(EventService):
    def __init__(self, event_repository: EventRepository):
        self.event_repository = event_repository

    async def process_event(self, event_data: Dict[str, Any]) -> EventFlow:
        # Initialize event flow
        event_flow = EventFlow(
            type=EventFlowType(event_data.get("type", "deal")),
            entity_id=event_data["entity_id"],
            entity_type=event_data["entity_type"],
            domain=event_data["domain"],
            hook=event_data["hook"],
            status=EventFlowStatus(event_data.get("status", "plan")),
            work_status=WorkStatusCode(event_data.get("work_status", "inJob")),
            responsible_id=event_data.get("responsible_id"),
            current_user=event_data.get("current_user"),
            current_user_name=event_data.get("current_user_name"),
            is_deal_flow=event_data.get("is_deal_flow", False),
            is_no_call=event_data.get("is_no_call", False),
            is_presentation_done=event_data.get("is_presentation_done", False),
            is_unplanned_presentation=event_data.get(
                "is_unplanned_presentation", False
            ),
            is_fail=event_data.get("is_fail", False),
            is_post_sale=event_data.get("is_post_sale", False),
            portal_deal_data=event_data.get("portal_deal_data"),
            portal_company_data=event_data.get("portal_company_data"),
            current_btx_entity=event_data.get("current_btx_entity"),
            presentation=event_data.get("presentation"),
            result_status=event_data.get("result_status"),
            fail_type=event_data.get("fail_type"),
            fail_reason=event_data.get("fail_reason"),
            noresult_reason=event_data.get("noresult_reason"),
            current_report_event_type=event_data.get("current_report_event_type"),
            current_report_event_name=event_data.get("current_report_event_name"),
            current_plan_event_type=event_data.get("current_plan_event_type"),
            current_plan_event_name=event_data.get("current_plan_event_name"),
            plan_created_id=event_data.get("plan_created_id"),
            plan_responsible_id=event_data.get("plan_responsible_id"),
            plan_deadline=event_data.get("plan_deadline"),
            comment=event_data.get("comment"),
            metadata=event_data.get("metadata", {}),
        )

        # Process event flow
        await self.get_event_flow(event_flow)
        return event_flow

    async def get_event_flow(self, event_flow: EventFlow) -> None:
        try:
            if event_flow.is_deal_flow and event_flow.portal_deal_data:
                event_flow.current_deals_ids = await self.get_new_batch_deal_flow(
                    event_flow
                )

            if not event_flow.is_no_call:
                if event_flow.domain == "gsirk.bitrix24.ru":
                    if event_flow.is_fail:
                        await self.fail_flow(event_flow)
                    await self.relation_lead_flow(event_flow)

            await self.get_list_batch_flow(event_flow)

        except Exception as e:
            event_flow.status = EventFlowStatus.FAIL
            event_flow.metadata["error"] = str(e)
            raise

    async def get_new_batch_deal_flow(self, event_flow: EventFlow) -> List[str]:
        """
        Exact implementation of getNEWBatchDealFlow from PHP code
        """
        current_deals_ids = []
        current_deal = None
        current_deal_id = None
        current_smart = None
        current_smart_id = None
        result = None

        # Get deal data from portal
        if event_flow.portal_deal_data:
            current_deal = event_flow.portal_deal_data
            current_deal_id = current_deal.get("ID")
            current_deals_ids = [current_deal_id]

            # Get smart data if exists
            if current_deal.get("UF_CRM_SMART"):
                current_smart = current_deal["UF_CRM_SMART"]
                current_smart_id = current_smart.get("ID")

        # Process deal flow
        if current_deal:
            # Get entity flow command for the deal
            entity_command = await self.get_entity_batch_flow_command(
                event_flow, is_deal=True, deal=current_deal, deal_type="base"
            )

            # Process smart flow if exists
            if current_smart:
                smart_command = await self.get_smart_flow(
                    event_flow, current_smart_id, current_deals_ids
                )
                if smart_command:
                    entity_command += smart_command

            # Process task flow
            task_command = await self.get_task_flow(
                event_flow, current_smart_id, current_deals_ids
            )
            if task_command:
                entity_command += task_command

            # Process list flow
            list_command = await self.get_list_flow(event_flow)
            if list_command:
                entity_command += list_command

            # Execute batch commands
            if entity_command:
                result = await self.execute_batch_commands(entity_command)

        return current_deals_ids

    async def get_entity_batch_flow_command(
        self,
        event_flow: EventFlow,
        is_deal: bool = False,
        deal: Optional[Dict[str, Any]] = None,
        deal_type: str = "base",
        base_deal_id: Optional[str] = None,
        deal_event_type: bool = False,
    ) -> str:
        """
        Implementation of getEntityBatchFlowCommand from PHP code
        """
        entity_command = ""
        current_report_event_type = event_flow.current_report_event_type
        current_plan_event_type = event_flow.current_plan_event_type
        is_presentation_done = event_flow.is_presentation_done

        current_btx_entity = event_flow.current_btx_entity
        entity_type = event_flow.entity_type
        entity_id = event_flow.entity_id

        portal_entity_data = event_flow.portal_company_data

        report_fields = {
            "manager_op": event_flow.plan_responsible_id,
            "op_work_status": "",
            "op_prospects_type": "op_prospects_good",
            "op_result_status": "",
            "op_noresult_reason": "",
            "op_fail_reason": "",
            "op_fail_comments": "",
            "op_history": "",
            "op_mhistory": [],
        }

        # Calculate presentation counts
        current_pres_count = 0
        company_pres_count = 0
        deal_pres_count = 0

        if event_flow.current_task and event_flow.current_task.get("presentation"):
            if event_flow.current_task["presentation"].get("company"):
                company_pres_count = int(
                    event_flow.current_task["presentation"]["company"]
                )
            if event_flow.current_task["presentation"].get("deal"):
                deal_pres_count = int(event_flow.current_task["presentation"]["deal"])

        current_pres_count = company_pres_count

        if is_deal and deal and deal.get("ID"):
            current_pres_count = deal_pres_count
            current_btx_entity = deal
            entity_type = "deal"
            entity_id = deal["ID"]
            portal_entity_data = event_flow.portal_deal_data

            if deal_type == "presentation":
                report_fields["to_base_sales"] = base_deal_id
                current_pres_count = 0
                if deal_event_type in ["plan", "fail"]:
                    current_pres_count = -1

        # Get comments
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

        # Set dates
        report_fields["next_pres_plan_date"] = None
        report_fields["call_next_date"] = None

        if current_report_event_type:
            report_fields["call_last_date"] = event_flow.updated_at.strftime("%d.%m.%Y")

            if current_report_event_type == "xo":
                report_fields["xo_date"] = None

        # Handle presentation done
        if is_presentation_done:
            report_fields["last_pres_done_date"] = event_flow.updated_at.strftime(
                "%d.%m.%Y"
            )
            report_fields["last_pres_done_responsible"] = event_flow.plan_responsible_id
            report_fields["pres_count"] = current_pres_count + 1

        # Handle fail
        if event_flow.is_fail:
            if event_flow.fail_type and event_flow.fail_type.get("code") == "failure":
                if event_flow.fail_reason and event_flow.fail_reason.get("code"):
                    report_fields["op_fail_reason"] = event_flow.fail_reason["code"]

        # Update comments
        comment = event_flow.comment
        current_m_comments.insert(
            0, f"{event_flow.updated_at.strftime('%d.%m.%Y')}\n{comment}"
        )

        total_comments_count = 30 if event_flow.domain == "gsirk.bitrix24.ru" else 12
        if len(current_m_comments) > total_comments_count:
            current_m_comments = current_m_comments[:total_comments_count]
        if len(current_pres_comments) > 15:
            current_pres_comments = current_pres_comments[:15]

        report_fields["op_mhistory"] = current_m_comments
        report_fields["pres_comments"] = current_pres_comments

        # Generate batch command
        entity_command = await self._generate_batch_command(
            event_flow,
            current_btx_entity,
            portal_entity_data,
            entity_type,
            entity_id,
            report_fields,
        )

        return entity_command

    async def _generate_batch_command(
        self,
        event_flow: EventFlow,
        current_btx_entity: Dict[str, Any],
        portal_entity_data: Dict[str, Any],
        entity_type: str,
        entity_id: str,
        report_fields: Dict[str, Any],
    ) -> str:
        """
        Generate batch command for Bitrix24 API
        """
        # Implementation of batch command generation
        # This would contain the actual Bitrix24 API calls
        return ""

    async def get_smart_flow(
        self, event_flow: EventFlow, current_smart_id: str, current_deals_ids: List[str]
    ) -> str:
        """
        Implementation of getSmartFlow from PHP code
        """
        # Implementation of smart flow processing
        return ""

    async def get_task_flow(
        self, event_flow: EventFlow, current_smart_id: str, current_deals_ids: List[str]
    ) -> str:
        """
        Implementation of taskFlow from PHP code
        """
        # Implementation of task flow processing
        return ""

    async def get_list_flow(self, event_flow: EventFlow) -> str:
        """
        Implementation of getListFlow from PHP code
        """
        # Implementation of list flow processing
        return ""

    async def execute_batch_commands(self, commands: str) -> Dict[str, Any]:
        """
        Execute batch commands in Bitrix24
        """
        # Implementation of batch command execution
        return {}

    async def fail_flow(self, event_flow: EventFlow) -> None:
        if event_flow.fail_type and event_flow.fail_type.get("code") == "failure":
            if event_flow.fail_reason and event_flow.fail_reason.get("code"):
                event_flow.metadata["op_fail_reason"] = event_flow.fail_reason["code"]

        event_flow.status = EventFlowStatus.FAIL
        event_flow.work_status = WorkStatusCode.FAIL
        await self.update_entity_history(event_flow)

    async def relation_lead_flow(self, event_flow: EventFlow) -> None:
        if event_flow.current_btx_entity and event_flow.current_btx_entity.get(
            "LEAD_ID"
        ):
            lead_id = event_flow.current_btx_entity["LEAD_ID"]
            # Process lead relation
            pass

    async def get_list_batch_flow(self, event_flow: EventFlow) -> None:
        if event_flow.plan_pres_deal_ids:
            await self.get_list_presentation_flow_batch(
                event_flow, event_flow.plan_pres_deal_ids
            )

    async def get_list_presentation_flow_batch(
        self, event_flow: EventFlow, plan_pres_deal_ids: List[str]
    ) -> None:
        # Implement presentation flow batch processing
        pass

    async def handle_deal_flow(self, event: EventFlow) -> EventFlow:
        # Implement deal flow logic
        return event

    async def handle_presentation_flow(self, event: EventFlow) -> EventFlow:
        # Implement presentation flow logic
        return event

    async def handle_lead_flow(self, event: EventFlow) -> EventFlow:
        # Implement lead flow logic
        return event

    async def handle_fail_flow(self, event: EventFlow) -> EventFlow:
        # Implement fail flow logic
        return event

    async def handle_relation_flow(self, event: EventFlow) -> EventFlow:
        # Implement relation flow logic
        return event

    async def update_entity_history(self, event: EventFlow) -> None:
        history_entry = {
            "timestamp": datetime.now().isoformat(),
            "event_id": event.id,
            "status": event.status,
            "work_status": event.work_status,
            "comment": event.comment,
        }

        if "history" not in event.metadata:
            event.metadata["history"] = []

        event.metadata["history"].append(history_entry)
        await self.event_repository.update(event)
