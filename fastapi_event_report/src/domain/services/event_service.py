from abc import ABC, abstractmethod
from typing import Dict, Any, Optional
from ..entities.event import Event


class EventService(ABC):
    @abstractmethod
    async def process_event(self, event_data: Dict[str, Any]) -> Event:
        pass

    @abstractmethod
    async def handle_deal_flow(self, event: Event) -> Event:
        pass

    @abstractmethod
    async def handle_presentation_flow(self, event: Event) -> Event:
        pass

    @abstractmethod
    async def handle_lead_flow(self, event: Event) -> Event:
        pass

    @abstractmethod
    async def handle_fail_flow(self, event: Event) -> Event:
        pass

    @abstractmethod
    async def handle_relation_flow(self, event: Event) -> Event:
        pass

    @abstractmethod
    async def update_entity_history(self, event: Event) -> None:
        pass
