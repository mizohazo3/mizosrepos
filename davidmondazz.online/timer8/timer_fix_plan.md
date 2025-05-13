# Timer Timezone Fix Plan

## Problem

The timer in `index.php` starts counting from 2 hours instead of zero, and when stopped, it displays a time that's 2 hours ahead of the actual stop time. This indicates a timezone inconsistency between the server (PHP/MySQL) and the client (JavaScript).

## Analysis

- **Server Timezone:** `timezone_config.php` sets the PHP default timezone to 'Africa/Cairo' (UTC+2).
- **Data Fetching (`api/get_data.php`):** The `start_time` is fetched directly from the database (likely stored in UTC) and sent to the frontend without explicit timezone conversion in PHP.
- **JavaScript Parsing (`script.js`):**
    - The `calculateCurrentSeconds` function parses the `start_time` string using `new Date(timerData.start_time.replace(' ', 'T'))`.
    - Without a timezone specifier ('Z' or offset), `new Date()` assumes the string represents the *browser's local time* (Africa/Cairo, UTC+2).
    - This mismatch (parsing a UTC string as local time) causes the `startTime` object in JS to be 2 hours earlier than the actual UTC start time.
    - Comparing `Date.now()` (UTC) with the incorrect `startTime.getTime()` results in an `elapsedSeconds` calculation that is ~2 hours too high, explaining the initial 2-hour display.
- **Stop Action:** The backend likely calculates the final duration correctly. The frontend displays the `accumulated_seconds` received from the server upon stopping, which appears correct relative to the *actual* duration, but the *running* display was wrong.

## Proposed Solution

1.  **Modify `script.js`:** Adjust line 222 in the `calculateCurrentSeconds` function to explicitly parse the server's `start_time` string as UTC by appending 'Z'.

    ```javascript
    // Change this:
    const startTime = new Date(timerData.start_time.replace(' ', 'T'));
    // To this:
    const startTime = new Date(timerData.start_time.replace(' ', 'T') + 'Z'); // Append 'Z' to specify UTC
    ```

2.  **Verify Backend Stop Logic (`api/timer_action.php`):** Examine the 'stop' action logic to ensure the final `accumulated_seconds` calculation uses consistent timezones (preferably UTC for both start and stop times) before saving to the database.

3.  **Test:** Verify timers start at 00:00:00, the running time progresses correctly, and the final stopped time matches the actual duration.

## Diagram

```mermaid
sequenceDiagram
    participant Browser (JS)
    participant Server (PHP API)
    participant Database (MySQL)

    Browser (JS)->>Server (PHP API): GET /api/get_data.php
    Server (PHP API)->>Database (MySQL): SELECT start_time FROM timers WHERE id = ?
    Database (MySQL)-->>Server (PHP API): Returns start_time (e.g., "2025-04-24 07:55:00" - UTC)
    Server (PHP API)-->>Browser (JS): Sends JSON { ..., start_time: "2025-04-24 07:55:00", ... }

    Note over Browser (JS): Timer starts running... UI Tick calls calculateCurrentSeconds()
    Browser (JS)->Browser (JS): calculateCurrentSeconds(timerData)
    Note right of Browser (JS): OLD: new Date("2025-04-24T07:55:00") -> Parsed as Local (UTC+2) -> WRONG
    Note right of Browser (JS): NEW: new Date("2025-04-24T07:55:00Z") -> Parsed as UTC -> CORRECT
    Browser (JS)->Browser (JS): elapsed = Date.now() - startTime.getTime()
    Note right of Browser (JS): Correct elapsed time calculated
    Browser (JS)->>Browser (JS): Updates Timer Display (Starts from 00:00:00)

    Browser (JS)->>Server (PHP API): POST /api/timer_action.php (action: 'stop')
    Server (PHP API)->>Database (MySQL): Calculates duration (using consistent TZ), UPDATE timers SET accumulated_seconds = ?, is_running = 0 WHERE id = ?
    Database (MySQL)-->>Server (PHP API): Update OK
    Server (PHP API)-->>Browser (JS): Sends JSON { ..., accumulated_seconds: CORRECT_VALUE, ... }
    Browser (JS)->>Browser (JS): Updates Timer Display (Shows correct final time)