<?php

namespace Tests\Feature\Auth;

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
