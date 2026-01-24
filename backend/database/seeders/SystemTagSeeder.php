<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class SystemTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $systemTags = [
            [
                'name' => 'Physical',
                'slug' => 'physical',
                'color' => '#3B82F6',
                'is_system' => true,
            ],
            [
                'name' => 'Digital',
                'slug' => 'digital',
                'color' => '#10B981',
                'is_system' => true,
            ],
            [
                'name' => 'Favorite',
                'slug' => 'favorite',
                'color' => '#EF4444',
                'is_system' => true,
            ],
            [
                'name' => 'Watched',
                'slug' => 'watched',
                'color' => '#8B5CF6',
                'is_system' => true,
            ],
            [
                'name' => 'To Watch',
                'slug' => 'to-watch',
                'color' => '#F59E0B',
                'is_system' => true,
            ],
            [
                'name' => 'Listened',
                'slug' => 'listened',
                'color' => '#8B5CF6',
                'is_system' => true,
            ],
            [
                'name' => 'To Listen',
                'slug' => 'to-listen',
                'color' => '#F59E0B',
                'is_system' => true,
            ],
        ];

        foreach ($systemTags as $tagData) {
            Tag::firstOrCreate(
                ['slug' => $tagData['slug'], 'is_system' => true],
                $tagData
            );
        }
    }
}
