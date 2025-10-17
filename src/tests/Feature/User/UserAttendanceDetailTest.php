<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function attendance_detail_displays_logged_in_name()
    {
        $user = User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2025-10-15',
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id . '?date=' . $attendance->date);

        $response->assertStatus(200);
        $response->assertSee($user->name);
    }

    /** @test */
    public function attendance_detail_displays_selected_date()
    {
        $user = User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2025-10-15',
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id . '?date=' . $attendance->date);

        $response->assertStatus(200);
        $response->assertSee('2025年');
        $response->assertSee('10月15日');
    }

    /** @test */
    public function attendance_detail_displays_clock_in_and_clock_out_times()
    {
        $user = User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2025-10-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id . '?date=' . $attendance->date);

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** @test */
    public function attendance_detail_displays_break_times()
    {
        $user = User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2025-10-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ]);

        $response = $this->get('/attendance/detail/' . $attendance->id . '?date=' . $attendance->date);

        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
