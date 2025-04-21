from fastapi import APIRouter, Depends, HTTPException
from typing import List
from application.dto.event_dto import EventCreateDTO, EventUpdateDTO
from domain.entities.event import Event
from application.services.event_processing_service import EventProcessingService
from domain.repositories.event_repository import EventRepository

router = APIRouter(prefix="/events", tags=["events"])


def get_event_service(
    event_repository: EventRepository = Depends(),
) -> EventProcessingService:
    return EventProcessingService(event_repository)


@router.post("/", response_model=Event)
async def create_event(
    event_data: EventCreateDTO,
    event_service: EventProcessingService = Depends(get_event_service),
):
    try:
        event = await event_service.process_event(event_data.dict())
        return event
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))


@router.get("/{event_id}", response_model=Event)
async def get_event(
    event_id: str, event_service: EventProcessingService = Depends(get_event_service)
):
    event = await event_service.event_repository.find_by_id(event_id)
    if not event:
        raise HTTPException(status_code=404, detail="Event not found")
    return event


@router.get("/entity/{entity_id}/{entity_type}", response_model=List[Event])
async def get_entity_events(
    entity_id: str,
    entity_type: str,
    event_service: EventProcessingService = Depends(get_event_service),
):
    events = await event_service.event_repository.find_by_entity_id(
        entity_id, entity_type
    )
    return events


@router.put("/{event_id}", response_model=Event)
async def update_event(
    event_id: str,
    event_data: EventUpdateDTO,
    event_service: EventProcessingService = Depends(get_event_service),
):
    event = await event_service.event_repository.find_by_id(event_id)
    if not event:
        raise HTTPException(status_code=404, detail="Event not found")

    # Update event fields
    for field, value in event_data.dict(exclude_unset=True).items():
        setattr(event, field, value)

    updated_event = await event_service.event_repository.update(event)
    return updated_event


@router.delete("/{event_id}")
async def delete_event(
    event_id: str, event_service: EventProcessingService = Depends(get_event_service)
):
    success = await event_service.event_repository.delete(event_id)
    if not success:
        raise HTTPException(status_code=404, detail="Event not found")
    return {"message": "Event deleted successfully"}
