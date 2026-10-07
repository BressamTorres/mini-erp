<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'document' => fake()->optional()->numerify('###########'),
            'phone' => fake()->numerify('###########'),
            'email' => fake()->optional()->safeEmail(),
            'photo_path' => null,
            'type' => Person::TYPE_CUSTOMER,
            'active' => true,
        ];
    }

    public function customer(): static
    {
        return $this->state(fn (): array => ['type' => Person::TYPE_CUSTOMER]);
    }

    public function seller(): static
    {
        return $this->state(fn (): array => ['type' => Person::TYPE_SELLER]);
    }
}
