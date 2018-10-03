<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = array(
            'andy',
            'bob',
            'common',
            'dave',
            'eve',
            'frank',
            'grace',
            'heidi',
            'john',
            'kate',
            'lisa',
        );

        foreach ($users as $user) {
            DB::table('users')->insert([
                'name' => $user,
                'email' => $user.'@example.com',
                'password' => bcrypt('secret'),
            ]);
        }
    }
}
