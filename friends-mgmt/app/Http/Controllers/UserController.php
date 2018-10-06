<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    /**
     * Create a new user.
     *
     * @param  String $name
     * @return User
     */
    public static function create(String $name) {
        return User::create([
            'name' => $name,
            'email' => $name . '@example.com',
            'password' => bcrypt('secret')
        ]);
    }

    /**
     * Get the User with the specified email.
     *
     * @param  String $email
     * @return User
     */
    public static function getUserByEmail(String $email) {
        return User::where('email', $email)->first();
    }

    /**
     * Get the User with the specified id.
     *
     * @param  $id
     * @return User
     */
    public static function getUserById($id) {
        return User::where('id', $id)->first();
    }
}
