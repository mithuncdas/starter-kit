<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\UserAddress;
use Chronicle\Facades\Chronicle;
use Illuminate\Support\Facades\DB;

class UserAddressService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $owner, array $data, User $actor): UserAddress
    {
        $result = DB::transaction(function () use ($owner, $data): array {
            $makePrimary = (bool) ($data['is_primary'] ?? false);
            $previousPrimaryId = null;

            if ($makePrimary) {
                $previousPrimaryId = $owner->addresses()
                    ->where('is_primary', true)
                    ->value('id');

                $owner->addresses()->where('is_primary', true)->update(['is_primary' => false]);
            }

            $data['is_primary'] = $makePrimary;
            $address = $owner->addresses()->create($data);

            return [
                'address' => $address,
                'previous_primary_id' => $previousPrimaryId,
                'made_primary' => $makePrimary,
            ];
        });

        /** @var UserAddress $address */
        $address = $result['address'];

        Chronicle::record()
            ->actor($actor)
            ->action('user_address.created')
            ->subject($address)
            ->metadata([
                'user_id' => $owner->id,
                'label' => $address->label?->value,
                'is_primary' => $address->is_primary,
                'admin_area_id' => $address->admin_area_id,
            ])
            ->diff([
                'address_line1' => ['old' => null, 'new' => $address->address_line1],
                'address_line2' => ['old' => null, 'new' => $address->address_line2],
            ])
            ->tags(['address'])
            ->commit();

        if ($result['made_primary'] && $result['previous_primary_id'] !== null) {
            Chronicle::record()
                ->actor($actor)
                ->action('user_address.primary_set')
                ->subject($address)
                ->metadata([
                    'user_id' => $owner->id,
                    'previous_primary_address_id' => $result['previous_primary_id'],
                ])
                ->tags(['address'])
                ->commit();
        }

        return $address;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $owner, UserAddress $address, array $data, User $actor): UserAddress
    {
        $result = DB::transaction(function () use ($owner, $address, $data): array {
            $original = [
                'label' => $address->label?->value,
                'is_primary' => $address->is_primary,
                'admin_area_id' => $address->admin_area_id,
                'address_line1' => $address->address_line1,
                'address_line2' => $address->address_line2,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'notes' => $address->notes,
            ];

            $previousPrimaryId = null;
            $promoting = array_key_exists('is_primary', $data) && $data['is_primary'] && ! $address->is_primary;

            if ($promoting) {
                $previousPrimaryId = $owner->addresses()
                    ->where('is_primary', true)
                    ->where('id', '!=', $address->id)
                    ->value('id');

                $owner->addresses()
                    ->where('is_primary', true)
                    ->where('id', '!=', $address->id)
                    ->update(['is_primary' => false]);
            }

            $address->fill($data)->save();

            $new = [
                'label' => $address->label?->value,
                'is_primary' => $address->is_primary,
                'admin_area_id' => $address->admin_area_id,
                'address_line1' => $address->address_line1,
                'address_line2' => $address->address_line2,
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
                'notes' => $address->notes,
            ];

            $fieldDiff = [];
            foreach ($new as $field => $value) {
                if ($original[$field] !== $value) {
                    $fieldDiff[$field] = ['old' => $original[$field], 'new' => $value];
                }
            }

            return [
                'address' => $address,
                'field_diff' => $fieldDiff,
                'promoted' => $promoting,
                'previous_primary_id' => $previousPrimaryId,
            ];
        });

        /** @var UserAddress $address */
        $address = $result['address'];

        if (! empty($result['field_diff'])) {
            Chronicle::record()
                ->actor($actor)
                ->action('user_address.updated')
                ->subject($address)
                ->metadata(['user_id' => $owner->id])
                ->diff($result['field_diff'])
                ->tags(['address'])
                ->commit();
        }

        if ($result['promoted'] && $result['previous_primary_id'] !== null) {
            Chronicle::record()
                ->actor($actor)
                ->action('user_address.primary_set')
                ->subject($address)
                ->metadata([
                    'user_id' => $owner->id,
                    'previous_primary_address_id' => $result['previous_primary_id'],
                ])
                ->tags(['address'])
                ->commit();
        }

        return $address;
    }

    public function delete(User $owner, UserAddress $address, User $actor): void
    {
        $snapshot = [
            'user_id' => $owner->id,
            'label' => $address->label?->value,
            'was_primary' => $address->is_primary,
            'admin_area_id' => $address->admin_area_id,
            'address_line1' => $address->address_line1,
        ];

        $address->delete();

        Chronicle::record()
            ->actor($actor)
            ->action('user_address.deleted')
            ->subject($address)
            ->metadata($snapshot)
            ->tags(['address'])
            ->commit();
    }
}
