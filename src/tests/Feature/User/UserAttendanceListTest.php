<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Carbon;

class UserAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_see_all_their_attendance_records()
    {
        $user = User::create([
            'name' => '山田太郎',
            'email' => 'text@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $dates = [today(), today()->subDay(), today()->subDays(2)];
        foreach ($dates as $date) {
            Attendance::create([
                'user_id' => $user->id,
                'date' => $date,
                'clock_in' => $date->copy()->setTime(9, 0),
                'clock_out' => $date->copy()->setTime(18, 0),
            ]);
        }

        $response = $this->get('/attendance/list');

        foreach ($dates as $date) {
            $response->assertSee($date->isoFormat('MM/DD(ddd)'));
        }
    }

    /** @test */
    public function attendance_list_shows_current_month_by_default()
    {
        $user = User::create([
            'name' => '山田太郎',
            'email' => 'text@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $response = $this->get('/attendance/list');
        $currentMonth = now()->format('Y/m');
        $response->assertSee($currentMonth);
    }

    /** @test */
    public function user_can_view_previous_month_records()
    {
        $user = User::create([
            'name' => '山田太郎',
            'email' => 'text@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $previousMonth = now()->subMonth();

        Attendance::create([
            'user_id' => $user->id,
            'date' => $previousMonth->copy()->startOfMonth(),
            'clock_in' => $previousMonth->copy()->startOfMonth()->setTime(9, 0),
        ]);

        $response = $this->get('/attendance/list?month=' . $previousMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee($previousMonth->format('Y/m'));
    }

    /** @test */
    public function user_can_view_next_month_records()
    {
        $user = User::create([
            'name' => '山田太郎',
            'email' => 'text@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $nextMonth = now()->addMonth();

        Attendance::create([
            'user_id' => $user->id,
            'date' => $nextMonth->copy()->startOfMonth(),
            'clock_in' => $nextMonth->copy()->startOfMonth()->setTime(9, 0),
        ]);

        $response = $this->get('/attendance/list?month=' . $nextMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
    }

    /** @test */
    public function user_can_view_attendance_detail_from_list()
    {
        $user = User::create([
            'name' => '山田太郎',
            'email' => 'text@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => now()->setTime(9, 0),
            'clock_out' => now()->setTime(18, 0),
        ]);

        $response = $this->get('/attendance/list');

        $response->assertSee('/attendance/detail/' . $attendance->id);

        $detailResponse = $this->get('/attendance/detail/' . $attendance->id);
        $detailResponse->assertStatus(200);
        $year = $attendance->date->format('Y年');
        $monthDay = $attendance->date->format('n月j日');

        $detailResponse->assertSee($year);
        $detailResponse->assertSee($monthDay);
    }
}
