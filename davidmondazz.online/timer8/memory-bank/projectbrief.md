# Project Brief: Timer8 Online

## Overview
Timer8 Online is a web-based timer application that allows users to track time spent on various activities. The system includes reward mechanisms, leveling, and a virtual bank that accumulates currency based on time tracked. 

## Core Requirements

1. **Timer Management**
   - Create, start, stop, and reset multiple timers
   - Track accumulated time across sessions
   - Pin important timers for easy access

2. **User Progression System**
   - Level progression based on accumulated hours
   - Different ranks (Novice, Apprentice, Intermediate, etc.)
   - Increasing reward rates as users level up

3. **Virtual Economy**
   - Bank system accumulating currency based on time tracked
   - Reward rate tied to user level
   - Marketplace for spending accumulated currency

4. **Usability Features**
   - Real-time updates of running timers
   - Visual indicators of timer status
   - Progress tracking toward next level
   - Offline capability with data synchronization

## Technical Goals
- Reliable timer operation with accurate time tracking
- Responsive design that works across devices
- Persistent data storage in MySQL database
- Clean separation between frontend (JavaScript) and backend (PHP/MySQL)
- Efficient API communication between client and server

## Success Criteria
- Users can reliably track time across multiple activities
- System accurately calculates rewards and progression
- User interface provides clear feedback on timer status
- Data persists reliably between sessions
- Application performs well with many concurrent timers 