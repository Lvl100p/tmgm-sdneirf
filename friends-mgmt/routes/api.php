<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/v1/befriend', 'FriendsMgmtController@makeFriends');

Route::get('/v1/friends', 'FriendsMgmtController@getFriendsList');

Route::get('/v1/friends-common', 'FriendsMgmtController@getCommonFriendsList');

Route::post('/v1/subscribe', 'FriendsMgmtController@subscribe');

Route::post('/v1/block', 'FriendsMgmtController@block');

Route::get('/v1/recipients', 'FriendsMgmtController@getUpdateRecipients');
