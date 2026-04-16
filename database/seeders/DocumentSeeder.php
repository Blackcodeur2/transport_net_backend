<?php

namespace Database\Seeders;

use App\Models\KWCDocument;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        KWCDocument::factory()->count(1)->create();
    }
}
