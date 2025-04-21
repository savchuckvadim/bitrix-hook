from typing import Dict, Any, Optional, List
from datetime import datetime
from domain.entities.event_flow import EventFlow


class EntityFlowService:
    async def get_entity_flow(
        self,
        event_flow: EventFlow,
        is_deal: bool = False,
        deal: Optional[Dict[str, Any]] = None,
        deal_type: str = "base",
        base_deal_id: Optional[str] = None,
        deal_event_type: bool = False,
    ) -> None:
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

        report_fields["next_pres_plan_date"] = None
        report_fields["call_next_date"] = None

        if current_report_event_type:
            report_fields["call_last_date"] = event_flow.updated_at.strftime("%d.%m.%Y")

            if current_report_event_type == "xo":
                report_fields["xo_date"] = None

        if is_presentation_done:
            report_fields["last_pres_done_date"] = event_flow.updated_at.strftime(
                "%d.%m.%Y"
            )
            report_fields["last_pres_done_responsible"] = event_flow.plan_responsible_id
            report_fields["pres_count"] = current_pres_count + 1

        if event_flow.is_fail:
            if event_flow.fail_type and event_flow.fail_type.get("code") == "failure":
                if event_flow.fail_reason and event_flow.fail_reason.get("code"):
                    report_fields["op_fail_reason"] = event_flow.fail_reason["code"]

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

        # Update entity with report fields
        await self._update_entity(
            event_flow,
            current_btx_entity,
            portal_entity_data,
            entity_type,
            entity_id,
            report_fields,
        )

    async def _update_entity(
        self,
        event_flow: EventFlow,
        current_btx_entity: Dict[str, Any],
        portal_entity_data: Dict[str, Any],
        entity_type: str,
        entity_id: str,
        report_fields: Dict[str, Any],
    ) -> None:
        # Implement entity update logic
        pass
