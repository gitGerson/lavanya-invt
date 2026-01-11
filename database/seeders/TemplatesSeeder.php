<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['name' => 'Template 1', 'code' => 'template-1', 'version' => '1.0'],
        ];

        foreach ($templates as $template) {
            Template::query()->updateOrCreate(
                ['code' => $template['code']],
                $template
            );
        }
    }
}
