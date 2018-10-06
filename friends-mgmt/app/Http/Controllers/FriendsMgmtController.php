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
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $response = $next($request);

            // Laravel seems to return 422 on validation failures,
            // but it seems more appropriate to return 400 instead,
            // so we introduce a middleware for intercepting 422
            // responses and returning 400 instead.
            if ($response->status() == 422) {
                return response('', 400);
            }
            return $response;
        });
    }

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

        $data = $request->validate([
            'friends' => 'required|array|size:2',
            'friends.*' => 'required|email'
        ]);

        $successArr = array('success' => true);
        $failureArr = array('success' => false);

        $user1 = UserController::getUserByEmail($data['friends'][0]);
        $user2 = UserController::getUserByEmail($data['friends'][1]);
        if ($user1 == null
            || $user2 == null
            || !FriendController::canBeFriends($user1->id, $user2->id)
        ) {
            return response()->json($failureArr);
        }

        FriendController::create($user1->id, $user2->id);
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
        $data = $request->validate([
            'email' => 'required|email'
        ]);

        $failureArr = array('success' => false);

        $user = UserController::getUserByEmail($data['email']);
        if ($user == null) {
            return response()->json($failureArr);
        }

        $friendsEmailList = FriendController::getFriendsEmailList($user->id);

        $successArr = array(
            'success' => true,
            'friends' => $friendsEmailList,
            'count' => count($friendsEmailList)
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
        $data = $request->validate([
            'friends' => 'required|array|size:2',
            'friends.*' => 'required|email'
        ]);

        $failureArr = array('success' => false);

        $user1 = UserController::getUserByEmail($data['friends'][0]);
        $user2 = UserController::getUserByEmail($data['friends'][1]);
        if ($user1 == null
            || $user2 == null
            || $user1->id == $user2->id
        ) {
            return response()->json($failureArr);
        }

        $commonFriendsEmailList = FriendController::getCommonFriendsEmailList(
            $user1->id,
            $user2->id
        );

        $successArr = array(
            'success' => true,
            'friends' => $commonFriendsEmailList,
            'count' => count($commonFriendsEmailList)
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

        $data = $request->validate([
            'requestor' => 'required|email',
            'target' => 'required|email'
        ]);

        $successArr = array('success' => true);
        $failureArr = array('success' => false);

        $requestor = UserController::getUserByEmail($data['requestor']);
        $target = UserController::getUserByEmail($data['target']);
        if ($requestor == null
            || $target == null
            || !SubscriptionController::canSubscribeTo($requestor->id, $target->id)
        ) {
            return response()->json($failureArr);
        }

        SubscriptionController::create($requestor->id, $target->id);
        return response()->json($successArr);
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

        $data = $request->validate([
            'requestor' => 'required|email',
            'target' => 'required|email'
        ]);

        $successArr = array('success' => true);
        $failureArr = array('success' => false);

        $requestor = UserController::getUserByEmail($data['requestor']);
        $target = UserController::getUserByEmail($data['target']);
        if ($requestor == null
            || $target == null
            || !BlockController::canBlock($requestor->id, $target->id)
        ) {
            return response()->json($failureArr);
        }

        BlockController::create($requestor->id, $target->id);
        return response()->json($successArr);
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
        $data = $request->validate([
            'sender' => 'required|email',
            'text' => 'required|string'
        ]);

        $successArr = array('success' => true, 'recipients' => []);
        $failureArr = array('success' => false);

        $sender = UserController::getUserByEmail($data['sender']);
        if ($sender == null) {
            return response()->json($failureArr);
        }

        $successArr['recipients']
            = SubscriptionController::getRecipientsEmailList(
                $sender->id, $data['text']
            );
        return response()->json($successArr);
    }
}
