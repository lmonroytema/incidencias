<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Consultant;

class ConsultantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Consultant::updateOrCreate(
            ['email' => 'consultor@temalitoclean.com'],
            [
                'name' => 'Consultor TemaLitoClean',
                'password' => Hash::make('Tema2025@Migration'),
                'area_name' => 'TI',
            ]
        );
    }
}
