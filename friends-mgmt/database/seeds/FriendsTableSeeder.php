<?php

use Illuminate\Database\Seeder;

class FriendsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('friends')->insert([
            'user1_id' => 1,
            'user2_id' => 3,
        ]);

        DB::table('friends')->insert([
            'user1_id' => 3,
            'user2_id' => 9,
        ]);
    }
}
