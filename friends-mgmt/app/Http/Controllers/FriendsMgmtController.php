<?php

namespace App\Http\Controllers;

use App\User;
use App\Friend;
use App\Subscription;
use App\Block;
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

        $user1 = $this->getUserRecordByEmail($data['friends'][0]);
        $user2 = $this->getUserRecordByEmail($data['friends'][1]);
        if ($user1 == null
            || $user2 == null
            || $this->cannotBeFriends($user1, $user2)
        ) {
            return response()->json($failureArr);
        }

        $this->createFriendRecord($user1->id, $user2->id);

        return response()->json($successArr);
    }

    private function getUserRecordByEmail($userEmail) {
        return User::where('email', $userEmail)->first();
    }

    private function cannotBeFriends(User $user1, User $user2) {
        return $this->areSameUser($user1, $user2)
            || $this->hasBlocked($user1, $user2)
            || $this->hasBlocked($user2, $user1)
            || $this->areFriends($user1, $user2);
    }

    private function areSameUser(User $user1, User $user2) {
        return $user1->id == $user2->id;
    }

    private function hasBlocked(User $requestor, User $target) {
        $blockRecord = $this->getBlockRecord(
            $requestor->id, $target->id
        );
        return $blockRecord != null;
    }

    private function getBlockRecord($requestorId, $targetId) {
        return Block::where([
            'requestor_id' => $requestorId, 'target_id' => $targetId
        ])->first();
    }

    private function areFriends(User $user1, User $user2) {
        // All friend records in the friends table have the smaller of
        // both ids as user1_id. This is to simplify database operations.
        $friendRecord = Friend::where([
            'user1_id' => min($user1->id, $user2->id),
            'user2_id' => max($user1->id, $user2->id)
        ])->first();

        return $friendRecord != null;
    }

    private function createFriendRecord($user1Id, $user2Id) {
        // This ensures that when we insert a record into the friends table,
        // the user1_id will always be numerically smaller than user2_id.
        Friend::create([
            'user1_id' => min($user1Id, $user2Id),
            'user2_id' => max($user1Id, $user2Id)
        ]);
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

        $user = $this->getUserRecordByEmail($data['email']);
        if ($user == null) {
            return response()->json($failureArr);
        }

        $friendsEmailList = $this->getFriendsEmailList($user->id);

        $successArr = array(
            'success' => true,
            'friends' => $friendsEmailList,
            'count' => count($friendsEmailList)
        );
        return response()->json($successArr);
    }

    private function getFriendsEmailList($userId) {
        $friendRecords = $this->getFriendRecords($userId);

        $friendsEmailList = [];
        foreach ($friendRecords as $friendRecord) {
            $friendId = $friendRecord->user1_id == $userId
                ? $friendRecord->user2_id
                : $friendRecord->user1_id;
            $friendEmail = $this->getUserRecordById($friendId)->email;
            array_push($friendsEmailList, $friendEmail);
        }

        return $friendsEmailList;
    }

    private function getFriendRecords($userId) {
        $friendRecords = Friend::where([
            'user1_id' => $userId,
        ])->orWhere([
            'user2_id' => $userId,
        ])->get();

        return $friendRecords;
    }

    private function getUserRecordById($userId) {
        return User::where('id', $userId)->first();
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

        $user1 = $this->getUserRecordByEmail($data['friends'][0]);
        $user2 = $this->getUserRecordByEmail($data['friends'][1]);
        if ($user1 == null
            || $user2 == null
            || $this->areSameUser($user1, $user2)
        ) {
            return response()->json($failureArr);
        }

        $commonFriendsEmailList = $this->getCommonFriendsEmailList(
            $request,
            $user1,
            $user2
        );

        $successArr = array(
            'success' => true,
            'friends' => $commonFriendsEmailList,
            'count' => count($commonFriendsEmailList)
        );
        return response()->json($successArr);
    }

    private function getCommonFriendsEmailList(Request $request, User $user1, User $user2) {
        // Reusing the web API for retrieving friends list. That API
        // expects an 'email' property, so we can merge it into the
        // current request and conveniently reuse that request for
        // making the API call.
        $request->merge([
            'email' => $user1->email
        ]);
        $user1FriendsEmailList = json_decode(
            $this->getFriendsList($request)->content(), true
        )['friends'];

        $commonFriendsEmailList = [];
        foreach ($user1FriendsEmailList as $user1FriendEmail) {
            $user1Friend = $this->getUserRecordByEmail($user1FriendEmail);
            if ($this->areFriends($user1Friend, $user2)) {
                array_push($commonFriendsEmailList, $user1FriendEmail);
            }
        }

        return $commonFriendsEmailList;
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

        $requestor = $this->getUserRecordByEmail($data['requestor']);
        $target = $this->getUserRecordByEmail($data['target']);
        if ($requestor == null
            || $target == null
            || $this->areSameUser($requestor, $target)
            || $this->alreadySubscribedTo($requestor, $target)
        ) {
            return response()->json($failureArr);
        }

        $this->createSubscriptionRecord($requestor->id, $target->id);

        return response()->json($successArr);
    }

    private function alreadySubscribedTo(User $requestor, User $target) {
        $subscriptionRecord = $this->getSubscriptionRecord(
            $requestor->id, $target->id
        );

        return $subscriptionRecord != null;
    }

    private function getSubscriptionRecord($requestorId, $targetId) {
        return Subscription::where([
            'requestor_id' => $requestorId, 'target_id' => $targetId
        ])->first();
    }

    private function createSubscriptionRecord($requestorId, $targetId) {
        Subscription::create([
            'requestor_id' => $requestorId,
            'target_id' => $targetId
        ]);
    }

    /**
     * Make the requestor specified in the given request block the target
     * specified in the given request.
     *
     * @param  Request $request
     * @return Response
     */
    public function block(Request $request)
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

        $requestor = $this->getUserRecordByEmail($data['requestor']);
        $target = $this->getUserRecordByEmail($data['target']);
        if ($requestor == null
            || $target == null
            || $this->areSameUser($requestor, $target)
            || $this->hasBlocked($requestor, $target)
        ) {
            return response()->json($failureArr);
        }

        $this->createBlockRecord($requestor->id, $target->id);

        return response()->json($successArr);
    }

    private function createBlockRecord($requestorId, $targetId) {
        Block::create([
            'requestor_id' => $requestorId,
            'target_id' => $targetId
        ]);
    }

    /**
     * Get the list of users who can receive updates from the sender
     * specified in the given request.
     *
     * @param  Request $request
     * @return Response
     */
    public function getUpdateRecipients(Request $request)
    {
        $data = $request->input();
        if ($data == null
            || !array_key_exists('sender', $data)
            || !array_key_exists('text', $data)
            || !is_string($data['sender'])
            || !is_string($data['text'])
        ) {
            return response('', 400);
        }

        $successArr = array('success' => true, 'recipients' => []);
        $failureArr = array('success' => false);

        $sender = $this->getUserRecordByEmail($data['sender']);
        if ($sender == null) {
            return response()->json($failureArr);
        }

        $successArr['recipients']
            = $this->getRecipientsEmailList($sender, $data['text']);
        return response()->json($successArr);
    }

    private function getRecipientsEmailList(User $sender, $text) {
        $subscribersEmailList = $this->getSubscribersEmailList($sender->id);
        $sendersFriendsEmailList = $this->getFriendsEmailList($sender->id);
        $mentionedUsersEmailList = $this->getMentionedUsersEmailList($text);
        $recipientsEmailList = array_unique(array_merge(
            $subscribersEmailList,
            $sendersFriendsEmailList,
            $mentionedUsersEmailList
        ));

        foreach ($recipientsEmailList as $key => $recipientEmail) {
            $recipient = $this->getUserRecordByEmail($recipientEmail);
            if ($this->hasBlocked($recipient, $sender)) {
                unset($recipientsEmailList[$key]);
            }
        }

        return $recipientsEmailList;
    }

    private function getSubscribersEmailList($senderId) {
        $subscribersEmailList = [];
        $subscriptionRecords = Subscription::where([
            'target_id' => $senderId
        ])->get();
        foreach ($subscriptionRecords as $subscriptionRecord) {
            $subscriberEmail = $this->getUserRecordById(
                $subscriptionRecord->requestor_id
            )->email;
            array_push($subscribersEmailList, $subscriberEmail);
        }

        return $subscribersEmailList;
    }

    private function getMentionedUsersEmailList($text) {
        $matches = [];
        $pattern
            = '/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,4})(?:\.[a-z]{2})?/i';
        preg_match_all($pattern, $text, $matches);

        return $matches[0];
    }
}
