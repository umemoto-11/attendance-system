<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AdminAttendanceApprovalTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function pending_correction_requests_are_displayed()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'corrected_clock_in' => '09:30:00',
            'corrected_clock_out' => '18:30:00',
            'corrected_reason' => 'test1',
            'status' => 'pending',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'date' => today(),
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
            'corrected_clock_in' => '10:30:00',
            'corrected_clock_out' => '19:30:00',
            'corrected_reason' => 'test2',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.requests.index', ['tab' => 'pending']));

        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee($user1->name);
        $response->assertSee($user2->name);
        $response->assertSee(today()->format('Y/m/d'));
        $response->assertSee('test1');
        $response->assertSee('test2');
        $response->assertSee($attendance1->created_at->format('Y/m/d'));
        $response->assertSee($attendance2->created_at->format('Y/m/d'));
    }

    /** @test */
    public function approved_correction_requests_are_displayed()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'corrected_clock_in' => '09:30:00',
            'corrected_clock_out' => '18:30:00',
            'corrected_reason' => 'test',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.requests.index', ['tab' => 'approved']));

        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee($user->name);
        $response->assertSee(today()->format('Y/m/d'));
        $response->assertSee('test');
        $response->assertSee($attendance->created_at->format('Y/m/d'));
    }

    /** @test */
    public function correction_request_details_are_correctly_displayed()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'corrected_clock_in' => '09:30:00',
            'corrected_clock_out' => '18:30:00',
            'corrected_reason' => 'test',
            'status' => 'pending',
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.corrections.show', $attendance->id));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee(today()->format('Y年'));
        $response->assertSee(today()->format('n月j日'));
        $response->assertSee('09:30');
        $response->assertSee('18:30');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('test');
    }

    /** @test */
    public function admin_can_approve_correction_request()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'corrected_clock_in' => '09:30:00',
            'corrected_clock_out' => '18:30:00',
            'corrected_reason' => 'test',
            'status' => 'pending',
        ]);

        $break = BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.corrections.approved', $attendance->id));

        $response->assertRedirect(route('admin.corrections.show', $attendance->id));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '09:30:00',
            'clock_out' => '18:30:00',
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);
    }
}
