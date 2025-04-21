from fastapi import FastAPI
from interfaces.api.routes import event_routes
from infrastructure.repositories.event_repository_impl import EventRepositoryImpl

app = FastAPI(
    title="Event Report API",
    description="API for managing event reports with DDD architecture",
    version="1.0.0",
)

# Include routers
app.include_router(event_routes.router)


# Dependency injection setup
@app.on_event("startup")
async def startup_event():
    # Initialize repositories and services
    app.state.event_repository = EventRepositoryImpl()


@app.get("/")
async def root():
    return {"message": "Event Report API is running"}
