# Customer Module

The Customer module manages customer data, relationships, and related business logic.

## Structure

- `Controllers/` - HTTP controllers for customer-related endpoints
- `Data/Enums/` - Enumerations for customer status and types
- `Models/` - Models related to customers
- `Policies/` - Authorization policies
- `Requests/` - Form requests for validation
- `Resources/views/` - Blade views
- `routes.php` - Module-specific routes
- `database/migrations/` - Database schema changes
- `Events/` - Events triggered by customer actions
- `Listeners/` - Listeners for customer events
- `ServiceProvider.php` - Service provider for module registration

## Dependencies

- Core Laravel
- Access module (for user permissions)
- Workspace module (for workspace context)

## Usage

The Customer module is automatically registered via `bootstrap/providers.php`. No additional setup is required.
