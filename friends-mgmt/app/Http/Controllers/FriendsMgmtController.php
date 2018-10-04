<?php

namespace App\Http\Controllers;

use App\User;
use App\Friend;
use App\Subscription;
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
        if (!$request->isJson()) {
            return response('', 400);
        }

        $data = $request->input();
        if ($data == null
            || !array_key_exists('friends', $data)
            || count($data['friends']) != 2
            || !is_string($data['friends'][0])
            || !is_string($data['friends'][1])
        ) {
            return response('', 400);
        }

        $successArr = array('success' => true);
        $failureArr = array('success' => false);

        $user1 = User::where('email', $data['friends'][0])->first();
        $user2 = User::where('email', $data['friends'][1])->first();
        if ($user1 == null || $user2 == null || $user1->id == $user2->id) {
            return response()->json($failureArr);
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
            return response()->json($failureArr);
        }
        Friend::create(['user1_id' => $user1->id, 'user2_id' => $user2->id]);

        return response()->json($successArr);
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

        $failureArr = array('success' => false);

        $user = User::where('email', $data['email'])->first();
        if ($user == null) {
            return response()->json($failureArr);
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

        $successArr = array(
            'success' => true,
            'friends' => $friendsList,
            'count' => count($friendsList)
        );
        return response()->json($successArr);
    }

    /**
     * Get the common friends list for both users specified in the given
     * request.
     *
     * @param  Request $request
     * @return Response
     */
    public function getCommonFriendsList(Request $request)
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

        $failureArr = array('success' => false);

        $user1 = User::where('email', $data['friends'][0])->first();
        $user2 = User::where('email', $data['friends'][1])->first();
        if ($user1 == null || $user2 == null || $user1->id == $user2->id) {
            return response()->json($failureArr);
        }

        $request->merge([
            'email' => $data['friends'][0]
        ]);
        $user1FriendsList = json_decode(
            $this->getFriendsList($request)->content(), true
        )['friends'];

        $commonFriends = [];
        foreach ($user1FriendsList as $user1Friend) {
            $user1FriendId = User::where('email', $user1Friend)->first()->id;
            $smallerId = $user2->id < $user1FriendId
                ? $user2->id
                : $user1FriendId;
            $largerId = $smallerId == $user2->id ? $user1FriendId : $user2->id;
            $friendRecord = Friend::where([
                'user1_id' => $smallerId, 'user2_id' => $largerId
            ])->first();
            if ($friendRecord != null) {
                array_push($commonFriends, $user1Friend);
            }
        }

        $successArr = array(
            'success' => true,
            'friends' => $commonFriends,
            'count' => count($commonFriends)
        );
        return response()->json($successArr);
    }

    /**
     * Subscribe the requestor specified in the given request to the target
     * specified in the given request.
     *
     * @param  Request $request
     * @return Response
     */
    public function subscribe(Request $request)
    {
        if (!$request->isJson()) {
            return response('', 400);
        }

        $data = $request->input();
        if ($data == null
            || !array_key_exists('requestor', $data)
            || !array_key_exists('target', $data)
            || !is_string($data['requestor'])
            || !is_string($data['target'])
        ) {
            return response('', 400);
        }

        $successArr = array('success' => true);
        $failureArr = array('success' => false);

        $requestor = User::where('email', $data['requestor'])->first();
        $target = User::where('email', $data['target'])->first();
        if ($requestor == null
            || $target == null
            || $requestor->id == $target->id
        ) {
            return response()->json($failureArr);
        }

        $subscriptionRecord = Subscription::where([
            'requestor_id' => $requestor->id, 'target_id' => $target->id
        ])->first();
        if ($subscriptionRecord != null) {
            return response()->json($failureArr);
        }

        Subscription::create([
            'requestor_id' => $requestor->id,
            'target_id' => $target->id
        ]);
        return response()->json($successArr);
    }
}
