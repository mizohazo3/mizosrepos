# Technical Context

## Technologies Used

### Frontend
- **HTML5**: Structure and content markup
- **CSS3**: Styling and responsive design
- **JavaScript (ES6+)**: Client-side logic and UI interaction
- **Custom Web Fonts**: DSEG7 for digital clock display, Inter, JetBrains Mono, and Roboto Mono
- **Template System**: Native HTML templates for component rendering

### Backend
- **PHP 7+**: Server-side logic and API endpoints
- **MySQL**: Relational database for data persistence
- **JSON**: Data interchange format between client and server
- **PDO**: Database abstraction layer for MySQL connection

### Development Tools
- **SFTP**: Direct deployment to server via SFTP protocol
- **Laragon**: Local development environment for Windows
- **Visual Studio Code**: Code editor with PHP and JavaScript support

## Development Setup
- Local development using Laragon (WAMP stack) on Windows
- Production deployment on shared hosting via SFTP
- Database setup script (`setup_db.php`) for schema creation
- Manual deployments to the production server

## Technical Constraints

### Database
- MySQL with InnoDB engine for transaction support
- Connection parameters:
  - Host: `localhost`
  - Database: `mcgkxyz_timer_app`
  - Username: `mcgkxyz_masterpop`
  - Character Set: `utf8mb4` with Unicode collation

### Performance
- Client polling interval limited to 5 seconds to reduce server load
- UI updates at 1-second intervals for visual responsiveness
- Optimistic UI updates to minimize perceived latency
- Limited to 250 timer records per user account

### Connectivity
- Grace period handling for network interruptions
- Client-side timer continuation during brief server disconnections
- Error suppression for non-critical API errors

### Security
- Basic CORS headers for cross-origin requests
- Input validation on server-side for all API endpoints
- PDO prepared statements to prevent SQL injection
- Frontend code obfuscation is minimal

## External Dependencies
- Google Fonts API for web font loading
- DSEG7 font hosted on CDN (cdn.jsdelivr.net)
- No third-party JavaScript libraries or frameworks
- No build tools or transpilation steps

## Technical Debt Areas
- Limited test coverage
- Manual deployment process
- Some duplicate code between PHP files
- Hardcoded database credentials in multiple files
- Limited error logging mechanism
- No formal versioning system 