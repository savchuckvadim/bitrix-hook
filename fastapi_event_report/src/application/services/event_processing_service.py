from typing import Dict, Any
from ..dto.event_dto import EventCreateDTO, EventUpdateDTO
from domain.entities.event import Event, EventType, EventStatus, WorkStatus
from domain.services.event_service import EventService
from domain.repositories.event_repository import EventRepository
from datetime import datetime


class EventProcessingService(EventService):
    def __init__(self, event_repository: EventRepository):
        self.event_repository = event_repository

    async def process_event(self, event_data: Dict[str, Any]) -> Event:
        # Create event from DTO
        event = Event(
            type=EventType(event_data.get("type", "deal")),
            entity_id=event_data["entity_id"],
            entity_type=event_data["entity_type"],
            domain=event_data["domain"],
            hook=event_data["hook"],
            status=EventStatus(event_data.get("status", "plan")),
            work_status=WorkStatus(event_data.get("work_status", "inJob")),
            responsible_id=event_data.get("responsible_id"),
            deadline=event_data.get("deadline"),
            comment=event_data.get("comment"),
            metadata=event_data.get("metadata", {}),
        )

        # Save event
        event = await self.event_repository.save(event)

        # Process based on event type
        if event.type == EventType.DEAL:
            event = await self.handle_deal_flow(event)
        elif event.type == EventType.PRESENTATION:
            event = await self.handle_presentation_flow(event)
        elif event.type == EventType.LEAD:
            event = await self.handle_lead_flow(event)

        # Update history
        await self.update_entity_history(event)

        return event

    async def handle_deal_flow(self, event: Event) -> Event:
        # Implement deal flow logic
        event.metadata["deal_processed"] = True
        event.updated_at = datetime.now()
        return await self.event_repository.update(event)

    async def handle_presentation_flow(self, event: Event) -> Event:
        # Implement presentation flow logic
        event.metadata["presentation_processed"] = True
        event.updated_at = datetime.now()
        return await self.event_repository.update(event)

    async def handle_lead_flow(self, event: Event) -> Event:
        # Implement lead flow logic
        event.metadata["lead_processed"] = True
        event.updated_at = datetime.now()
        return await self.event_repository.update(event)

    async def handle_fail_flow(self, event: Event) -> Event:
        # Implement fail flow logic
        event.status = EventStatus.FAIL
        event.updated_at = datetime.now()
        return await self.event_repository.update(event)

    async def handle_relation_flow(self, event: Event) -> Event:
        # Implement relation flow logic
        event.metadata["relation_processed"] = True
        event.updated_at = datetime.now()
        return await self.event_repository.update(event)

    async def update_entity_history(self, event: Event) -> None:
        # Implement history update logic
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
