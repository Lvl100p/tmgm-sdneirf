<?php

namespace Tests\Feature;

use App\User;
use App\Friend;
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
            ->assertJson(['success' => true,]);
    }

    /** @test */
    public function MakeFriends_BothUsersExistAndAreNotFriends_UserIdsAddedToFriendsTableWithNumericallySmallerIdAsUser1Id()
    {
        $user1 = User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);
        $user2 = User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);

        $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'john@example.com']
        ]);

        $this->assertTrue($user1->id < $user2->id);
        $this->assertDatabaseHas('friends', [
            'user1_id' => $user1->id, 'user2_id' => $user2->id
        ]);
        $this->assertDatabaseMissing('friends', [
            'user1_id' => $user2->id, 'user2_id' => $user1->id
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
            ->assertJson(['success' => false,]);
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
            ->assertJson(['success' => false,]);

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'nonexistent@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertJson(['success' => false,]);
    }

    /** @test */
    public function MakeFriends_BothUsersExistButAreAlreadyFriends_ReturnsFalse()
    {
        $user1 = User::create([
            'name' => 'Andy',
            'email' => 'andy@example.com',
            'password' => bcrypt('secret')
        ]);
        $user2 = User::create([
            'name' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('secret')
        ]);
        Friend::create(['user1_id' => $user1->id, 'user2_id' => $user2->id]);

        $response = $this->json('POST', '/api/v1/make-friends', [
            'friends' => ['andy@example.com', 'john@example.com']
        ]);
        $response
            ->assertStatus(200)
            ->assertJson(['success' => false,]);
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
    }
}
