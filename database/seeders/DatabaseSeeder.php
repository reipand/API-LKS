<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com', 'password' => Hash::make('password'), 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Bob',   'email' => 'bob@example.com',   'password' => Hash::make('password'), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $categories = [
            ['name' => 'Technology', 'slug' => 'technology'],
            ['name' => 'Politics',   'slug' => 'politics'],
            ['name' => 'Sports',     'slug' => 'sports'],
            ['name' => 'Health',     'slug' => 'health'],
            ['name' => 'Business',   'slug' => 'business'],
            ['name' => 'World',      'slug' => 'world'],
        ];
        foreach ($categories as &$c) { $c['created_at'] = $c['updated_at'] = now(); }
        DB::table('categories')->insert($categories);

        $tags = [
            ['name' => 'AI',        'slug' => 'ai'],
            ['name' => 'Startup',   'slug' => 'startup'],
            ['name' => 'Mobile',    'slug' => 'mobile'],
            ['name' => 'Economy',   'slug' => 'economy'],
            ['name' => 'Election',  'slug' => 'election'],
            ['name' => 'Football',  'slug' => 'football'],
            ['name' => 'Nutrition', 'slug' => 'nutrition'],
            ['name' => 'Finance',   'slug' => 'finance'],
        ];
        foreach ($tags as &$t) { $t['created_at'] = $t['updated_at'] = now(); }
        DB::table('tags')->insert($tags);

        $articles = [
            [1, 'AI Revolution Reshapes the Tech Industry',   'ai-revolution-reshapes-tech-industry', 'How artificial intelligence is fundamentally changing how software is built.',         'Artificial intelligence is no longer a futuristic concept...',      'https://picsum.photos/seed/ai/800/400',      1, 'Jane Smith',   5420,  '2026-04-28 08:00:00'],
            [2, 'New Startup Raises $50M in Series B Funding','new-startup-raises-50m-series-b',       'A local startup disrupts logistics with drone delivery.',                            'The Jakarta-based startup announced a $50M round...',               'https://picsum.photos/seed/startup/800/400', 1, 'John Doe',     3100,  '2026-04-27 10:30:00'],
            [3, 'Election Results Spark National Debate',      'election-results-spark-national-debate','Analysts weigh in after yesterday\'s surprise election outcome.',                   'The results sent shockwaves through political circles...',          'https://picsum.photos/seed/election/800/400',2, 'Ahmad Rizal',  8200,  '2026-04-28 06:00:00'],
            [4, 'National Team Qualifies for World Cup',       'national-team-qualifies-world-cup',     'Indonesia books their spot in the 2026 World Cup finals.',                          'After a tense 90-minute match, Indonesia secured qualification...', 'https://picsum.photos/seed/football/800/400',3, 'Budi Santoso', 12500, '2026-04-26 20:00:00'],
            [5, 'New Study Links Sleep to Heart Health',       'new-study-links-sleep-heart-health',    'Researchers find strong correlation between sleep quality and cardiovascular risk.', 'A comprehensive study involving 50,000 participants revealed...',   'https://picsum.photos/seed/health/800/400',  4, 'Dr. Sarah',    2300,  '2026-04-25 09:00:00'],
            [6, 'Rupiah Strengthens Against Dollar',           'rupiah-strengthens-against-dollar',     'Indonesia\'s currency gains ground amid positive trade data.',                       'The Indonesian rupiah closed at its strongest level in months...',  'https://picsum.photos/seed/finance/800/400', 5, 'Eko Prasetyo', 1800,  '2026-04-24 14:00:00'],
            [7, 'Mobile App Downloads Hit Record High',        'mobile-app-downloads-record-high',      'Indonesia tops Southeast Asia in mobile application downloads for Q1 2026.',         'Data from analytics firm shows record-breaking numbers...',         'https://picsum.photos/seed/mobile/800/400',  1, 'Jane Smith',   4100,  '2026-04-23 11:00:00'],
            [8, 'Global Leaders Meet for Climate Summit',      'global-leaders-climate-summit',         'World leaders convene in Geneva to discuss new climate commitments.',               'Heads of state from over 80 countries gathered in Geneva...',       'https://picsum.photos/seed/climate/800/400', 6, 'John Doe',     3600,  '2026-04-22 07:00:00'],
            [9, 'Diet Rich in Plants Reduces Cancer Risk',     'plant-diet-reduces-cancer-risk',        'A decade-long study confirms plant-based diets lower cancer incidence.',             'The landmark study tracked dietary habits of 30,000 adults...',     'https://picsum.photos/seed/diet/800/400',    4, 'Dr. Sarah',    5100,  '2026-04-21 08:30:00'],
            [10,'Tech Giant Enters Indonesian Market',         'tech-giant-enters-indonesian-market',   'A Fortune 500 tech company announces $2B investment in Indonesia.',                 'The announcement marks the largest single foreign tech investment...','https://picsum.photos/seed/invest/800/400', 5, 'Eko Prasetyo', 7300,  '2026-04-20 13:00:00'],
        ];

        $rows = [];
        foreach ($articles as [$id, $title, $slug, $excerpt, $content, $image, $catId, $author, $views, $published]) {
            $rows[] = ['id' => $id, 'title' => $title, 'slug' => $slug, 'excerpt' => $excerpt, 'content' => $content, 'image_url' => $image, 'category_id' => $catId, 'author' => $author, 'views' => $views, 'published_at' => $published, 'created_at' => now(), 'updated_at' => now()];
        }
        DB::table('articles')->insert($rows);

        DB::table('article_tags')->insert([
            ['article_id' => 1,  'tag_id' => 1], ['article_id' => 1,  'tag_id' => 2],
            ['article_id' => 2,  'tag_id' => 2],
            ['article_id' => 3,  'tag_id' => 5],
            ['article_id' => 4,  'tag_id' => 6],
            ['article_id' => 5,  'tag_id' => 7],
            ['article_id' => 6,  'tag_id' => 8], ['article_id' => 6,  'tag_id' => 4],
            ['article_id' => 7,  'tag_id' => 3],
            ['article_id' => 8,  'tag_id' => 4],
            ['article_id' => 9,  'tag_id' => 7],
            ['article_id' => 10, 'tag_id' => 1], ['article_id' => 10, 'tag_id' => 8],
        ]);

        DB::table('bookmarks')->insert([
            ['user_id' => 1, 'article_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 1, 'article_id' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 2, 'article_id' => 3, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('user_category_preferences')->insert([
            ['user_id' => 1, 'category_id' => 1],
            ['user_id' => 1, 'category_id' => 3],
            ['user_id' => 2, 'category_id' => 2],
            ['user_id' => 2, 'category_id' => 5],
        ]);
    }
}
