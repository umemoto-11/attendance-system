<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class AdminStaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_see_all_users_name_and_email()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $user1 = User::factory()->create(['name' => '山田太郎', 'email' => 'yamada@example.com']);
        $user2 = User::factory()->create(['name' => '佐藤花子', 'email' => 'sato@example.com']);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('山田太郎');
        $response->assertSee('yamada@example.com');
        $response->assertSee('佐藤花子');
        $response->assertSee('sato@example.com');
    }

    /** @test */
    public function admin_can_see_user_attendance_for_current_month()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $user = User::factory()->create(['name' => '山田太郎']);

        $today = today();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/users/{$user->id}/attendances");

        $response->assertStatus(200);
        $response->assertSee('山田太郎');
        $response->assertSee($today->isoFormat('MM/DD(ddd)'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('01:00');
        $response->assertSee('08:00');
    }

    /** @test */
    public function admin_can_view_previous_month_attendance()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $previousMonth = today()->subMonth()->startOfMonth();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $previousMonth,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/users/{$user->id}/attendances?month={$previousMonth->format('Y-m')}");

        $response->assertStatus(200);
        $response->assertSee($previousMonth->format('Y/m'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('01:00');
        $response->assertSee('08:00');
    }


    /** @test */
    public function admin_can_view_next_month_attendance()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $user = User::factory()->create();

        $nextMonth = today()->addMonth()->startOfMonth();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $nextMonth,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/users/{$user->id}/attendances?month={$nextMonth->format('Y-m')}");

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('01:00');
        $response->assertSee('08:00');
    }

    /** @test */
    public function admin_can_view_attendance_detail_from_list()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $user = User::factory()->create(['name' => '山田太郎']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)->get("/admin/users/{$user->id}/attendances");

        $response->assertSee('/admin/attendances/' . $attendance->id);

        $detailResponse = $this->actingAs($admin)->get('/admin/attendances/' . $attendance->id);

        $detailResponse->assertStatus(200);

        $year = $attendance->date->format('Y年');
        $monthDay = $attendance->date->format('n月j日');

        $detailResponse->assertSee($year);
        $detailResponse->assertSee($monthDay);
        $detailResponse->assertSee('09:00');
        $detailResponse->assertSee('18:00');
        $detailResponse->assertSee('12:00');
        $detailResponse->assertSee('13:00');
        $detailResponse->assertSee($user->name);
    }
}
