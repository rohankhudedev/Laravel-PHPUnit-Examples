<?php

namespace Tests\Unit;

use App\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    //https://medium.com/@DCzajkowski/testing-laravel-authentication-flow-573ea0a96318
    public function test_user_can_view_a_login_form()
    {
        $response = $this->get('/login');
        $response->assertSuccessful();
        $response->assertViewIs('auth.login');
    }

    public function test_user_cannot_view_a_login_form_when_authenticated()
    {
        $user = factory(User::class)->make(); // Creates a user object
        $response = $this->actingAs($user)->get('/login');
        $response->assertRedirect('/home');
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $user = factory(User::class)->create([
            'password' => bcrypt($password = 'i-love-laravel'),
        ]);
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => $password,
        ]);
        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_incorrect_password()
    {
        /**
         * Asserting against session
        Let’s test attempting to login with incorrect password. What we have to do:

        create a user in the database with password x
        attempt logging in with password y
        assert user is redirected back
        assert there is an error in the session
        assert email field has old input
        assert password field does not have old input (for security measures)
        assert user is still a guest
         */
        $user = factory(User::class)->create([
            'password' => bcrypt('i-love-laravel'),
        ]);

        //->from() helper to make sure user is redirected back to the login page. Otherwise the request would come from ‘nowhere’, so user would be redirected to `/`
        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'invalid-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function test_remember_me_functionality()
    {
        /**
         * Asserting against cookies
        What we have to do:

        create a user
        make a request to the login page with remember turned on
        assert user is redirected to a correct page
        assert correct cookie is attached
        assert user is authenticated
         */
        $user = factory(User::class)->create([
            'id' => random_int(1, 100),
            'password' => bcrypt($password = 'i-love-laravel'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => $password,
            'remember' => 'on',
        ]);

        $response->assertRedirect('/home');
        // cookie assertion goes here
        //To assert against a cookie, we have to know what is its name and what values does it hold. Name of the cookie is available through the Auth facade. Just call Auth::guard()->getRecallerName(). Cookie’s value is user-id|remember-token|user's-hashed-password .
        $response->assertCookie(Auth::guard()->getRecallerName(), vsprintf('%s|%s|%s', [
            $user->id,
            $user->getRememberToken(),
            $user->password,
        ]));
        $this->assertAuthenticatedAs($user);
    }
    
}
