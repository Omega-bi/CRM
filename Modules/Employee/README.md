# Employee Module

The Employee module manages employee data, employment relationships, and HR-related functionality.

## Structure

- `Controllers/` - HTTP controllers for employee-related endpoints
- `Data/Enums/` - Enumerations for employment status and roles
- `Models/` - Models related to employees
- `Policies/` - Authorization policies
- `Requests/` - Form requests for validation
- `Resources/views/` - Blade views
- `routes.php` - Module-specific routes
- `database/migrations/` - Database schema changes
- `Events/` - Events triggered by employee actions
- `Listeners/` - Listeners for employee events
- `ServiceProvider.php` - Service provider for module registration

## Dependencies

- Core Laravel
- Access module (for user permissions)
- Organization module (for organizational context)

## Usage

The Employee module is automatically registered via `bootstrap/providers.php`. No additional setup is required.
