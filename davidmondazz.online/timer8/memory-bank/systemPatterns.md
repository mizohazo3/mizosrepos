# System Patterns

## Architecture Overview
Timer8 Online follows a classic client-server architecture with a clear separation between frontend and backend:

```mermaid
flowchart TD
    Client[Client Browser] <--> API[PHP API Endpoints]
    API <--> DB[(MySQL Database)]
    
    subgraph Frontend
        UI[HTML/CSS UI] --> JS[JavaScript Controllers]
        JS --> Timer[Timer Logic]
        JS --> Sync[Data Synchronization]
        JS --> Render[UI Rendering]
    end
    
    subgraph Backend
        API --> Auth[Authentication] 
        API --> TimerOps[Timer Operations]
        API --> Progress[Progress Tracking]
        API --> Economy[Economy System]
    end
```

## Key Design Patterns

### 1. State Management
- Client-side timers are maintained in a JavaScript object acting as a state store
- Polling mechanism periodically synchronizes client state with server
- Optimistic UI updates with server validation

### 2. Service-Oriented API
- RESTful PHP endpoints handle specific timer operations
- Endpoints are organized by function (timer actions, data retrieval, marketplace operations)
- JSON response format for consistent data exchange

### 3. Data Persistence
- MySQL database stores timer data, user progress, and economy information
- Timer state (running/stopped) and accumulated time are persisted to survive page refreshes
- Transaction-based updates to ensure data integrity for critical operations

### 4. UI Component System
- Template-based rendering for timer components
- Event delegation for efficient event handling
- CSS classes control visual state (running, stopped, pinned)

### 5. Interval-Based Processing
- Polling interval for server synchronization (5 seconds)
- UI tick interval for visual updates (1 second)
- Both optimized to balance responsiveness with performance

## Core Technical Components

### Frontend
- **HTML Templates**: Define the structure of timer components
- **CSS Styling**: Responsive design with visual state indicators
- **JavaScript Controllers**: Handle user interactions and timer logic
- **State Synchronization**: Keep client and server data in sync

### Backend
- **PHP API Endpoints**: Process timer actions and data requests
- **MySQL Database**: Store persistent timer and user data
- **Level System**: Track and manage user progression
- **Economy System**: Handle currency accumulation and spending

## Data Flow Pattern

```mermaid
sequenceDiagram
    participant User
    participant UI
    participant JS as JavaScript Logic
    participant API as PHP API
    participant DB as Database
    
    User->>UI: Interact with timer
    UI->>JS: Trigger action
    JS->>UI: Update UI (optimistic)
    JS->>API: Send action to server
    API->>DB: Update database
    API->>JS: Confirm success/failure
    JS->>UI: Update UI (confirmed)
    
    loop Every 5 seconds
        JS->>API: Poll for updates
        API->>DB: Fetch current state
        API->>JS: Return state data
        JS->>UI: Update UI if changed
    end
```

## Error Handling Pattern
- Client-side error detection with retry mechanisms
- Server-side validation with descriptive error responses
- Grace period for network disruptions (timers continue running client-side)
- Console logging for development diagnostics
- User-friendly error notifications for critical failures 