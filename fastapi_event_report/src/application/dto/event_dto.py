from datetime import datetime
from typing import Optional, Dict, Any
from pydantic import BaseModel, Field
from domain.entities.event import EventType, EventStatus, WorkStatus


class EventCreateDTO(BaseModel):
    type: EventType
    entity_id: str
    entity_type: str
    domain: str
    hook: str
    status: EventStatus = EventStatus.PLAN
    work_status: WorkStatus = WorkStatus.IN_JOB
    responsible_id: Optional[str] = None
    deadline: Optional[datetime] = None
    comment: Optional[str] = None
    metadata: Dict[str, Any] = Field(default_factory=dict)


class EventUpdateDTO(BaseModel):
    status: Optional[EventStatus] = None
    work_status: Optional[WorkStatus] = None
    responsible_id: Optional[str] = None
    deadline: Optional[datetime] = None
    comment: Optional[str] = None
    metadata: Optional[Dict[str, Any]] = None
