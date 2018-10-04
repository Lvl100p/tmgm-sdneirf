<?php

namespace Tests\Feature;

use App\User;
use App\Friend;
use App\Subscription;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FriendsMgmtApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function MakeFriends_BothUsersExistAndAreNotFriends_ReturnsTrue()
    {
        User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);
        User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);

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
        $andy = User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);
        $john = User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);

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
        User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);

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
        User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);
        User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);

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
        $andy = User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);
        $john = User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);
        Friend::create(['user1_id' => $andy->id, 'user2_id' => $john->id]);

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'john@example.com']
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
    public function MakeFriends_InputContentTypeIsNotJson_ReturnsStatus400()
    {
        $response = $this->post('/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'john@example.com']
        ]);
        $response->assertStatus(400);
    }

    /** @test */
    public function GetFriendsList_UserExistsAndHasOneFriend_ReturnsCorrectJson()
    {
        $andy = User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);
        $john = User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);
        Friend::create(['user1_id' => $andy->id, 'user2_id' => $john->id]);

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
        $andy = User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);
        $john = User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);
        $common = User::create([
            'name' => 'Common',
            'email' => 'common@example.com',
            'password' => bcrypt('secret')
        ]);
        Friend::create(['user1_id' => $andy->id, 'user2_id' => $john->id]);
        Friend::create(['user1_id' => $andy->id, 'user2_id' => $common->id]);
        Friend::create(['user1_id' => $john->id, 'user2_id' => $common->id]);

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
        User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);

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
        User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);
        User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);

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
        $andy = User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);
        $john = User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);
        $common = User::create([
            'name' => 'Common',
            'email' => 'common@example.com',
            'password' => bcrypt('secret')
        ]);
        Friend::create(['user1_id' => $andy->id, 'user2_id' => $common->id]);
        Friend::create(['user1_id' => $john->id, 'user2_id' => $common->id]);

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
        $andy = User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);
        $john = User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);
        $common1 = User::create([
            'name' => 'Common1',
            'email' => 'common1@example.com',
            'password' => bcrypt('secret')
        ]);
        $common2 = User::create([
            'name' => 'Common2',
            'email' => 'common2@example.com',
            'password' => bcrypt('secret')
        ]);
        Friend::create(['user1_id' => $andy->id, 'user2_id' => $common1->id]);
        Friend::create(['user1_id' => $andy->id, 'user2_id' => $common2->id]);
        Friend::create(['user1_id' => $john->id, 'user2_id' => $common1->id]);
        Friend::create(['user1_id' => $john->id, 'user2_id' => $common2->id]);

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
        $andy = User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);

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
        User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);
        User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);

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
        User::create([
            'name' => 'Lisa',
            'email' => 'lisa@example.com',
            'password' => bcrypt('secret')
        ]);
        User::create([
            'name' => 'Kate',
            'email' => 'kate@example.com',
            'password' => bcrypt('secret')
        ]);

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
        $lisa = User::create([
            'name' => 'Lisa',
            'email' => 'lisa@example.com',
            'password' => bcrypt('secret')
        ]);
        $kate = User::create([
            'name' => 'Kate',
            'email' => 'kate@example.com',
            'password' => bcrypt('secret')
        ]);

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
    public function Subscribe_RequestorIsAlreadySubscribedToTarget_ReturnsFalse()
    {
        $lisa = User::create([
            'name' => 'Lisa',
            'email' => 'lisa@example.com',
            'password' => bcrypt('secret')
        ]);
        $kate = User::create([
            'name' => 'Kate',
            'email' => 'kate@example.com',
            'password' => bcrypt('secret')
        ]);
        Subscription::create([
            'requestor_id' => $lisa->id ,
            'target_id' => $kate->id
        ]);

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
        User::create([
            'name' => 'Lisa',
            'email' => 'lisa@example.com',
            'password' => bcrypt('secret')
        ]);

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
        User::create([
            'name' => 'Lisa',
            'email' => 'lisa@example.com',
            'password' => bcrypt('secret')
        ]);
        User::create([
            'name' => 'Kate',
            'email' => 'kate@example.com',
            'password' => bcrypt('secret')
        ]);

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
    public function Subscribe_InputContentTypeIsNotJson_ReturnsStatus400()
    {
        $response = $this->post('/api/v1/subscribe', [
            'requestor' => 'lisa@example.com',
            'target' => 'kate@example.com'
        ]);
        $response->assertStatus(400);
    }
}
