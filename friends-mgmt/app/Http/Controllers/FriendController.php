<?php

namespace App\Http\Controllers;

use App\Friend;
use App\Http\Controllers\Controller;

class FriendController extends Controller
{
    /**
     * Create a new friend record.
     *
     * @param  $user1Id
     * @param  $user2Id
     * @return Friend
     */
    public static function create($user1Id, $user2Id) {
        // This ensures that when we insert a record into the friends table,
        // the user1_id will always be numerically smaller than user2_id.
        // This is to simplify database operations.
        return Friend::create([
            'user1_id' => min($user1Id, $user2Id),
            'user2_id' => max($user1Id, $user2Id),
        ]);
    }

    /**
     * Check if the given user ids are friends.
     *
     * @param  $user1Id
     * @param  $user2Id
     * @return bool
     */
    public static function areFriends($user1Id, $user2Id) {
        $friendRecord = Friend::where([
            'user1_id' => min($user1Id, $user2Id),
            'user2_id' => max($user1Id, $user2Id)
        ])->first();

        return $friendRecord != null;
    }

    /**
     * Check if the given user ids can be friends.
     *
     * @param  $user1Id
     * @param  $user2Id
     * @return bool
     */
    public static function canBeFriends($user1Id, $user2Id) {
        if ($user1Id == $user2Id
            || BlockController::hasBlocked($user1Id, $user2Id)
            || BlockController::hasBlocked($user2Id, $user1Id)
            || self::areFriends($user1Id, $user2Id)
        ) {
            return false;
        }
        return true;
    }

    /**
     * Get the email list of the specified user id's friends.
     * @param  $userId
     * @return array
     */
    public static function getFriendsEmailList($userId) {
        $friendRecords = self::getFriendRecords($userId);

        $friendsEmailList = [];
        foreach ($friendRecords as $friendRecord) {
            $friendId = $friendRecord->user1_id == $userId
                ? $friendRecord->user2_id
                : $friendRecord->user1_id;
            $friendEmail = UserController::getUserById($friendId)->email;
            array_push($friendsEmailList, $friendEmail);
        }

        return $friendsEmailList;
    }

    private static function getFriendRecords($userId) {
        $friendRecords = Friend::where([
            'user1_id' => $userId,
        ])->orWhere([
            'user2_id' => $userId,
        ])->get();

        return $friendRecords;
    }

    /**
     * Get the email list of the common friends of both user ids.
     *
     * @param  $user1Id
     * @param  $user2Id
     * @return array
     */
    public static function getCommonFriendsEmailList($user1Id, $user2Id) {
        $commonFriendsEmailList = self::getFriendsEmailList($user1Id);
        foreach ($commonFriendsEmailList as $key => $user1FriendEmail) {
            $user1Friend = UserController::getUserByEmail($user1FriendEmail);
            if (!self::areFriends($user1Friend->id, $user2Id)) {
                unset($commonFriendsEmailList[$key]);
            }
        }

        return $commonFriendsEmailList;
    }
}
