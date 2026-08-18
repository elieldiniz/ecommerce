<?php

namespace App\Actions\Fortify;

use App\Models\Customer;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CreateNewCustomer
{
    /**
     * Validate and create a new customer.
     *
     * @param  array<string, string>  $input
     */
    public function execute(array $input): Customer
    {
        $input['email'] = strtolower($input['email']);

        $validated = validator($input, [
            'name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'string', 'email', 'max:180', 'unique:customers,email'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ])->validate();

        $customerRole = Role::where('slug', 'customer')->firstOrFail();

        $customer = Customer::create([
            'role_id' => $customerRole->id,
            'legal_name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'terms_accepted_at' => now(),
        ]);

        return $customer;
    }
}
