<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisterTest extends TestCase
{
    use WithoutMiddleware; // use this trait
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $this->assertGuest($guard = null);


        $user = [
            'name' => 'Joe',
            'email' => 'testemail@test.com',
            'password' => 'passwordtest',
            'password_confirmation' => 'passwordtest'
        ];

        $response = $this->post('/register', $user);

        $response->assertRedirect('/home');

        //Remove password and password_confirmation from array
        array_splice($user,2, 2);

        $this->assertDatabaseHas('users', $user);
        $this->assertAuthenticated($guard = null);
    }
}
