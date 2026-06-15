<?php

namespace Modules\Customer\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use App\Models\Project;
use Modules\Workspace\Models\Workspace;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string|null $bin_iin
 * @property string|null $customer_type
 * @property string $status
 * @property string|null $source
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $website
 * @property string|null $legal_address
 * @property string|null $actual_address
 * @property int|null $responsible_user_id
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Workspace $workspace
 * @property-read User|null $responsibleUser
 * @property-read Collection<int, CustomerContact> $contacts
 * @property-read Collection<int, Project> $projects
 */
#[Fillable([
    'workspace_id',
    'name',
    'bin_iin',
    'customer_type',
    'status',
    'source',
    'phone',
    'email',
    'website',
    'legal_address',
    'actual_address',
    'responsible_user_id',
    'notes',
    'created_by',
    'updated_by',
])]
class Customer extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'crm_customers';

    /**
     * Get the workspace that owns this customer.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    /**
     * Get the user responsible for this customer.
     *
     * @return BelongsTo<User, $this>
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Get customer-side people.
     *
     * @return HasMany<CustomerContact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class, 'customer_id');
    }

    /**
     * Get projects connected to this customer.
     *
     * @return BelongsToMany<Project, $this>
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_customers')
            ->withPivot(['workspace_id', 'role'])
            ->withTimestamps();
    }
}
