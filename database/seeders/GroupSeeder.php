<?php

namespace Database\Seeders;

use App\Models\Group;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Group::insert([
            [
                'name' => "test1"
            ],
            [
                'name' => "test2"
            ],
            [
                'name' => "test3"
            ]
        ]);

        DB::table('user_group')->insert([
            [
                'user_id' => 1,
                'group_id' => 1
            ]
        ]);
    }
}
