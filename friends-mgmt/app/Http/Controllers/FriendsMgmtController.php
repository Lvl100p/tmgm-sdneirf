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
        $data = json_decode($request->getContent(), true);
        if (!array_key_exists('friends', $data)
            || count($data['friends']) != 2)
        {
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
}
