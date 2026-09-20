<?php

namespace App\Services;

use App\Exceptions\AddressInUseException;
use App\Models\Address;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AddressService
{
    /**
     * Create an address for the user, handling default-address assignment.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createForUser(User $user, array $attributes): Address
    {
        return DB::transaction(function () use ($user, $attributes): Address {
            $isFirst = ! $user->addresses()->exists();
            $wantsDefault = (bool) ($attributes['is_default'] ?? false);

            if ($isFirst || $wantsDefault) {
                $user->addresses()->update(['is_default' => false]);
                $attributes['is_default'] = true;
            } else {
                $attributes['is_default'] = false;
            }

            return $user->addresses()->create($attributes);
        });
    }

    /**
     * Update an address while preserving single-default invariant.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Address $address, array $attributes): Address
    {
        return DB::transaction(function () use ($address, $attributes): Address {
            if (array_key_exists('is_default', $attributes) && (bool) $attributes['is_default']) {
                Address::ownedBy($address->user_id)->whereKeyNot($address->id)->update(['is_default' => false]);
                $attributes['is_default'] = true;
            } elseif (array_key_exists('is_default', $attributes)) {
                // Prevent unsetting the only default without a replacement.
                if ($address->is_default && ! Address::ownedBy($address->user_id)->whereKeyNot($address->id)->exists()) {
                    $attributes['is_default'] = true;
                }
            }

            $address->update($attributes);

            return $address->refresh();
        });
    }

    /**
     * Mark the given address as the default for its owner.
     */
    public function setDefault(Address $address): Address
    {
        return DB::transaction(function () use ($address): Address {
            Address::ownedBy($address->user_id)->whereKeyNot($address->id)->lockForUpdate()->update(['is_default' => false]);

            $address->update(['is_default' => true]);

            return $address->refresh();
        });
    }

    /**
     * Delete an address, promoting another one to default when needed.
     *
     * @throws AddressInUseException
     */
    public function delete(Address $address): void
    {
        if ($address->maintenanceRequests()->exists()) {
            throw new AddressInUseException('This address cannot be deleted because it is used by maintenance requests.');
        }

        DB::transaction(function () use ($address): void {
            $wasDefault = $address->is_default;
            $userId = $address->user_id;

            $address->delete();

            if ($wasDefault) {
                $replacement = Address::ownedBy($userId)->oldest()->first();

                if ($replacement !== null) {
                    $replacement->update(['is_default' => true]);
                }
            }
        });
    }
}
