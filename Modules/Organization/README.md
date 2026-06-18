# Organization Module

The Organization module manages organizational structures, departments, and hierarchy.

## Structure

- `Controllers/` - HTTP controllers for organization-related endpoints
- `Data/Enums/` - Enumerations for organization types
- `Models/` - Models related to organizations
- `Policies/` - Authorization policies
- `Requests/` - Form requests for validation
- `Resources/views/` - Blade views
- `routes.php` - Module-specific routes
- `database/migrations/` - Database schema changes
- `Events/` - Events triggered by organization actions
- `Listeners/` - Listeners for organization events
- `ServiceProvider.php` - Service provider for module registration

## Dependencies

- Core Laravel
- Access module (for user permissions)
- Employee module (for employee relationships)

## Usage

The Organization module is automatically registered via `bootstrap/providers.php`. No additional setup is required.
