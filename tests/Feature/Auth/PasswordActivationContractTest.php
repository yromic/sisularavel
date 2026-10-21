<?php

namespace Tests\Feature\Auth;

use App\Models\Division;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordActivationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sets_hashed_token_and_returns_generic_response(): void
    {
        $user = User::factory()->create([
            'email' => 'operator@example.test',
            'password_reset_token' => null,
            'password_reset_expires_at' => null,
        ]);

        Notification::fake();

        $this->postJson('/forgot-password', [
            'email' => 'operator@example.test',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Jika akun terdaftar, tautan reset telah dikirimkan')
            ->assertJsonMissing(['email' => 'operator@example.test'])
            ->assertJsonMissing(['token']);

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class
        );

        $user->refresh();

        $this->assertNotNull($user->password_reset_token);
        $this->assertSame(64, strlen($user->password_reset_token));
        $this->assertTrue($user->password_reset_expires_at->greaterThan(now()->addHours(23)));
    }

    public function test_password_reset_token_sets_new_password_and_clears_activation_state(): void
    {
        $plainToken = Str::random(64);
        $user = User::factory()->create([
            'username' => 'operator_lama',
            'password' => Hash::make(Str::random(40)),
            'must_reset_password' => true,
            'password_reset_token' => hash('sha256', $plainToken),
            'password_reset_expires_at' => now()->addDay(),
        ]);

        $this->postJson('/password/reset', [
            'token' => $plainToken,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertOk();

        $user->refresh();
        $this->assertFalse($user->must_reset_password);
        $this->assertNull($user->password_reset_token);
        $this->assertNull($user->password_reset_expires_at);
        $this->assertTrue(Hash::check('new-secure-password', $user->password));
    }

    public function test_admin_can_issue_one_time_activation_secret_for_same_division_user(): void
    {
        $division = Division::factory()->create();
        $admin = User::factory()->for($division)->divisionAdmin()->create();
        $operator = User::factory()->for($division)->create([
            'must_reset_password' => true,
        ]);

        $response = $this->actingAs($admin)->postJson("/admin/users/{$operator->id}/activation-secret");

        $response
            ->assertOk()
            ->assertJsonStructure(['temporary_secret', 'expires_at']);

        $secret = $response->json('temporary_secret');
        $operator->refresh();

        $this->assertTrue(Hash::check($secret, $operator->password));
        $this->assertTrue($operator->must_reset_password);
        $this->assertNotNull($operator->password_reset_expires_at);
        $this->assertDatabaseMissing('audit_logs', [
            'new_values' => $secret,
        ]);
    }

    public function test_must_reset_user_is_blocked_until_setting_permanent_password(): void
    {
        $user = User::factory()->create([
            'username' => 'operator_lama',
            'password' => Hash::make('temporary-secret'),
            'must_reset_password' => true,
            'password_reset_expires_at' => now()->addDay(),
        ]);

        $this->postJson('/login', [
            'username' => 'operator_lama',
            'password' => 'temporary-secret',
        ])
            ->assertStatus(403)
            ->assertJsonPath('redirect', '/password/activate');

        $this->actingAs($user)->getJson('/dashboard')
            ->assertStatus(403)
            ->assertJsonPath('code', 'ERR_PASSWORD_RESET_REQUIRED');

        $this->actingAs($user)->postJson('/password/activate', [
            'password' => 'permanent-secure-password',
            'password_confirmation' => 'permanent-secure-password',
        ])->assertOk();

        $user->refresh();
        $this->assertFalse($user->must_reset_password);
        $this->assertTrue(Hash::check('permanent-secure-password', $user->password));

        $this->actingAs($user)->getJson('/dashboard')->assertOk();
    }

    public function test_division_admin_cannot_issue_secret_for_other_division_user(): void
    {
        $admin = User::factory()->for(Division::factory())->divisionAdmin()->create();
        $otherDivisionUser = User::factory()->for(Division::factory())->create();

        $this->actingAs($admin)
            ->postJson("/admin/users/{$otherDivisionUser->id}/activation-secret")
            ->assertForbidden();
    }
}
