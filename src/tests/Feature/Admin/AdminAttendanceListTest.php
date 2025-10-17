<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_see_all_users_attendance_for_the_day()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $user1 = User::factory()->create(['name' => '山田太郎']);
        $user2 = User::factory()->create(['name' => '佐藤花子']);

        $today = today();

        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'date' => $today,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'date' => $today,
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance2->id,
            'break_start' => '13:00:00',
            'break_end' => '14:00:00',
        ]);

        $response = $this->actingAs($admin)->get('/admin/attendances');

        $response->assertStatus(200);
        $response->assertSee('山田太郎');
        $response->assertSee('佐藤花子');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('01:00');
        $response->assertSee('08:00');
        $response->assertSee($today->format('Y年m月d日'));
    }

    /** @test */
    public function shows_today_date_by_default()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $today = today();

        $response = $this->get('/admin/attendances');

        $response->assertStatus(200);
        $response->assertSee($today->format('Y/m/d'));
    }

    /** @test */
    public function shows_previous_day_when_clicking_previous()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $user = User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $previousDay = today()->subDay();

        Attendance::create([
            'user_id' => $user->id,
            'date' => $previousDay->copy()->startOfDay(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get('/admin/attendances/?date=' . $previousDay->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertSee($previousDay->format('Y/m/d'));
    }

    /** @test */
    public function shows_next_day_when_clicking_next()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $user = User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $nextDay = today()->addDay();

        Attendance::create([
            'user_id' => $user->id,
            'date' => $nextDay->copy()->startOfDay(),
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get('/admin/attendances/?date=' . $nextDay->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertSee($nextDay->format('Y/m/d'));
    }
}
