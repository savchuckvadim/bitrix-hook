from datetime import datetime
from typing import Optional, Dict, Any, List
from pydantic import BaseModel, Field
from enum import Enum


class EventFlowType(str, Enum):
    DEAL = "deal"
    PRESENTATION = "presentation"
    LEAD = "lead"
    COMPANY = "company"
    TMC = "tmc"
    SMART = "smart"
    TASK = "task"
    LIST = "list"


class EventFlowStatus(str, Enum):
    PLAN = "plan"
    DONE = "done"
    EXPIRED = "expired"
    FAIL = "fail"
    NO_CALL = "noCall"


class WorkStatusCode(str, Enum):
    IN_JOB = "inJob"
    SET_ASIDE = "setAside"
    FAIL = "fail"


class EventFlow(BaseModel):
    # Basic fields
    id: Optional[str] = None
    type: EventFlowType
    entity_id: str
    entity_type: str
    domain: str
    hook: str
    status: EventFlowStatus
    work_status: WorkStatusCode

    # Timestamps
    created_at: datetime = Field(default_factory=datetime.now)
    updated_at: datetime = Field(default_factory=datetime.now)

    # User and responsibility
    responsible_id: Optional[str] = None
    current_user: Optional[Dict[str, Any]] = None
    current_user_name: Optional[str] = None

    # Event specific
    is_deal_flow: bool = False
    is_no_call: bool = False
    is_presentation_done: bool = False
    is_unplanned_presentation: bool = False
    is_fail: bool = False
    is_post_sale: bool = False

    # Deal specific
    portal_deal_data: Optional[Dict[str, Any]] = None
    current_deal: Optional[Dict[str, Any]] = None
    current_deal_id: Optional[str] = None
    current_deals_ids: Optional[List[str]] = None

    # Company specific
    portal_company_data: Optional[Dict[str, Any]] = None
    current_btx_entity: Optional[Dict[str, Any]] = None

    # Smart specific
    current_smart: Optional[Dict[str, Any]] = None
    current_smart_id: Optional[str] = None

    # Task specific
    current_task: Optional[Dict[str, Any]] = None

    # Presentation specific
    presentation: Optional[Dict[str, Any]] = None
    plan_pres_deal_ids: Optional[List[str]] = None

    # Status and results
    result_status: Optional[str] = None  # result, noresult, expired
    fail_type: Optional[Dict[str, Any]] = None
    fail_reason: Optional[Dict[str, Any]] = None
    noresult_reason: Optional[Dict[str, Any]] = None

    # Event types
    current_report_event_type: Optional[str] = None
    current_report_event_name: Optional[str] = None
    current_plan_event_type: Optional[str] = None
    current_plan_event_name: Optional[str] = None

    # Planning
    plan_created_id: Optional[str] = None
    plan_responsible_id: Optional[str] = None
    plan_deadline: Optional[datetime] = None

    # Comments and history
    comment: Optional[str] = None
    metadata: Dict[str, Any] = Field(default_factory=dict)

    # Department
    current_department_type: Optional[str] = None

    # Relations
    relation_sale_pres_deal: Optional[Dict[str, Any]] = None

    class Config:
        from_attributes = True
