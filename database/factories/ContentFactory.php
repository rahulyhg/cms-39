<?php

use Faker\Generator as Faker;
use Gzero\Cms\Models\Content;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Content::class, function (Faker $faker) {
    return [
        'type'               => 'content',
        'theme'              => null,
        'path'               => null,
        'weight'             => null,
        'rating'             => null,
        'visits'             => null,
        'is_on_home'         => false,
        'is_comment_allowed' => true,
        'is_promoted'        => false,
        'is_sticky'          => false,
        'is_active'          => true,
        'published_at'       => date('Y-m-d H:i:s')
    ];
});
