<?php

namespace Database\Factories;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'kind' => 'image',
            'category' => 'other',
            'storage' => 'url',
            'disk' => null,
            'path' => null,
            'url' => $this->faker->imageUrl(1200, 800, 'people', true),
            'alt_text' => $this->faker->sentence(3),
        ];
    }

    public function audioUrl(): static
    {
        return $this->state(fn () => [
            'kind' => 'audio',
            'category' => 'music',
            'storage' => 'url',
            'url' => 'https://example.com/sample.mp3',
        ]);
    }

    public function qrUrl(): static
    {
        return $this->state(fn () => [
            'kind' => 'image',
            'category' => 'other',
            'storage' => 'url',
            'url' => $this->faker->imageUrl(600, 600, 'abstract', true),
        ]);
    }
}
