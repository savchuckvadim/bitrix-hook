from abc import ABC, abstractmethod
from typing import Optional, List
from ..entities.event import Event


class EventRepository(ABC):
    @abstractmethod
    async def save(self, event: Event) -> Event:
        pass

    @abstractmethod
    async def find_by_id(self, event_id: str) -> Optional[Event]:
        pass

    @abstractmethod
    async def find_by_entity_id(self, entity_id: str, entity_type: str) -> List[Event]:
        pass

    @abstractmethod
    async def update(self, event: Event) -> Event:
        pass

    @abstractmethod
    async def delete(self, event_id: str) -> bool:
        pass
