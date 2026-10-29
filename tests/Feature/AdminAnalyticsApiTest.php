<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAnalyticsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(string $role = User::ROLE_ADMIN): User
    {
        return User::factory()->create(['role' => $role, 'email_verified_at' => now()]);
    }

    public function test_analytics_returns_real_counts_for_the_selected_range(): void
    {
        Carbon::setTestNow('2026-03-23 10:00:00');

        $services = ComplaintCategory::factory()->create(['name' => 'Services']);
        $other = ComplaintCategory::factory()->create(['name' => 'Other']);

        Complaint::factory()->create(['complaint_category_id' => $services->id, 'status' => Complaint::STATUS_PENDING, 'created_at' => '2026-03-20 09:00:00']);
        Complaint::factory()->create(['complaint_category_id' => $services->id, 'status' => Complaint::STATUS_RESOLVED, 'created_at' => '2026-03-21 09:00:00', 'resolved_at' => '2026-03-21 11:00:00']);
        Complaint::factory()->create(['complaint_category_id' => $other->id, 'status' => Complaint::STATUS_REJECTED, 'created_at' => '2026-03-22 09:00:00']);
        Complaint::factory()->create(['complaint_category_id' => $other->id, 'created_at' => '2026-01-01 09:00:00']);

        $response = $this->actingAs($this->admin())
            ->getJson('/api/admin/analytics?from=2026-03-20&to=2026-03-23');

        $response->assertOk()
            ->assertJsonPath('range.bucket', 'day')
            ->assertJsonPath('totals.total', 3)
            ->assertJsonPath('totals.pending', 1)
            ->assertJsonPath('totals.resolved', 1)
            ->assertJsonPath('totals.rejected', 1)
            ->assertJsonPath('category_breakdown.0.name', 'Services')
            ->assertJsonPath('category_breakdown.0.total', 2)
            ->assertJsonPath('average_resolution_hours', 2)
            ->assertJsonCount(4, 'trend');
    }

    public function test_analytics_rejects_inverted_ranges_and_non_staff(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/api/admin/analytics?from=2026-03-23&to=2026-03-01')
            ->assertStatus(422);

        $user = User::factory()->create(['role' => User::ROLE_USER, 'email_verified_at' => now()]);
        $this->actingAs($user)->getJson('/api/admin/analytics')->assertForbidden();
    }

    public function test_dashboard_includes_category_breakdown(): void
    {
        $category = ComplaintCategory::factory()->create(['name' => 'Hostel']);
        Complaint::factory()->count(2)->create(['complaint_category_id' => $category->id]);

        $this->actingAs($this->admin())->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('category_breakdown.0.name', 'Hostel')
            ->assertJsonPath('category_breakdown.0.total', 2);
    }

    public function test_admin_complaint_list_supports_sorting(): void
    {
        $category = ComplaintCategory::factory()->create();
        Complaint::factory()->create(['complaint_category_id' => $category->id, 'title' => 'Bravo']);
        Complaint::factory()->create(['complaint_category_id' => $category->id, 'title' => 'Alpha']);

        $this->actingAs($this->admin())->getJson('/api/admin/complaints?sort=title&direction=asc')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Alpha');

        $this->actingAs($this->admin())->getJson('/api/admin/complaints?sort=password&direction=asc')
            ->assertOk();
    }

    public function test_audit_logs_are_limited_to_managers_and_admins(): void
    {
        AuditLog::query()->create(['action' => 'complaint.admin_updated', 'description' => 'Complaint updated by admin portal.']);

        $this->actingAs($this->admin())->getJson('/api/admin/audit-logs')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'complaint.admin_updated');

        $this->actingAs($this->admin(User::ROLE_SUPPORT_AGENT))->getJson('/api/admin/audit-logs')
            ->assertForbidden();
    }
}
