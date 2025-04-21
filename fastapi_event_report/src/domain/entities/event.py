from datetime import datetime
from enum import Enum
from typing import Optional, List, Dict, Any
from pydantic import BaseModel, Field


class EventType(str, Enum):
    DEAL = "deal"
    PRESENTATION = "presentation"
    LEAD = "lead"
    COMPANY = "company"
    TMC = "tmc"


class EventStatus(str, Enum):
    PLAN = "plan"
    DONE = "done"
    EXPIRED = "expired"
    FAIL = "fail"


class WorkStatus(str, Enum):
    IN_JOB = "inJob"
    SET_ASIDE = "setAside"
    FAIL = "fail"


class Event(BaseModel):
    id: Optional[str] = None
    type: EventType
    entity_id: str
    entity_type: str
    domain: str
    hook: str
    status: EventStatus
    work_status: WorkStatus
    created_at: datetime = Field(default_factory=datetime.now)
    updated_at: datetime = Field(default_factory=datetime.now)
    responsible_id: Optional[str] = None
    deadline: Optional[datetime] = None
    comment: Optional[str] = None
    metadata: Dict[str, Any] = Field(default_factory=dict)

    class Config:
        from_attributes = True
