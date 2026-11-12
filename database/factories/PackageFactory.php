<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['gym', 'pt', 'combo'];
        $type = fake()->randomElement($types);

        return [
            'name' => fake()->words(3, true).' Package',
            'type' => $type,
            'duration_days' => fake()->randomElement([30, 90, 180, 365]),
            'session_count' => in_array($type, ['pt', 'combo']) ? fake()->randomElement([8, 12, 16, 24]) : 0,
            'price' => fake()->randomElement([350000, 750000, 1500000, 3000000]),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
