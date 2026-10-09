<?php

namespace Tests\Feature\Seeders;

use App\Models\Division;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_login_accounts_for_each_role(): void
    {
        $this->seed();

        $expectedUsers = [
            ['username' => 'superadmin', 'role' => 'super_admin', 'division' => null],
            ['username' => 'admin_dikdas', 'role' => 'division_admin', 'division' => 'DIKDAS'],
            ['username' => 'op_dikdas01', 'role' => 'operator', 'division' => 'DIKDAS'],
            ['username' => 'admin_paud', 'role' => 'division_admin', 'division' => 'PAUD'],
            ['username' => 'op_paud01', 'role' => 'operator', 'division' => 'PAUD'],
        ];

        foreach ($expectedUsers as $expectedUser) {
            $user = User::query()->where('username', $expectedUser['username'])->firstOrFail();

            $this->assertSame($expectedUser['role'], $user->role);
            $this->assertTrue($user->is_active);
            $this->assertFalse($user->must_reset_password);
            $this->assertTrue(Hash::check('password', $user->password));

            if ($expectedUser['division'] === null) {
                $this->assertNull($user->division_id);
            } else {
                $this->assertSame(
                    Division::query()->where('code', $expectedUser['division'])->value('id'),
                    $user->division_id
                );
            }
        }
    }

    public function test_seeded_accounts_can_login_with_password(): void
    {
        $this->seed();

        foreach (['superadmin', 'admin_dikdas', 'op_dikdas01', 'admin_paud', 'op_paud01'] as $username) {
            $this->postJson('/login', [
                'username' => $username,
                'password' => 'password',
            ])
                ->assertOk()
                ->assertJsonPath('user.username', $username);

            $this->postJson('/logout')->assertOk();
        }
    }
}
