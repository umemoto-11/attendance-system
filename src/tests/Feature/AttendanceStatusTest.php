<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class AttendanceStatusTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_off_duty_status_when_not_working()
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

        $response = $this->get('/attendance');
        $response->assertSee('勤務外');
    }

    public function it_displays_working_status_when_clocked_in()
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

        Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => Carbon::now()->subHours(2),
            'clock_out' => null,
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    /** @test */
    public function it_displays_on_break_status_when_on_break()
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
            'date' => today(),
            'clock_in' => Carbon::now()->subHours(3),
            'clock_out' => null,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->subMinutes(30),
            'break_end' => null,
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    /** @test */
    public function it_displays_finished_status_when_clocked_out()
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

        Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => Carbon::now()->subHour(),
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('退勤済');
    }
}
