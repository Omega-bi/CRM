<?php

namespace Modules\Customer\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Customer\Models\CustomerContact;

class GrantCustomerContactSystemAccess
{
    /**
     * Create or attach a login account for a customer-side contact.
     */
    public function handle(CustomerContact $contact, ?User $user = null, ?string $password = null): User
    {
        return DB::transaction(function () use ($contact, $user, $password): User {
            if ($contact->user_id !== null && $user === null) {
                return $contact->user()->firstOrFail();
            }

            if ($user === null && $contact->email === null) {
                throw new InvalidArgumentException('Customer contact email is required to create a user account.');
            }

            $user ??= User::create([
                'name' => $contact->full_name,
                'email' => $contact->email,
                'password' => $password ?? Str::password(32),
            ]);

            $contact->forceFill(['user_id' => $user->id])->save();

            return $user;
        });
    }
}
