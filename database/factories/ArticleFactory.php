<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(6);

        return [
            'title'        => $title,
            'slug'         => Str::slug($title),
            'excerpt'      => $this->faker->paragraph(),
            'content'      => $this->faker->paragraphs(3, true),
            'image_url'    => $this->faker->imageUrl(800, 400),
            'category_id'  => Category::factory(),
            'author'       => $this->faker->name(),
            'views'        => $this->faker->numberBetween(0, 10000),
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['published_at' => null]);
    }
}
