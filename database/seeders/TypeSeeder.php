<?php

namespace Database\Seeders;

use App\Models\Type;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['color', 'size'] as $name) {
            Type::firstOrCreate(['name' => $name]);
        }
    }
}
