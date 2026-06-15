<?php

namespace Modules\Customer\Actions;

use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerContact;

class CreateCustomerContact
{
    /**
     * @param  array{full_name: string, phone?: string|null, email?: string|null, position?: string|null, status?: string|null, notes?: string|null}  $data
     */
    public function handle(Customer $customer, array $data): CustomerContact
    {
        return $customer->contacts()->create([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'position' => $data['position'] ?? null,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
