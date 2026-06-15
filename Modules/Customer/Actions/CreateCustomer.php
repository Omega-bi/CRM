<?php

namespace Modules\Customer\Actions;

use Modules\Customer\Models\Customer;

class CreateCustomer
{
    /**
     * Create a customer inside a workspace.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Customer
    {
        return Customer::create($attributes);
    }
}
