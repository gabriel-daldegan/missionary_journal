<?php

namespace App\Services;

use App\Models\Tenant;

class AddressService
{
    public function tenantHasAddressInfo(Tenant $tenant): bool
    {
        $address = $tenant->address()->first();

        if (! $address) {
            return false;
        }

        if (empty($address->address_line_1) || empty($address->country_code)) {
            return false;
        }

        return true;
    }
}
