<?php

namespace Modules\Customer\Actions;

use Modules\Customer\Models\Customer;

class UpdateCustomer
{
    /**
     * Update a customer.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Customer $customer, array $attributes): Customer
    {
        $customer->update($attributes);

        return $customer;
    }
}
