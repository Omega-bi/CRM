# Project Module

The Project module manages projects, project memberships, and related business logic.

## Structure

- `Controllers/` - HTTP controllers for project-related endpoints
- `Models/` - Models related to projects and memberships
- `Policies/` - Authorization policies
- `Requests/` - Form requests for validation
- `Resources/views/` - Blade views
- `routes.php` - Module-specific routes
- `database/migrations/` - Database schema changes
- `database/factories/` - Factories for testing
- `Events/` - Events triggered by project actions
- `Listeners/` - Listeners for project events
- `ServiceProvider.php` - Service provider for module registration

## Dependencies

- Core Laravel
- Access module (for user permissions)
- Customer module (for customer relationships)
- Workspace module (for workspace context)

## Usage

The Project module is automatically registered via `bootstrap/providers.php`. No additional setup is required.
