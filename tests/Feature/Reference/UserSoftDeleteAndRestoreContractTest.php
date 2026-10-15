<?php

namespace Tests\Feature\Reference;

use App\Models\Division;
use App\Models\IncomingLetter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserSoftDeleteAndRestoreContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_user_with_unique_credentials(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create(['status' => 'active']);

        $response = $this->actingAs($superAdmin)->postJson('/api/v1/users', [
            'username' => 'operator_baru',
            'name' => 'Operator Baru Dikdas',
            'email' => 'operator_baru@disdikbudpora.test',
            'password' => 'password123',
            'role' => 'operator',
            'division_id' => $division->id,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.username', 'operator_baru')
            ->assertJsonPath('data.email', 'operator_baru@disdikbudpora.test');

        $this->assertDatabaseHas('users', [
            'username' => 'operator_baru',
            'email' => 'operator_baru@disdikbudpora.test',
            'deleted_at' => null,
        ]);
    }

    public function test_rejects_username_and_email_colliding_with_active_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create(['status' => 'active']);

        User::factory()->create([
            'username' => 'active_user',
            'email' => 'active@disdikbudpora.test',
            'division_id' => $division->id,
        ]);

        // Attempt with same username
        $responseUser = $this->actingAs($superAdmin)->postJson('/api/v1/users', [
            'username' => 'active_user',
            'name' => 'Duplikat User',
            'email' => 'different@disdikbudpora.test',
            'password' => 'password123',
            'role' => 'operator',
            'division_id' => $division->id,
        ]);

        $responseUser->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
        $this->assertEquals(
            'Username atau email sudah digunakan oleh akun lain.',
            $responseUser->json('errors.username.0')
        );

        // Attempt with same email
        $responseEmail = $this->actingAs($superAdmin)->postJson('/api/v1/users', [
            'username' => 'different_user',
            'name' => 'Duplikat Email',
            'email' => 'active@disdikbudpora.test',
            'password' => 'password123',
            'role' => 'operator',
            'division_id' => $division->id,
        ]);

        $responseEmail->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
        $this->assertEquals(
            'Username atau email sudah digunakan oleh akun lain.',
            $responseEmail->json('errors.email.0')
        );
    }

    public function test_rejects_username_and_email_colliding_with_soft_deleted_user_with_informative_message(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create(['status' => 'active']);

        $trashedUser = User::factory()->create([
            'username' => 'rif',
            'email' => 'r@gmail.com',
            'division_id' => $division->id,
        ]);
        $trashedUser->delete();

        $this->assertSoftDeleted('users', ['id' => $trashedUser->id]);

        // Attempt with soft-deleted username
        $responseUser = $this->actingAs($superAdmin)->postJson('/api/v1/users', [
            'username' => 'rif',
            'name' => 'Rif Baru',
            'email' => 'different_rif@disdikbudpora.test',
            'password' => 'password123',
            'role' => 'operator',
            'division_id' => $division->id,
        ]);

        $responseUser->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
        $this->assertEquals(
            'Akun dengan username atau email tersebut pernah terdaftar dan saat ini berada dalam arsip pengguna. Silakan hubungi administrator untuk memulihkan akun.',
            $responseUser->json('errors.username.0')
        );

        // Attempt with soft-deleted email
        $responseEmail = $this->actingAs($superAdmin)->postJson('/api/v1/users', [
            'username' => 'rif_lain',
            'name' => 'Rif Email Sama',
            'email' => 'r@gmail.com',
            'password' => 'password123',
            'role' => 'operator',
            'division_id' => $division->id,
        ]);

        $responseEmail->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
        $this->assertEquals(
            'Akun dengan username atau email tersebut pernah terdaftar dan saat ini berada dalam arsip pengguna. Silakan hubungi administrator untuk memulihkan akun.',
            $responseEmail->json('errors.email.0')
        );
    }

    public function test_trashed_users_listing_respects_division_scoping(): void
    {
        $divisionA = Division::factory()->create(['name' => 'Divisi A', 'status' => 'active']);
        $divisionB = Division::factory()->create(['name' => 'Divisi B', 'status' => 'active']);

        $adminA = User::factory()->divisionAdmin()->create(['division_id' => $divisionA->id]);

        $trashedA = User::factory()->create(['username' => 'trashed_a', 'division_id' => $divisionA->id]);
        $trashedA->delete();

        $trashedB = User::factory()->create(['username' => 'trashed_b', 'division_id' => $divisionB->id]);
        $trashedB->delete();

        $response = $this->actingAs($adminA)->get('/master/pengguna?status=trashed');

        $response->assertOk();
        $response->assertSee('trashed_a');
        $response->assertDontSee('trashed_b');
        $response->assertSee('Pengguna Terhapus (Arsip)');
    }

    public function test_super_admin_can_restore_trashed_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create(['status' => 'active']);

        $user = User::factory()->create([
            'username' => 'rif',
            'email' => 'r@gmail.com',
            'division_id' => $division->id,
            'is_active' => true,
        ]);
        $originalId = $user->id;
        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $originalId]);

        $response = $this->actingAs($superAdmin)->postJson("/api/v1/users/{$originalId}/restore");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $originalId)
            ->assertJsonPath('data.username', 'rif')
            ->assertJsonPath('data.is_active', false);

        // Assert record is restored with same primary key
        $restoredUser = User::find($originalId);
        $this->assertNotNull($restoredUser);
        $this->assertNull($restoredUser->deleted_at);
        $this->assertFalse($restoredUser->is_active);

        // Assert audit log was recorded
        $this->assertDatabaseHas('audit_logs', [
            'event_code' => 'USER_RESTORED',
            'user_id' => $superAdmin->id,
            'auditable_id' => $originalId,
        ]);
    }

    public function test_division_admin_can_only_restore_operator_in_own_division(): void
    {
        $divisionA = Division::factory()->create(['status' => 'active']);
        $divisionB = Division::factory()->create(['status' => 'active']);

        $adminA = User::factory()->divisionAdmin()->create(['division_id' => $divisionA->id]);

        $operatorA = User::factory()->create([
            'role' => 'operator',
            'division_id' => $divisionA->id,
        ]);
        $operatorA->delete();

        $operatorB = User::factory()->create([
            'role' => 'operator',
            'division_id' => $divisionB->id,
        ]);
        $operatorB->delete();

        $divisionAdminA = User::factory()->divisionAdmin()->create([
            'division_id' => $divisionA->id,
        ]);
        $divisionAdminA->delete();

        // 1. Restore own operator -> Allowed
        $this->actingAs($adminA)
            ->postJson("/api/v1/users/{$operatorA->id}/restore")
            ->assertOk();

        // 2. Restore foreign operator -> Denied 403
        $this->actingAs($adminA)
            ->postJson("/api/v1/users/{$operatorB->id}/restore")
            ->assertForbidden();

        // 3. Restore another division admin -> Denied 403
        $this->actingAs($adminA)
            ->postJson("/api/v1/users/{$divisionAdminA->id}/restore")
            ->assertForbidden();
    }

    public function test_operator_is_denied_from_restoring_users(): void
    {
        $division = Division::factory()->create(['status' => 'active']);
        $operator = User::factory()->create(['role' => 'operator', 'division_id' => $division->id]);

        $trashedUser = User::factory()->create(['division_id' => $division->id]);
        $trashedUser->delete();

        $this->actingAs($operator)
            ->postJson("/api/v1/users/{$trashedUser->id}/restore")
            ->assertForbidden();

        $this->actingAs($operator)
            ->get('/master/pengguna')
            ->assertForbidden();
    }

    public function test_user_cannot_restore_self(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        // Cannot restore self
        $this->actingAs($superAdmin)
            ->postJson("/api/v1/users/{$superAdmin->id}/restore")
            ->assertForbidden();
    }

    public function test_restore_fails_when_user_division_is_inactive(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $inactiveDivision = Division::factory()->create(['status' => 'inactive']);

        $user = User::factory()->create([
            'division_id' => $inactiveDivision->id,
            'role' => 'operator',
        ]);
        $user->delete();

        $response = $this->actingAs($superAdmin)
            ->postJson("/api/v1/users/{$user->id}/restore");

        $response->assertStatus(422)
            ->assertJsonPath('code', 'ERR_422_INACTIVE_DIVISION');
    }

    public function test_restore_fails_when_active_account_conflicts_with_username_or_email(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create(['status' => 'active']);

        $trashed = User::factory()->create([
            'username' => 'conflict_user',
            'division_id' => $division->id,
        ]);
        $trashed->delete();

        // Temporarily drop unique index in DB to simulate data anomaly/collision
        Schema::table('users', function ($table) {
            $table->dropUnique('users_username_unique');
        });

        // Someone created another user directly in DB
        User::factory()->create([
            'username' => 'conflict_user',
            'division_id' => $division->id,
        ]);

        $response = $this->actingAs($superAdmin)
            ->postJson("/api/v1/users/{$trashed->id}/restore");

        $response->assertStatus(409)
            ->assertJsonPath('code', 'ERR_409_CONFLICT');
    }

    public function test_soft_deleted_user_cannot_login(): void
    {
        $division = Division::factory()->create(['status' => 'active']);
        $user = User::factory()->create([
            'username' => 'deleted_operator',
            'password' => Hash::make('password123'),
            'division_id' => $division->id,
            'is_active' => true,
        ]);
        $user->delete();

        $response = $this->postJson('/login', [
            'username' => 'deleted_operator',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
        $this->assertGuest();
    }

    public function test_browser_web_restore_redirects_back_with_flash_message(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create(['status' => 'active']);

        $user = User::factory()->create([
            'username' => 'web_restore_user',
            'division_id' => $division->id,
        ]);
        $user->delete();

        $response = $this->actingAs($superAdmin)
            ->from('/master/pengguna?status=trashed')
            ->post("/master/pengguna/{$user->id}/restore");

        $response->assertRedirect('/master/pengguna?status=trashed');
        $response->assertSessionHas('success', "Akun pengguna {$user->username} berhasil dipulihkan.");
    }

    public function test_historical_letter_relation_preserved_after_restore(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $division = Division::factory()->create(['status' => 'active']);

        $operator = User::factory()->create([
            'username' => 'historical_operator',
            'division_id' => $division->id,
        ]);

        $letter = IncomingLetter::create([
            'uuid' => (string) Str::uuid(),
            'division_id' => $division->id,
            'agenda_number' => 101,
            'letter_number' => 'SURAT/HIST/001',
            'received_date' => now()->toDateString(),
            'letter_date' => now()->toDateString(),
            'origin_agency' => 'Dinas Pendidikan',
            'subject' => 'Surat Pengujian Arsip Histori',
            'created_by_user_id' => $operator->id,
            'status' => 'completed',
        ]);

        $this->assertEquals($operator->id, $letter->createdBy->id);

        // Soft delete operator - historical relation still readable thanks to withTrashed()
        $operator->delete();
        $letter->refresh();
        $this->assertEquals($operator->id, $letter->created_by_user_id);
        $this->assertEquals($operator->id, $letter->createdBy->id);

        // Restore operator
        $this->actingAs($superAdmin)->postJson("/api/v1/users/{$operator->id}/restore")->assertOk();

        $letter->refresh();
        $this->assertEquals($operator->id, $letter->createdBy->id);
        $this->assertEquals('historical_operator', $letter->createdBy->username);
    }
}
