# Codebase Index

This document maps out the entire project structure, detailing the specific logic and responsibilities of each file.

## 1. Configuration & Bootstrap (`src/config/`)

*   **`src/config/database.php`**: Establishes the PDO database connection, sets up the `$db` instance, and initializes the `\Delight\Auth\Auth` system.
*   **`src/config/roles.php`**: Defines the bitmask role constants (`ROLE_ADMIN`, `ROLE_TEACHER`, `ROLE_STUDENT`) used by the authentication system to determine user access.
*   **`src/config/security.php`**: Handles global security configurations. Starts the secure session (forcing HTTPOnly, Secure, and SameSite flags) and defines a global `xss_clean` utility function. Adds standard security headers (X-Frame-Options, X-XSS-Protection).
*   **`src/config/meetings_bootstrap.php`**: A specific bootstrap file that initializes repositories, services, and the database connection specifically required for the Meetings module.

## 2. Middleware (`src/middleware/`)

*   **`src/middleware/AuthMiddleware.php`**: Contains helper functions (`requireLogin`, `redirectIfLoggedIn`, `requireRole`, `hasRole`) to enforce authentication and authorization across routes. Redirects unauthorized users.
*   **`src/middleware/CsrfMiddleware.php`**: Contains functions (`generateCsrfToken`, `validateCsrfToken`) to generate and verify CSRF tokens. Used on all POST requests to prevent Cross-Site Request Forgery.

## 3. Repositories (`src/Repositories/`) - Data Access Layer

*   **`src/Repositories/AnalyticsRepository.php`**: Executes database queries to fetch statistics for the admin dashboard (e.g., total students, total teachers, total classes, upcoming sessions).
*   **`src/Repositories/ClassroomRepository.php`**: Handles CRUD operations and pagination for Classrooms. Contains queries to find classrooms specifically assigned to a teacher or student.
*   **`src/Repositories/ClassSessionRepository.php`**: Handles CRUD for Class Sessions. Contains logic to check for duplicate session schedules to prevent overlapping meetings.
*   **`src/Repositories/SessionMeetingRepository.php`**: Saves the actual meeting links and provider responses (Zoom/Google API payloads).
*   **`src/Repositories/MeetingJobRepository.php`**: Tracks the status (success/failed) of background meeting generation jobs.
*   **`src/Repositories/ProviderAccountRepository.php`**: Manages OAuth credentials for Google Meet and Zoom. **Crucially, uses AES-256-CBC encryption (`openssl_encrypt` / `openssl_decrypt`) to securely store API tokens, Client IDs, and Client Secrets in the database.**

## 4. Services (`src/Services/`) - Business Logic Layer

*   **`src/Services/AnalyticsService.php`**: Formats and processes the raw data fetched by `AnalyticsRepository` for the dashboard controllers.
*   **`src/Services/ClassroomService.php`**: Contains the business logic for creating and updating classrooms. It validates inputs, ensuring that the selected teacher has `ROLE_TEACHER` and the student has `ROLE_STUDENT` before creating the database record.
*   **`src/Services/Sessions/ClassSessionService.php`**: Contains the business logic for managing class sessions (scheduling, updating, cancelling) and handles integration with `MeetingService` for generating associated meetings.
*   **`src/Services/Meetings/MeetingService.php`**: The core orchestrator for meetings. It uses the `MeetingProviderFactory` to generate meetings (Google/Zoom), saves the links using `SessionMeetingRepository`, handles retries, and coordinates the cancellation of meetings.
*   **`src/Services/Meetings/MeetingProviderFactory.php`**: A factory class that instantiates and returns the correct provider implementation (`GoogleMeetProvider` or `ZoomProvider`) based on the session's configuration.
*   **`src/Services/Meetings/Providers/MeetingProviderInterface.php`**: The interface that enforces a standard contract (`createMeeting`, `cancelMeeting`) for all meeting providers.
*   **`src/Services/Meetings/Providers/GoogleMeetProvider.php`**: Implements the Google Calendar/Meet API logic. Handles OAuth2 token refreshing, constructs event payloads, and parses the Google API responses to extract the `meet.google.com` link.
*   **`src/Services/Meetings/Providers/ZoomProvider.php`**: Implements the Zoom API logic for scheduling meetings.
*   **`src/Services/Meetings/DTO/...`**: Data Transfer Objects used to strictly type the data passed to and returned from providers.

