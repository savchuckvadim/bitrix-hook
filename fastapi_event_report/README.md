# Event Report API

A modern FastAPI implementation of the event reporting system following Domain-Driven Design (DDD) principles and onion architecture.

## Project Structure

```
fastapi_event_report/
├── src/
│   ├── domain/                 # Domain layer
│   │   ├── entities/          # Business entities
│   │   ├── repositories/      # Repository interfaces
│   │   └── services/          # Domain services
│   ├── application/           # Application layer
│   │   ├── dto/              # Data Transfer Objects
│   │   └── services/         # Application services
│   ├── infrastructure/        # Infrastructure layer
│   │   └── repositories/     # Repository implementations
│   └── interfaces/           # Interface layer
│       └── api/              # API endpoints
└── requirements.txt          # Python dependencies
```

## Features

-   Clean architecture with clear separation of concerns
-   Type-safe with Pydantic models
-   Async/await support
-   MongoDB integration
-   RESTful API endpoints
-   Event processing with different flows (deal, presentation, lead)
-   Entity history tracking

## Setup

1. Create a virtual environment:

```bash
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
```

2. Install dependencies:

```bash
pip install -r requirements.txt
```

3. Set up environment variables:
   Create a `.env` file with:

```
MONGODB_URL=mongodb://localhost:27017
DATABASE_NAME=event_report
```

4. Run the application:

```bash
uvicorn src.main:app --reload
```

## API Documentation

Once the server is running, you can access:

-   Swagger UI: http://localhost:8000/docs
-   ReDoc: http://localhost:8000/redoc

## Key Components

### Domain Layer

-   `Event` entity with typed fields
-   Repository interfaces
-   Domain service interfaces

### Application Layer

-   DTOs for data validation
-   Application services implementing business logic
-   Event processing flows

### Infrastructure Layer

-   MongoDB repository implementation
-   Database connection handling

### Interface Layer

-   FastAPI routes
-   Request/response models
-   Error handling

## Development

1. Follow the onion architecture principles
2. Keep domain logic in the domain layer
3. Use dependency injection for services
4. Write tests for each layer
5. Maintain type safety with Pydantic
