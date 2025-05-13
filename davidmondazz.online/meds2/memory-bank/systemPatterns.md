# System Patterns

## Architecture Overview
The system appears to follow a traditional PHP web application architecture with:
- PHP backend scripts for data processing and business logic
- MySQL database for data storage
- JavaScript for frontend interactivity
- HTML/CSS for presentation

## Key Design Patterns
- **Page-based routing**: Different PHP files handle specific functionality (index.php, search.php, etc.)
- **Function-based organization**: Core functionality is modularized in med_functions.php
- **Direct database interactions**: PHP scripts connect directly to the database
- **Form-based data submission**: User data is submitted through HTML forms to PHP processing scripts

## Component Relationships
- **Core application**: index.php appears to be the main entry point
- **Medication management**: 
  - delete_med.php - For removing medications
  - fetch_med_details.php - For retrieving medication information
- **Side effects tracking**:
  - side_investigation.php - For analyzing side effects
  - possible_sides.php - For listing potential side effects
- **Search functionality**:
  - search.php - Search interface
  - search_med_filter.php - Filtering medications
  - search_mySus.php - Searching suspected issues
- **Daily tracking**:
  - daypage.php - Daily medication tracking

## Data Flow
Based on file names, the system likely follows this data flow:
1. User adds/manages medications through the main interface
2. System tracks medication usage over time
3. User reports side effects and experiences
4. System provides insights on medication combinations and potential issues
5. User can search historical data and medication information

*Note: This is an initial understanding based on file structure. The actual architecture and patterns will be refined as we examine the codebase in more detail.* 