<?php

namespace Database\Factories;

use App\Enums\UserAddressLabelEnum;
use App\Models\AdminArea;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAddress>
 */
class UserAddressFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'admin_area_id' => fn () => AdminArea::query()->inRandomOrder()->value('id')
                ?? throw new \RuntimeException('Seed LocationDataSeeder before creating UserAddress factories.'),
            'label' => UserAddressLabelEnum::Home,
            'is_primary' => false,
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'latitude' => null,
            'longitude' => null,
            'notes' => null,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    public function withLabel(UserAddressLabelEnum $label): static
    {
        return $this->state(fn (array $attributes) => [
            'label' => $label,
        ]);
    }
}
