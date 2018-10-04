<?php

namespace App\Http\Controllers;

use App\User;
use App\Friend;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FriendsMgmtController extends Controller
{
    /**
     * Make both users specified in the given request as friends.
     *
     * @param  Request $request
     * @return Response
     */
    public function makeFriends(Request $request)
    {
        $data = $request->input();
        if ($data == null
            || !array_key_exists('friends', $data)
            || count($data['friends']) != 2
            || !is_string($data['friends'][0])
            || !is_string($data['friends'][1])
        ) {
            return response('', 400);
        }

        $successJson = json_encode(array('success' => true));
        $failureJson = json_encode(array('success' => false));

        $user1 = User::where('email', $data['friends'][0])->first();
        $user2 = User::where('email', $data['friends'][1])->first();
        if ($user1 == null || $user2 == null || $user1->id == $user2->id) {
            return $failureJson;
        }

        // This ensures that when we insert a record into the friends table,
        // the user1_id will always be numerically smaller than user2_id
        if ($user1->id > $user2->id) {
            $temp = $user1->id;
            $user1->id = $user2->id;
            $user2->id = $temp;
        }

        $friendRecord = Friend::where([
            'user1_id' => $user1->id,
            'user2_id' => $user2->id
        ])->first();
        if ($friendRecord != null) {
            return $failureJson;
        }
        Friend::create(['user1_id' => $user1->id, 'user2_id' => $user2->id]);

        return $successJson;
    }

    /**
     * Get the friends list for the user specified in the given request.
     *
     * @param  Request $request
     * @return Response
     */
    public function getFriendsList(Request $request)
    {
        $data = $request->input();
        if ($data == null
            || !array_key_exists('email', $data)
            || !is_string($data['email'])
        ) {
            return response('', 400);
        }

        $failureJson = json_encode(array('success' => false));

        $user = User::where('email', $data['email'])->first();
        if ($user == null) {
            return $failureJson;
        }

        $friendRecords = Friend::where([
            'user1_id' => $user->id,
        ])->orWhere([
            'user2_id' => $user->id,
        ])->get();

        $friendsList = [];
        foreach ($friendRecords as $friendRecord) {
            $friendId = $friendRecord->user1_id == $user->id
                ? $friendRecord->user2_id
                : $friendRecord->user1_id;
            $friendEmail = User::where('id', $friendId)->first()->email;
            array_push($friendsList, $friendEmail);
        }

        $responseArray = array(
            'success' => true,
            'friends' => $friendsList,
            'count' => count($friendsList)
        );

        $successJson = json_encode($responseArray);
        return $successJson;
    }
}
