<?php

namespace Database\Seeders;

use App\Models\Food;
use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Starter food library (USDA SR28 values, see data/foods.php).
 * Imported once (foods the admin deletes are not brought back on the next deploy),
 * and firstOrCreate by USDA number never overwrites admin edits.
 */
class FoodsSeeder extends Seeder
{
    public function run(): void
    {
        if (Setting::get('seeded_foods_v1')) {
            return;
        }

        $columns = ['usda_ndb', 'name', 'name_ar', 'category', 'calories', 'protein', 'carbs', 'fat', 'fiber',
                    'serving_grams', 'serving_name', 'serving_name_ar'];

        foreach (require __DIR__ . '/data/foods.php' as $row) {
            $data = array_combine($columns, $row);
            Food::firstOrCreate(['usda_ndb' => $data['usda_ndb']], $data + ['is_active' => true]);
        }

        Setting::put('seeded_foods_v1', now()->toDateTimeString());
    }
}