## 5. Controllers (`src/Controllers/`) - Request Handling

*   **`src/Controllers/Admin/DashboardController.php`**: Handles the logic for rendering the Admin dashboard, fetching analytics and active notices.
*   **`src/Controllers/Admin/AdminMeetingSettingsController.php`**: Manages the API settings page. Handles the OAuth callback flow for Google (exchanging authorization code for tokens) and saving encrypted credentials.
*   **`src/Controllers/Admin/AdminClassroomController.php`**: Manages CRUD operations for classrooms from the admin panel.
*   **`src/Controllers/Admin/AdminSessionController.php`**: Handles scheduling, editing, listing, canceling, and retrying class sessions and their associated meetings from the admin panel.
*   **`src/Controllers/Student/StudentClassroomController.php`**: Handles fetching and displaying classrooms assigned to the logged-in student.
*   **`src/Controllers/Student/StudentSessionController.php`**: Fetches and paginates upcoming and past sessions for a specific student.
*   **`src/Controllers/Teacher/TeacherClassroomController.php`**: Handles fetching and displaying classrooms assigned to the logged-in teacher.
*   **`src/Controllers/Teacher/TeacherSessionController.php`**: Fetches and paginates upcoming and past sessions for a specific teacher.
*   **`src/Controllers/Session/SessionJoinController.php`**: Handles the logic when a user clicks "Join". It validates if the user has permission to join the specific session, logs the join attempt, and redirects them to the actual provider URL.

## 6. Public Entry Points & Views (`public/` & `views/`)

### Authentication & Profile
*   **`public/index.php`**: The main login page. Sanitizes inputs, handles Delight Auth login flow, implements basic session fixation prevention, and redirects on success.
*   **`public/logout.php`**: Handles secure logout, destroying the session and clearing Delight Auth tokens.
*   **`public/profile.php`**: Displays the logged-in user's profile and system role.
*   **`public/change_password.php`**: Handles password updates with strength validation.

### Admin Panel (`public/admin/`)
*   **`dashboard.php`**: Admin dashboard entry point.
*   **`students.php` / `teachers.php`**: Handles listing, creating, editing, and banning users. Uses Delight Auth's admin functionality to register users and assign bitmask roles.
*   **`classrooms/index.php`, `create.php`, `edit.php`**: Entry points for Classroom CRUD.
*   **`notices/index.php`, `create.php`, `edit.php`**: Entry points for Notice CRUD. Can target notices to students, teachers, or both.
*   **`sessions/`**: Directory containing entry points for scheduling and managing meeting sessions.
*   **`settings/`**: Contains entry points for configuring Google/Zoom API integrations.

### Teacher Panel (`public/teacher/`)
*   **`dashboard.php`**: Teacher dashboard showing relevant notices.
*   **`classrooms.php` & `classroom_details.php`**: Lists classrooms assigned to the logged-in teacher and shows details. Enforces authorization checks.
*   **`sessions.php`**: Lists scheduled meetings for the teacher.

### Student Panel (`public/student/`)
*   **`dashboard.php`**: Student dashboard showing relevant notices.
*   **`classrooms.php` & `classroom_details.php`**: Lists classrooms assigned to the logged-in student and shows details. Enforces authorization checks.
*   **`sessions.php`**: Lists scheduled meetings for the student.

## 7. Background Tasks (`cron/`)

*   **`cron/process_meeting_jobs.php`**: A CLI script designed to run in the background (via cron). It processes pending meeting generation jobs in bulk to prevent timeout errors when scheduling multiple sessions at once.

## 8. Database Schemas (`database/`)

*   **`setup.sql`**: The official schema for `delight-im/auth`, containing `users`, `users_2fa`, `users_audit_log`, `users_remembered`, etc.
*   **`classroom_schema.sql`**: Schema for the `classrooms` table.
*   **`meetings_schema.sql`**: Extensive schema for the meeting integration, including `provider_accounts`, `class_sessions`, `session_meetings`, and job tracking tables.
*   **`notices_schema.sql`**: Schema for the `notices` announcement system.
*   **`permissions_schema.sql`**: A future-ready schema layout for granular Role-Based Access Control (RBAC), meant to eventually replace/augment the simple bitmask roles.

---

*This document serves as the master index for the system's architecture and execution flow.*
