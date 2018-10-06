<?php

namespace App\Http\Controllers;

use App\Subscription;
use App\Http\Controllers\Controller;

class SubscriptionController extends Controller
{
    /**
     * Create a new subscription record.
     *
     * @param  $requestorId
     * @param  $targetId
     * @return Subscription
     */
    public static function create($requestorId, $targetId) {
        return Subscription::create([
            'requestor_id' => $requestorId,
            'target_id' => $targetId,
        ]);
    }

    /**
     * Check if the given requestor id can subscribe to the given
     * target id.
     *
     * @param  $requestorId
     * @param  $targetId
     * @return bool
     */
    public static function canSubscribeTo($requestorId, $targetId) {
        if ($requestorId == $targetId
            || self::isSubscribedTo($requestorId, $targetId)
        ) {
            return false;
        }
        return true;
    }

    /**
     * Check if the given requestor id is already subscribed to the given
     * target id.
     *
     * @param  $requestorId
     * @param  $targetId
     * @return bool
     */
    public static function isSubscribedTo($requestorId, $targetId) {
        $subscriptionRecord = self::getSubscriptionRecord(
            $requestorId, $targetId
        );
        return $subscriptionRecord != null;
    }

    private static function getSubscriptionRecord($requestorId, $targetId) {
        return Subscription::where([
            'requestor_id' => $requestorId, 'target_id' => $targetId
        ])->first();
    }

    /**
     * Get the email list of the recipients who can receive updates from the given
     * sender id.
     *
     * @param  $senderId
     * @param  String $text
     * @return array
     */
    public static function getRecipientsEmailList($senderId, String $text) {
        $subscribersEmailList = self::getSubscribersEmailList($senderId);
        $sendersFriendsEmailList
            = FriendController::getFriendsEmailList($senderId);
        $mentionedUsersEmailList = self::getMentionedUsersEmailList($text);
        $recipientsEmailList = array_unique(array_merge(
            $subscribersEmailList,
            $sendersFriendsEmailList,
            $mentionedUsersEmailList
        ));

        foreach ($recipientsEmailList as $key => $recipientEmail) {
            $recipient = UserController::getUserByEmail($recipientEmail);
            if (BlockController::hasBlocked($recipient->id, $senderId)) {
                unset($recipientsEmailList[$key]);
            }
        }

        return $recipientsEmailList;
    }

    private static function getSubscribersEmailList($senderId) {
        $subscribersEmailList = [];
        $subscriptionRecords = Subscription::where([
            'target_id' => $senderId
        ])->get();
        foreach ($subscriptionRecords as $subscriptionRecord) {
            $subscriberEmail = UserController::getUserById(
                $subscriptionRecord->requestor_id
            )->email;
            array_push($subscribersEmailList, $subscriberEmail);
        }

        return $subscribersEmailList;
    }

    private static function getMentionedUsersEmailList($text) {
        $matches = [];
        $pattern
            = '/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.([a-z]{2,4})(?:\.[a-z]{2})?/i';
        preg_match_all($pattern, $text, $matches);

        return $matches[0];
    }
}
