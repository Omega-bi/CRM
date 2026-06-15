<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\HasWorkspaces;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Modules\Access\Enums\RoleScope;
use Modules\Access\Models\Role;
use Modules\Employee\Models\Employee;
use Modules\Customer\Models\CustomerContact;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_workspace_id
 * @property string|null $locale
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace|null $currentWorkspace
 * @property-read Collection<int, Role> $systemRoles
 * @property-read Collection<int, Workspace> $ownedWorkspaces
 * @property-read Collection<int, WorkspaceMembership> $workspaceMemberships
 * @property-read Collection<int, Workspace> $workspaces
 * @property-read Employee|null $employee
 * @property-read CustomerContact|null $customerContact
 */
#[Fillable(['name', 'email', 'password', 'current_workspace_id', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasWorkspaces, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get system-level roles assigned to this user.
     *
     * @return BelongsToMany<Role, $this>
     */
    public function systemRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->where('scope', RoleScope::System->value)
            ->withTimestamps();
    }

    /**
     * Get the employee profile linked to this user.
     *
     * @return HasOne<Employee, $this>
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get the customer contact profile linked to this user.
     *
     * @return HasOne<CustomerContact, $this>
     */
    public function customerContact(): HasOne
    {
        return $this->hasOne(CustomerContact::class);
    }
}
