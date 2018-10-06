<?php

namespace Tests\Feature;

use App\User;
use App\Friend;
use App\Subscription;
use App\Block;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\BlockController;
use App\Http\Controllers\SubscriptionController;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FriendsMgmtApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function MakeFriends_BothUsersExistAndAreNotFriends_ReturnsTrue()
    {
        UserController::create('andy');
        UserController::create('john');

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'john@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => true,]);
    }

    /** @test */
    public function MakeFriends_BothUsersExistAndAreNotFriends_UserIdsAddedToFriendsTableWithNumericallySmallerIdAsUser1Id()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');

        $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'john@example.com']
        ]);

        $this->assertTrue($andy->id < $john->id);
        $this->assertDatabaseHas('friends', [
            'user1_id' => $andy->id, 'user2_id' => $john->id
        ]);
        $this->assertDatabaseMissing('friends', [
            'user1_id' => $john->id, 'user2_id' => $andy->id
        ]);
    }

    /** @test */
    public function MakeFriends_BothUsersExistAndAreSamePerson_ReturnsFalse()
    {
        UserController::create('andy');

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'andy@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function MakeFriends_AtLeastOneUserDoesntExist_ReturnsFalse()
    {
        UserController::create('andy');
        UserController::create('john');

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['nonexistent@example.com','john@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'nonexistent@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function MakeFriends_BothUsersExistButAreAlreadyFriends_ReturnsFalse()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        FriendController::create($andy->id, $john->id);

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'john@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['john@example.com', 'andy@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function MakeFriends_InvalidInput_ReturnsStatus400()
    {
        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com']
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/make-friends', [
            'enemies' => ['andy@example.com', 'john@example.com']
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => [
                'andy@example.com', 'john@example.com', 'kate@example.com'
            ]
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => [123456, 'john@example.com']
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com', 123456]
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function MakeFriends_RequestContentTypeIsNotJson_ReturnsStatus400()
    {
        $response = $this->post('/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'john@example.com']
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function MakeFriends_AtLeastOneUserIsBlockedByOther_ReturnsFalse()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        BlockController::create($andy->id, $john->id);

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'john@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['john@example.com', 'andy@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function MakeFriends_BothUsersExistAndAreNotFriends_UserIdsNotAddedToSubscriptionsTable()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');

        $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'john@example.com']
        ]);
        $this->assertDatabaseMissing('subscriptions', [
            'requestor_id' => $john->id, 'target_id' => $andy->id
        ]);
        $this->assertDatabaseMissing('subscriptions', [
            'requestor_id' => $andy->id, 'target_id' => $john->id
        ]);
    }

    /** @test */
    public function GetFriendsList_UserExistsAndHasOneFriend_ReturnsCorrectJson()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        FriendController::create($andy->id, $john->id);

        $response = $this->json('GET', '/api/v1/friends-list', [
            'email' => 'andy@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'friends' => ['john@example.com'],
                'count' => 1
            ]);

        $response = $this->json('GET', '/api/v1/friends-list', [
            'email' => 'john@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'friends' => ['andy@example.com'],
                'count' => 1
            ]);
    }

    /** @test */
    public function GetFriendsList_UserExistsAndHasMultipleFriends_ReturnsCorrectJson()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        $common = UserController::create('common');
        FriendController::create($andy->id, $john->id);
        FriendController::create($andy->id, $common->id);
        FriendController::create($john->id, $common->id);

        $response = $this->json('GET', '/api/v1/friends-list', [
            'email' => 'andy@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'friends' => ['john@example.com', 'common@example.com'],
                'count' => 2
            ]);

        $response = $this->json('GET', '/api/v1/friends-list', [
            'email' => 'common@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'friends' => ['andy@example.com', 'john@example.com'],
                'count' => 2
            ]);
    }

    /** @test */
    public function GetFriendsList_UserExistsAndHasNoFriends_ReturnsCorrectJson()
    {
        UserController::create('andy');

        $response = $this->json('GET', '/api/v1/friends-list', [
            'email' => 'andy@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'friends' => [],
                'count' => 0
            ]);
    }

    /** @test */
    public function GetFriendsList_UserDoesntExist_ReturnsFalse()
    {
        $response = $this->json('GET', '/api/v1/friends-list', [
            'email' => 'andy@example.com'
        ]);

        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function GetFriendsList_InvalidInput_ReturnsStatus400()
    {
        $response = $this->json('GET', '/api/v1/friends-list', [
            'email' => ['andy@example.com', 'john@example.com']
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/friends-list', [
            'user' => 'andy@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/friends-list', [
            'email' => 123456
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function GetCommonFriendsList_NoFriendsInCommon_ReturnsCorrectJson()
    {
        UserController::create('andy');
        UserController::create('john');

        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'friends' => ['andy@example.com', 'john@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'friends' => [],
                'count' => 0
            ]);
    }

    /** @test */
    public function GetCommonFriendsList_OneFriendInCommon_ReturnsCorrectJson()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        $common = UserController::create('common');
        FriendController::create($andy->id, $common->id);
        FriendController::create($john->id, $common->id);

        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'friends' => ['andy@example.com', 'john@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'friends' => ['common@example.com'],
                'count' => 1
            ]);

        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'friends' => ['john@example.com', 'andy@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'friends' => ['common@example.com'],
                'count' => 1
            ]);
    }

    /** @test */
    public function GetCommonFriendsList_MultipleFriendsInCommon_ReturnsCorrectJson()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        $common1 = UserController::create('common1');
        $common2 = UserController::create('common2');
        FriendController::create($andy->id, $common1->id);
        FriendController::create($andy->id, $common2->id);
        FriendController::create($john->id, $common1->id);
        FriendController::create($john->id, $common2->id);

        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'friends' => ['andy@example.com', 'john@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'friends' => ['common1@example.com', 'common2@example.com'],
                'count' => 2
            ]);

        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'friends' => ['john@example.com', 'andy@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'friends' => ['common1@example.com', 'common2@example.com'],
                'count' => 2
            ]);
    }

    /** @test */
    public function GetCommonFriendsList_BothUsersExistAndAreSamePerson_ReturnsFalse()
    {
        UserController::create('andy');

        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'friends' => ['andy@example.com', 'andy@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function GetCommonFriendsList_AtLeastOneUserDoesntExist_ReturnsFalse()
    {
        UserController::create('andy');
        UserController::create('john');

        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'friends' => ['andy@example.com', 'nonexistent@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);

        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'friends' => ['nonexistent@example.com', 'john@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function GetCommonFriendsList_InvalidInput_ReturnsStatus400()
    {
        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'friends' => ['andy@example.com']
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'friends' => [
                'andy@example.com',
                'john@example.com',
                'kate@example.com'
            ]
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'enemies' => ['andy@example.com', 'john@example.com']
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'friends' => [123456, 'andy@example.com']
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/common-friends-list', [
            'friends' => ['andy@example.com', 123456]
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function Subscribe_RequestorIsNotSubscribedToTarget_ReturnsTrue()
    {
        UserController::create('lisa');
        UserController::create('kate');

        $response = $this->json('POST', '/api/v1/subscribe', [
            'requestor' => 'lisa@example.com',
            'target' => 'kate@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => true,]);
    }

    /** @test */
    public function Subscribe_RequestorIsNotSubscribedToTarget_UserIdsAddedToSubscriptionsTable()
    {
        $lisa = UserController::create('lisa');
        $kate = UserController::create('kate');

        $this->json('POST', '/api/v1/subscribe', [
            'requestor' => 'lisa@example.com',
            'target' => 'kate@example.com'
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'requestor_id' => $lisa->id,
            'target_id' => $kate->id
        ]);
    }

    /** @test */
    public function Subscribe_RequestorIsNotSubscribedToTarget_UserIdsNotAddedToFriendsTable()
    {
        $lisa = UserController::create('lisa');
        $kate = UserController::create('kate');

        $this->json('POST', '/api/v1/subscribe', [
            'requestor' => 'lisa@example.com',
            'target' => 'kate@example.com'
        ]);

        $this->assertDatabaseMissing('friends', [
            'user1_id' => $lisa->id,
            'user2_id' => $kate->id
        ]);
    }

    /** @test */
    public function Subscribe_RequestorIsAlreadySubscribedToTarget_ReturnsFalse()
    {
        $lisa = UserController::create('lisa');
        $kate = UserController::create('kate');
        SubscriptionController::create($lisa->id, $kate->id);

        $response = $this->json('POST', '/api/v1/subscribe', [
            'requestor' => 'lisa@example.com',
            'target' => 'kate@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function Subscribe_RequestorIsSamePersonAsTarget_ReturnsFalse()
    {
        UserController::create('lisa');

        $response = $this->json('POST', '/api/v1/subscribe', [
            'requestor' => 'lisa@example.com',
            'target' => 'lisa@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function Subscribe_AtLeastOneUserDoesntExist_ReturnsFalse()
    {
        UserController::create('lisa');
        UserController::create('kate');

        $response = $this->json('POST', '/api/v1/subscribe', [
            'requestor' => 'nonexistent@example.com',
            'target' => 'kate@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);

        $response = $this->json('POST', '/api/v1/subscribe', [
            'requestor' => 'lisa@example.com',
            'target' => 'nonexistent@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function Subscribe_InvalidInput_ReturnsStatus400()
    {
        $response = $this->json('POST', '/api/v1/subscribe', [
            'requestor' => 'lisa@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/subscribe', [
            'target' => 'john@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/subscribe', [
            'subscriber' => 'lisa@example.com',
            'target' => 'john@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/subscribe', [
            'requestor' => ['lisa@example.com', 'kate@example.com'],
            'target' => 'john@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/subscribe', [
            'requestor' => 'lisa@example.com',
            'target' => ['john@example.com', 'kate@example.com']
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/subscribe', [
            'requestor' => 123456,
            'target' => 'john@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/subscribe', [
            'requestor' => 'lisa@example.com',
            'target' => 123456
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function Subscribe_RequestContentTypeIsNotJson_ReturnsStatus400()
    {
        $response = $this->post('/api/v1/subscribe', [
            'requestor' => 'lisa@example.com',
            'target' => 'kate@example.com'
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function Block_RequestorHasNotBlockedTarget_ReturnsTrue()
    {
        UserController::create('andy');
        UserController::create('john');

        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => 'andy@example.com',
            'target' => 'john@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => true,]);
    }

    /** @test */
    public function Block_RequestorHasNotBlockedTarget_UserIdsAddedToBlocksTable()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');

        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => 'andy@example.com',
            'target' => 'john@example.com'
        ]);
        $this->assertDatabaseHas('blocks', [
            'requestor_id' => $andy->id,
            'target_id' => $john->id
        ]);
    }

    /** @test */
    public function Block_RequestorHasAlreadyBlockedTarget_ReturnsFalse()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        BlockController::create($andy->id, $john->id);

        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => 'andy@example.com',
            'target' => 'john@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function Block_PreviousTargetNowWantsToBlockPreviousRequestor_ReturnsTrue()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        BlockController::create($andy->id, $john->id);

        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => 'john@example.com',
            'target' => 'andy@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => true,]);
    }

    /** @test */
    public function Block_RequestorIsSamePersonAsTarget_ReturnsFalse()
    {
        UserController::create('andy');

        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => 'andy@example.com',
            'target' => 'andy@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function Block_BothUsersAreAlreadyFriends_BothUsersRemainAsFriends()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        FriendController::create($andy->id, $john->id);

        $this->post('/api/v1/block', [
            'requestor' => 'andy@example.com',
            'target' => 'john@example.com'
        ]);
        $this->post('/api/v1/block', [
            'requestor' => 'john@example.com',
            'target' => 'andy@example.com'
        ]);

        $this->assertDatabaseHas('friends', [
            'user1_id' => $andy->id,
            'user2_id' => $john->id
        ]);
    }

    /** @test */
    public function Block_AtLeastOneUserDoesntExist_ReturnsFalse()
    {
        UserController::create('andy');
        UserController::create('john');

        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => 'nonexistent@example.com',
            'target' => 'john@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);

        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => 'andy@example.com',
            'target' => 'nonexistent@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function Block_InvalidInput_ReturnsStatus400()
    {
        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => 'andy@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/block', [
            'target' => 'john@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/block', [
            'blocker' => 'andy@example.com',
            'target' => 'john@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => 'andy@example.com',
            'blocked' => 'john@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => ['andy@example.com', 'lisa@example.com'],
            'target' => 'john@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => 'andy@example.com',
            'target' => ['john@example.com', 'lisa@example.com']
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => 123456,
            'target' => 'john@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('POST', '/api/v1/block', [
            'requestor' => 'andy@example.com',
            'target' => 123456
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function Block_RequestContentTypeIsNotJson_ReturnsStatus400()
    {
        $response = $this->post('/api/v1/block', [
            'requestor' => 'andy@example.com',
            'target' => 'john@example.com'
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function GetUpdateRecipients_SendersFriendDidntBlockSender_ReturnsCorrectJson()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        FriendController::create($andy->id, $john->id);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 'john@example.com',
            'text' => 'Hello World!'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'recipients' => ['andy@example.com']
            ]);
    }

    /** @test */
    public function GetUpdateRecipients_SendersFriendBlockedSender_ReturnsCorrectJson()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        FriendController::create($andy->id, $john->id);
        BlockController::create($andy->id, $john->id);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 'john@example.com',
            'text' => 'Hello World!'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'recipients' => []
            ]);
    }

    /** @test */
    public function GetUpdateRecipients_SubscriberDidntBlockSender_ReturnsCorrectJson()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        SubscriptionController::create($andy->id, $john->id);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 'john@example.com',
            'text' => 'Hello World!'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'recipients' => ['andy@example.com']
            ]);
    }

    /** @test */
    public function GetUpdateRecipients_SubscriberBlockedSender_ReturnsCorrectJson()
    {
        $andy = UserController::create('andy');
        $john = UserController::create('john');
        SubscriptionController::create($andy->id, $john->id);
        BlockController::create($andy->id, $john->id);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 'john@example.com',
            'text' => 'Hello World!'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'recipients' => []
            ]);
    }

    /** @test */
    public function GetUpdateRecipients_MentionedUserDidntBlockSender_ReturnsCorrectJson()
    {
        UserController::create('john');
        UserController::create('kate');

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 'john@example.com',
            'text' => 'Hello World! kate@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'recipients' => ['kate@example.com']
            ]);
    }

    /** @test */
    public function GetUpdateRecipients_MentionedUserBlockedSender_ReturnsCorrectJson()
    {
        $john = UserController::create('john');
        $kate = UserController::create('kate');
        BlockController::create($kate->id, $john->id);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 'john@example.com',
            'text' => 'Hello World! kate@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'recipients' => []
            ]);
    }

    /** @test */
    public function GetUpdateRecipients_SenderDoesntExist_ReturnsFalse()
    {
        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 'john@example.com',
            'text' => 'Hello World! kate@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson(['success' => false,]);
    }

    /** @test */
    public function GetUpdateRecipients_RecipientIsSubscriberAndFriendAndMentionedUser_NoDuplicatesInReturnedJson()
    {
        $john = UserController::create('john');
        $kate = UserController::create('kate');
        FriendController::create($john->id, $kate->id);
        SubscriptionController::create($kate->id, $john->id);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 'john@example.com',
            'text' => 'Hello World! kate@example.com'
        ]);
        $response
            ->assertStatus(200)
            ->assertExactJson([
                'success' => true,
                'recipients' => ['kate@example.com']
            ]);
    }

    /** @test */
    public function GetUpdateRecipients_InvalidInput_ReturnsStatus400()
    {
        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 'john@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'text' => 'Hello World! kate@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'updater' => 'john@example.com',
            'text' => 'Hello World! kate@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 'john@example.com',
            'update' => 'Hello World! kate@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => ['john@example.com', 'lisa@example.com'],
            'text' => 'Hello World! kate@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 'john@example.com',
            'text' => ['Hello World! kate@example.com', 'sometext']
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 123456,
            'text' => 'Hello World! kate@example.com'
        ]);
        $response->assertStatus(400);

        $response = $this->json('GET', '/api/v1/can-receive-updates', [
            'sender' => 'john@example.com',
            'text' => 123456
        ]);
        $response->assertStatus(400);
    }
}
