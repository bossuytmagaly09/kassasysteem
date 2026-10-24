<?php

namespace Database\Factories;

use App\Models\Event;
use App\PaymentMode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'date' => fake()->dateTimeBetween('now', '+1 month'),
            'payment_mode' => PaymentMode::Beide,
            'vak_price' => 100,
            'card_price' => 2000,
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }
}
