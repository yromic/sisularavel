<?php

namespace Tests\Feature\Auth;

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login_and_logout_with_session_regeneration(): void
    {
        $division = Division::factory()->create();
        $user = User::factory()->for($division)->create([
            'username' => 'op_dikdas01',
            'password' => Hash::make('correct-password'),
        ]);

        $this->withSession(['before_login' => true]);
        $before = session()->getId();

        $login = $this->postJson('/login', [
            'username' => 'op_dikdas01',
            'password' => 'correct-password',
        ]);

        $login
            ->assertOk()
            ->assertJsonPath('user.username', 'op_dikdas01');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($before, session()->getId());

        $this->postJson('/logout')->assertOk();
        $this->assertGuest();
    }

    public function test_legacy_password_material_is_rejected_for_migrated_account(): void
    {
        User::factory()->create([
            'username' => 'miftah',
            'password' => Hash::make(Str::random(40)),
            'must_reset_password' => true,
        ]);

        $this->postJson('/login', [
            'username' => 'miftah',
            'password' => 'legacy-plaintext-password',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Username atau Password salah!');
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        User::factory()->create([
            'username' => 'inactive_operator',
            'password' => Hash::make('correct-password'),
            'is_active' => false,
        ]);

        $this->postJson('/login', [
            'username' => 'inactive_operator',
            'password' => 'correct-password',
        ])->assertUnauthorized();
    }

    public function test_login_rate_limit_locks_username_and_ip_after_five_failures(): void
    {
        User::factory()->create([
            'username' => 'op_dikdas01',
            'password' => Hash::make('correct-password'),
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/login', [
                'username' => 'op_dikdas01',
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/login', [
            'username' => 'op_dikdas01',
            'password' => 'wrong-password',
        ])
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'ERR_429_RATE_LIMIT')
            ->assertJsonPath('message', 'Terlalu banyak percobaan login. Silakan tunggu 15 menit.');

        $this->assertDatabaseHas('audit_logs', [
            'username' => 'op_dikdas01',
            'event_code' => 'SEC_RATE_LIMIT_EXCEEDED',
        ]);
    }

    public function test_web_form_login_redirects_to_dashboard_and_logout_redirects_to_login(): void
    {
        $user = User::factory()->create([
            'username' => 'web_admin',
            'password' => Hash::make('password'),
        ]);

        $this->post('/login', [
            'username' => 'web_admin',
            'password' => 'password',
        ])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_login_issues_remember_cookie_by_default(): void
    {
        $user = User::factory()->create([
            'username' => 'persisted_user',
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->post('/login', [
            'username' => 'persisted_user',
            'password' => 'secret-password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertNotNull($user->remember_token);
        $response->assertCookie(Auth::guard('web')->getRecallerName());
    }

    public function test_login_respects_opt_out_when_remember_is_false(): void
    {
        $user = User::factory()->create([
            'username' => 'temporary_user',
            'password' => Hash::make('secret-password'),
            'remember_token' => null,
        ]);

        $response = $this->post('/login', [
            'username' => 'temporary_user',
            'password' => 'secret-password',
            'remember' => '0',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $response->assertCookieMissing(Auth::guard('web')->getRecallerName());
    }

    public function test_user_is_seamlessly_reauthenticated_via_remember_cookie_when_session_is_lost(): void
    {
        $division = Division::factory()->create();
        $user = User::factory()->for($division)->create([
            'username' => 'durable_operator',
            'password' => Hash::make('secret-password'),
        ]);

        // 1. Initial Login
        $loginResponse = $this->post('/login', [
            'username' => 'durable_operator',
            'password' => 'secret-password',
            'remember' => '1',
        ]);
        $loginResponse->assertRedirect('/dashboard');

        $recallerName = Auth::guard('web')->getRecallerName();
        $recallerCookie = $loginResponse->getCookie($recallerName);
        $this->assertNotNull($recallerCookie);

        // 2. Simulate complete loss of server session (e.g. browser restart / session garbage collection)
        $this->flushSession();
        Auth::guard('web')->forgetUser();

        // 3. User visits protected dashboard with ONLY the remember cookie
        // In Laravel testing, cookies on the response from the test client can be sent directly using withCookie()
        $subsequentResponse = $this->withCookie($recallerName, $recallerCookie->getValue())
            ->get('/dashboard');

        $subsequentResponse->assertOk();
        $this->assertAuthenticatedAs($user);
    }
}
