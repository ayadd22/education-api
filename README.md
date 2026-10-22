<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# Education API

A RESTful educational platform API built with Laravel 12, PHP, and MySQL. The application manages users, courses, lessons, assignments, enrollments, comments, images, and categories through JSON endpoints.

The project demonstrates Laravel API development with role-aware authorization, Form Requests, Policies, PHP Enums, Eloquent relationships, polymorphic relationships, query scopes, a custom validation rule, and a service contract for enrollment business logic.

## Table of Contents

1. [Project Overview](#project-overview)
2. [Features](#features)
3. [Technology Stack](#technology-stack)
4. [System Architecture](#system-architecture)
5. [Design Patterns and Design Decisions](#design-patterns-and-design-decisions)
6. [Database Design](#database-design)
7. [Eloquent Relationships](#eloquent-relationships)
8. [Authentication and Authorization](#authentication-and-authorization)
9. [Enrollment Workflow and Business Rules](#enrollment-workflow-and-business-rules)
10. [Validation and Error Handling](#validation-and-error-handling)
11. [Enums and Query Scopes](#enums-and-query-scopes)
12. [Data Integrity](#data-integrity)
13. [Performance and Reliability](#performance-and-reliability)
14. [API Endpoints](#api-endpoints)
15. [Installation and Setup](#installation-and-setup)
16. [Project Structure](#project-structure)
17. [Design Trade-offs](#design-trade-offs)
18. [Future Improvements](#future-improvements)

## Project Overview

The Education API supports three user roles:

| Role | Responsibilities |
|---|---|
| Student | Browse published courses, enroll in courses, and participate in course discussions where permitted. |
| Teacher | Create and manage their own courses, lessons, and assignments; view relevant discussion activity. |
| Administrator | Perform elevated operations granted by the authorization rules. |

The application is API-focused. Its endpoints return JSON responses; a frontend or Blade-based interface is not part of the application's main functionality.

## Features

- Token-based API authentication through Laravel Sanctum.
- Role-aware course visibility and resource authorization.
- Course, lesson, and assignment management.
- Student enrollment and progress tracking.
- Comments attached to supported resources through polymorphic relationships.
- Image upload and management for supported resources.
- Category associations through a polymorphic pivot table.
- Form Request validation and request-level authorization.
- Policies for resource-level access control.
- PHP Enums for user roles, course levels, and publication status.
- Reusable local scopes for course publication status.
- A custom rule for validating supported video URL hosts.
- A contract and service for enrollment creation and duplicate handling.
- Automated feature tests and a separate manual API integration test.

## Technology Stack

| Technology | Purpose |
|---|---|
| PHP | Backend programming language. |
| Laravel 12 | Routing, middleware, validation, service container, and application framework. |
| MySQL | Main development database. |
| Laravel Sanctum | API token authentication. |
| Eloquent ORM | Database queries, model relationships, casts, and scopes. |
| Form Requests | Centralized request validation and request authorization. |
| Laravel Policies | Resource-level authorization. |
| PHP Enums | Type-safe, explicit role, level, and status values. |
| PHPUnit / Laravel testing tools | Automated feature and API testing. |
| SQLite in-memory | Isolated test database, as configured for automated tests. |
| Laravel Filesystem | Storage of uploaded image files on the configured disk. |

## System Architecture

The application follows Laravel's MVC conventions and separates concerns where doing so provides a meaningful benefit.

### Request flow

```text
Client
  |
  v
API Route and Middleware
  |
  v
Form Request Validation / Authorization
  |
  v
Controller
  |
  +----> Policy authorization, where invoked
  |
  +----> EnrollmentServiceInterface
  |          |
  |          v
  |      EnrollmentService
  |
  v
Eloquent Models and Relationships
  |
  v
Database / Filesystem
  |
  v
JSON Response
```

This is a logical overview, not a claim that every request follows precisely the same sequence. The route, Form Request, controller, and policy determine the actual request path. The enrollment operation uses a dedicated service; not every simple CRUD action needs a service class.

### Responsibilities

**Routes and middleware** define HTTP methods, endpoint mappings, and request-level requirements such as authentication.

**Form Requests** validate incoming fields and may also authorize a request through their `authorize()` method.

**Controllers** coordinate HTTP handling and responses. They should avoid duplicating complex business rules already owned by a service or policy.

**Policies** answer whether a user may perform an action on a resource. Policies do not automatically filter every database query or nested resource collection.

**Services** coordinate a meaningful business operation. `EnrollmentService` centralizes duplicate checking and enrollment creation.

**Contracts** define a service's public capability without coupling its consumers to a particular implementation.

**Models** define attributes, casts, relationships, and reusable query conditions.

**Migrations and database constraints** define persistent structure and protect data integrity.

## Design Patterns and Design Decisions

### 1. Service Layer

The enrollment workflow is implemented in `EnrollmentService`, rather than placing all enrollment-specific business logic in a controller.

Its responsibilities include checking whether a student is already enrolled, creating the enrollment, handling a database uniqueness conflict, and returning a consistent JSON response.

**Why use a service here?** Enrollment has a domain-specific workflow that benefits from being centralized. A service also gives the workflow a single place to test and maintain.

**Why not create a service for every endpoint?** Simple resource operations do not automatically benefit from another layer. Extra classes are worthwhile when they isolate meaningful logic, not when they merely forward method calls.

### 2. Contract and Dependency Injection

`EnrollmentServiceInterface` declares the enrollment operation:

```php
enroll(User $student, int $courseId): JsonResponse
```

`EnrollmentService` implements that interface. The service container can resolve the contract to the concrete implementation when the binding is configured.

**Why use a contract?** The interface defines what callers can expect without exposing the implementation details. It also makes it easier to replace or mock the enrollment implementation in tests. An interface is useful only when the application actually resolves and uses it; an unused contract adds no runtime value.

### 3. Policies

Policies keep resource-level permissions in dedicated classes instead of scattering ownership and role conditions across controllers.

For example, `CoursePolicy` checks course visibility and whether a teacher owns a course before allowing modification. `EnrollmentPolicy` protects a student's enrollment records. The exact effective access still depends on whether the request flow invokes the relevant policy.

### 4. Form Requests and Custom Validation Rules

Form Requests keep input validation close to the request boundary. `VideoUrlRule` encapsulates the checks for supported video URL formats so the validation logic can be reused.

### 5. Eloquent and Polymorphic Relationships

Eloquent relationships describe domain connections and allow the application to navigate related data without manually writing every join. Polymorphic relationships let comments and images belong to more than one supported model type.

### 6. PHP Enums

Enums provide a fixed set of valid values for roles, course levels, and course status. They reduce spelling inconsistencies and make the domain options explicit in code.

## Database Design

The migrations are the source of truth for table names, column definitions, indexes, foreign keys, and delete behavior. The main domain tables cover:

| Table / Model | Purpose | Important design point |
|---|---|---|
| `users` | Accounts for students, teachers, and administrators. | Role is represented by `UserRole`. |
| `courses` | Course information and publication state. | References its teacher and stores level/status values. |
| `lessons` | Individual lessons inside courses. | References the parent course. |
| `assignments` | Assignments belonging to courses. | References the parent course. |
| `enrollments` | Student-course registrations. | Stores progress and enforces uniqueness for the student-course pair. |
| `comments` | User-authored discussion entries. | Uses a polymorphic reference to its target resource. |
| `images` | Metadata for uploaded resource images. | Uses a polymorphic reference and permits one image per supported resource. |
| `categories` | Reusable classification data. | Can be associated through the polymorphic `categorizables` pivot table. |
| `categorizables` | Category-to-resource associations. | Uses a polymorphic pivot and a uniqueness constraint for duplicate associations. |

The database stores polymorphic type aliases through the configured morph map, such as `course`, `lesson`, and `assignment`, rather than relying everywhere on fully qualified PHP class names.

## Eloquent Relationships

| Relationship | Eloquent type | Purpose |
|---|---|---|
| Teacher → Courses | `hasMany` / `belongsTo` | A teacher owns courses through `teacher_id`. |
| Course → Lessons | `hasMany` / `belongsTo` | A course contains lessons. |
| Course → Assignments | `hasMany` / `belongsTo` | A course contains assignments. |
| Student → Enrollments | `hasMany` / `belongsTo` | A student can have multiple enrollment records. |
| Course → Enrollments | `hasMany` / `belongsTo` | A course can have multiple enrolled students. |
| Student ↔ Course | `belongsToMany` through enrollments | Represents course access and carries enrollment-specific data such as progress. |
| User → Comments | `hasMany` / `belongsTo` | A user authors comments. |
| Comment → Target resource | `morphTo` | A comment belongs to a supported commentable model. |
| Course / Lesson / Assignment → Comments | `morphMany` | Supported resources can have comments. |
| Image → Target resource | `morphTo` | An image belongs to a supported imageable model. |
| Course / Lesson / Assignment → Image | `morphOne` | A supported resource has one image. |
| Course ↔ Category | Polymorphic many-to-many | A course can be assigned reusable categories. |

The polymorphic design avoids separate comment and image tables for each resource type. The one-image-per-resource rule is also backed by a database uniqueness constraint on the polymorphic owner columns. The exact related model methods and constraints should be checked against the current model definitions and migrations when the schema changes.

## Authentication and Authorization

Laravel Sanctum provides token-based authentication. Protected routes require the middleware specified in the route definitions. Role helper methods on `User`, including `isStudent()`, `isTeacher()`, and `isAdmin()`, make role checks easier to read.

### Policy rules

The policies supplied for this project implement the following rules:

**`CoursePolicy`**
- Administrators bypass individual policy methods through `before()`.
- Any user who reaches `viewAny` is allowed by that method; actual listing visibility must still be filtered by the query.
- Published courses can be viewed; a teacher can also view their own draft course.
- Only teachers can create courses.
- Only the teacher who owns a course can update or delete it, unless the administrator bypass applies.

**`LessonPolicy`**
- Administrators bypass individual policy methods through `before()`.
- `viewAny` and `view` return `true`.
- Only teachers can create lessons.
- Only the teacher who owns the parent course can update or delete a lesson, unless the administrator bypass applies.

**`AssignmentPolicy`**
- Administrators bypass individual policy methods through `before()`.
- `viewAny` and `view` return `true`.
- Only teachers can create assignments.
- Only the teacher who owns the parent course can update or delete an assignment, unless the administrator bypass applies.

**`EnrollmentPolicy`**
- Administrators bypass individual policy methods through `before()`.
- Only a student can create an enrollment, and the course must be published.
- Viewing or deleting an enrollment is limited to the student who owns it, unless the administrator bypass applies.

**`CommentPolicy`**
- Administrators bypass individual policy methods through `before()`.
- Comment creation resolves the parent course from the selected target resource.
- Teachers may comment on a published course or on a course they own.
- Students may comment only on published courses in which they are enrolled.
- A comment can be deleted by its author, unless the administrator bypass applies.

**`CategoryPolicy`**
- Authenticated users may list and view categories.
- Only administrators may create, update, or delete categories.
- This policy does not define a `before()` method, so its individual methods determine the policy result.

### Important authorization distinction

A policy method returning `true` does not automatically hide draft lessons or assignments. Resource visibility depends on route middleware, Form Request authorization, controller policy calls, and query filtering working together. In particular, nested lesson and assignment endpoints should be reviewed to ensure that unpublished course content is not exposed unintentionally.

## Enrollment Workflow and Business Rules

The enrollment operation is handled by `EnrollmentService` through `EnrollmentServiceInterface`.

1. The service checks whether the student already has an enrollment for the requested course.
2. If a duplicate already exists, the operation returns HTTP `422 Unprocessable Entity` with the configured Arabic duplicate-enrollment message.
3. Otherwise, it creates an enrollment with `progress` initialized to `0`.
4. It loads selected course fields for the success response.
5. A successful creation returns HTTP `201 Created`.
6. If the database unique constraint detects a duplicate race condition, `UniqueConstraintViolationException` is caught and handled as a duplicate-enrollment response.

The initial application-level check improves the normal response path, while the database uniqueness constraint remains necessary for concurrent requests. Application checks alone cannot guarantee uniqueness if two requests arrive at nearly the same time.

## Validation and Error Handling

### Form Requests

Form Requests centralize input validation and may also apply request authorization. They are appropriate for rules about required fields, accepted values, data types, and field formats.

Validation failures should return structured JSON responses appropriate for an API. Authorization failures should remain distinct from validation failures: a valid request can still be forbidden for the current user.

### `VideoUrlRule`

The custom rule accepts a non-empty, parseable URL containing a scheme and host. The permitted schemes are `http` and `https`, and the permitted hosts are:

- `youtube.com`
- `www.youtube.com`
- `youtu.be`
- `vimeo.com`
- `www.vimeo.com`

The rule checks URL structure and host. It does **not** verify that the video exists, is publicly accessible, or can be played. Such checks would require a separate verification strategy and may depend on the video provider.

### Typical HTTP status codes

| Status | Meaning |
|---|---|
| `200 OK` | Successful read or update. |
| `201 Created` | A resource was created successfully. |
| `401 Unauthorized` | Authentication is missing or invalid. |
| `403 Forbidden` | The authenticated user is not permitted to perform the operation. |
| `404 Not Found` | The requested resource does not exist or is not exposed by the endpoint. |
| `422 Unprocessable Entity` | Validation failed or a domain rule, such as duplicate enrollment, was not satisfied. |

The precise response body depends on the relevant controller, Form Request, and service. Consult those implementations and their tests for the exact JSON structure.

## Enums and Query Scopes

### Enums

| Enum | Values | Purpose |
|---|---|---|
| `UserRole` | `student`, `teacher`, `admin` | Defines user roles. |
| `CourseLevel` | `beginner`, `intermediate`, `advanced` | Standardizes course difficulty. |
| `CourseStatus` | `draft`, `published` | Defines publication state. |

The Course model casts its `level` and `status` attributes to the relevant enums. The backed values are persisted in the database, while the application can work with named enum cases.

Enums define valid values; they do not grant permission. Policies and query conditions are responsible for access decisions.

### Local scopes

The Course model defines:

- `published()`: filters courses to published records.
- `draft()`: filters courses to draft records.

Example:

```php
Course::published()->latest()->paginate(15);
```

**Why use local scopes?** They give common query conditions meaningful names, reduce duplicated conditions, and keep query code readable.

The course listing uses the published scope where published-course visibility is required. Keep `draft()` if it is used or intentionally retained for a planned query; if it is unused, it adds no runtime benefit and can be removed after checking references.

A global scope is not needed merely to filter draft courses. Teachers may be allowed to see their own drafts, so publication visibility is context-dependent and is usually clearer when applied deliberately in the relevant query.

## Data Integrity

Important data-integrity measures include:

- A unique constraint on the student-course enrollment pair prevents duplicate enrollment records.
- A unique constraint on the image's polymorphic owner columns enforces one image per supported resource.
- A unique constraint on category associations prevents duplicate pivot rows for the same association.
- Foreign keys and their migration-defined delete behavior preserve referential integrity.
- Enum casts standardize application-level role, level, and status values.
- The enrollment service handles a unique-constraint exception so a concurrent duplicate attempt produces a controlled response instead of an unhandled database error.

Database constraints are essential because application-level checks can be bypassed by concurrent requests or by another process writing directly to the database.

## Performance and Reliability

- Load only the related data required by an endpoint. The course listing uses eager loading for the teacher's selected `id` and `name` fields.
- Avoid returning every column or nested relationship when the client does not need it.
- Use pagination for potentially large collections.
- Keep authorization filtering in the query as well as enforcing action-level permissions where appropriate.
- Retain database uniqueness constraints for rules that must remain true even under concurrent requests.
- Use the automated test suite to verify visibility, ownership, validation, and error responses after changes to policies or relationships.

Performance claims should be checked against the current implementation and actual query logs. In particular, dashboard and comment queries should be inspected for N+1 queries rather than assuming that a relationship automatically loads efficiently.

## API Endpoints

The API provides functionality for these resource areas:

| Resource area | Purpose |
|---|---|
| Authentication | Obtain and revoke API access according to the configured authentication routes. |
| Courses | List, view, create, update, and delete courses according to role and ownership rules. |
| Lessons | Manage lesson resources belonging to courses. |
| Assignments | Manage assignment resources belonging to courses. |
| Enrollments | Enroll students and access enrollment records subject to authorization. |
| Comments | Create and manage comments on supported resources. |
| Images | Upload, retrieve, update, and delete image records/files for supported resources. |
| Categories | Read category data and perform administrator-authorized category management. |

`routes/api.php` is the source of truth for the exact paths, HTTP methods, middleware, and controller actions. This README intentionally does not invent endpoint URLs that have not been checked against the current route file.

## Installation and Setup

### Requirements

- PHP compatible with the project's Laravel 12 dependencies.
- Composer.
- MySQL for the main development database.
- A configured PHP environment with the extensions required by Laravel and the installed packages.

### Setup

1. Clone the repository and enter the project directory.
2. Install dependencies:

   ```bash
   composer install
   ```

3. Create the environment file. On Windows PowerShell:

   ```powershell
   Copy-Item .env.example .env
   ```

   On macOS/Linux:

   ```bash
   cp .env.example .env
   ```

4. Generate the application key:

   ```bash
   php artisan key:generate
   ```

5. Configure the database credentials in `.env` and create the corresponding MySQL database.
6. Run migrations:

   ```bash
   php artisan migrate
   ```

7. Create the public storage link for uploaded files:

   ```bash
   php artisan storage:link
   ```

8. Start the Laravel development server:

   ```bash
   php artisan serve
   ```

9. Run automated tests:

   ```bash
   php artisan test
   ```

Follow the repository's actual `.env.example`, migrations, and test configuration if any of these assumptions change.

### Manual integration test

The project includes a standalone test script at `manual-tests/education.php`. Start `php artisan serve` in one terminal, then run the script from the project root in another terminal:

```bash
php manual-tests/education.php
```

The script writes records to the configured database and does not automatically delete them. Run it against a development/test database, not a database containing important data.

## Project Structure

| Path | Responsibility |
|---|---|
| `app/Models/` | Domain models, relationships, casts, and query scopes. |
| `app/Enums/` | Role, level, and course status enums. |
| `app/Http/Controllers/Api/` | API request handling and JSON responses. |
| `app/Http/Requests/` | Validation and request authorization. |
| `app/Policies/` | Resource-level authorization rules. |
| `app/Rules/` | Reusable custom validation rules, including `VideoUrlRule`. |
| `app/Contracts/` | Service interfaces. |
| `app/Services/` | Business operations, including enrollment creation. |
| `app/Providers/` | Application configuration and service-container bindings. |
| `routes/api.php` | API routes and middleware assignments. |
| `database/migrations/` | Database schema, constraints, and indexes. |
| `database/factories/` | Model factories used by tests. |
| `tests/Feature/` | Automated API and feature tests. |
| `manual-tests/` | Standalone manual integration scripts. |

## Design Trade-offs

### Why use Policies instead of checking roles only in controllers?

Policies provide a centralized place for resource permissions such as ownership and parent-course rules. Role checks in a controller may be sufficient for a simple endpoint, but repeated authorization conditions become difficult to keep consistent. Policies still need to be invoked by the request flow and paired with appropriate query filtering.

### Why use Form Requests?

They separate input validation from controller workflow code and can also handle request-level authorization. This keeps validation rules easier to read, reuse, and test.

### Why use Enums?

Enums reduce inconsistent strings and make supported roles, levels, and statuses explicit. They complement, but do not replace, validation or authorization.

### Why use a custom video URL rule?

`VideoUrlRule` centralizes the allowed URL schemes and hosts instead of duplicating host checks across request classes. It validates URL shape, not video availability.

### Why use polymorphic relationships?

Comments and images can be attached to multiple supported resource types without creating a separate table for each type. The category pivot can associate categories with supported types through one shared structure. The trade-off is that polymorphic relations do not have a conventional foreign key to every possible target table, so the application and its tests must enforce valid ownership behavior carefully.

### Why not add repositories everywhere?

Eloquent already provides query and persistence abstractions. A repository that only forwards Eloquent calls would duplicate responsibilities without a concrete benefit. Add one when there is a real data-access boundary or alternative persistence requirement.

### Why not use a service for every CRUD operation?

A service is useful when it encapsulates a meaningful workflow, as enrollment does. Creating a service for every single-model read or update can add indirection without improving the design.

### Why keep a database uniqueness constraint if the service checks for duplicates?

The initial existence check improves the normal response path, but it cannot prevent two concurrent requests from both observing that no enrollment exists. The database constraint is the final integrity guarantee, and the service handles its exception as a controlled duplicate response.

## Future Improvements

These are potential improvements, not claims that the features are already implemented:

- Review all nested lesson and assignment endpoints to ensure draft-course visibility is consistently enforced.
- Audit the teacher dashboard and comment queries for N+1 queries; add eager loading where measured and appropriate.
- Verify whether the `draft()` local scope is used; remove it if it is dead code and not intentionally reserved.
- Document exact routes and example JSON request/response bodies directly from `routes/api.php`, controllers, Form Requests, and tests.
- Add or expand tests for role boundaries, resource ownership, malformed video URLs, image lifecycle behavior, and concurrent duplicate enrollment attempts.
- Consider explicit database transactions for future workflows that perform multiple related writes. Do not add a transaction around a single insert without a concrete consistency requirement.
- Add API versioning or generated API documentation if the API becomes a shared long-term public interface.

The goal is to preserve clear responsibilities, secure resource visibility, and reliable database constraints without adding architectural layers that the application does not currently need.
