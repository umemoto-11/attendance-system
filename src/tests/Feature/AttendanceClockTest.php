<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceClockTest extends TestCase
{
    use RefreshDatabase;

    // 日時取得機能
    /** @test */
    public function page_displays_current_date_and_time()
    {
        Carbon::setTestNow(Carbon::create(2025, 10, 15, 10, 30));
        Carbon::setLocale('ja');

        $user = User::factory()->create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $response = $this->get('/attendance');

        $expectedDate = Carbon::now()->isoFormat('YYYY年MM月DD日(ddd)');
        $expectedTime = Carbon::now()->format('H:i');

        $response->assertSee($expectedDate);
        $response->assertSee($expectedTime);
    }

    // 出勤機能
    /** @test */
    public function user_can_clock_in()
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

        $fixedNow = Carbon::create(2025, 10, 15, 9, 0);
        Carbon::setTestNow($fixedNow);

        $this->post('/attendance', [
            'action' => 'clock_in',
        ])->assertStatus(302);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $fixedNow->toDateString())
            ->first();

        $this->assertNotNull($attendance, 'Attendance record was not created.');
        $this->assertEquals($fixedNow->format('H:i'), $attendance->clock_in_formatted);
    }

    /** @test */
    public function cannot_clock_in_twice_in_one_day()
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

        $this->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $response = $this->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $response->assertDontSee('input type="hidden" name="action" value="clock_in"', false);

        $this->assertEquals(1, Attendance::where('user_id', $user->id)
            ->whereDate('clock_in', today())
            ->count());
    }

    /** @test */
    public function clock_in_time_is_visible_on_attendance_list()
    {
        Carbon::setTestNow(now());
        $user = User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $this->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', today())
            ->first();

        $this->assertNotNull($attendance);

        $clockInTime = Carbon::parse($attendance->clock_in)->format('H:i');

        $response = $this->get('/attendance/list');
        $response->assertSee($clockInTime);
    }

    // 休憩機能
    /** @test */
    public function user_can_start_break()
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
            'clock_in' => Carbon::now()->subHours(2),
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('休憩入');

        $this->post('/attendance', [
            'action' => 'break_start',
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('休憩中');
    }

    /** @test */
    public function user_can_take_multiple_breaks_in_one_day()
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
            'clock_in' => Carbon::now()->subHours(5),
        ]);

        $this->post('/attendance', [
            'action' => 'break_start',
        ]);
        $this->post('/attendance', [
            'action' => 'break_end',
        ]);

        $this->post('/attendance', [
            'action' => 'break_start',
        ]);
        $this->post('/attendance', [
            'action' => 'break_end',
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('休憩入');

        $this->assertEquals(2, BreakTime::where('attendance_id', $attendance->id)->count());
    }

    /** @test */
    public function user_can_end_break()
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
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->subMinutes(30),
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('休憩戻');

        $this->post('/attendance', [
            'action' => 'break_end',
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('出勤中');
    }

    /** @test */
    public function user_can_end_break_multiple_times_in_one_day()
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
            'clock_in' => Carbon::now()->subHours(8),
        ]);

        $this->post('/attendance', [
            'action' => 'break_start',
        ]);
        $this->post('/attendance', [
            'action' => 'break_end',
        ]);

        $response = $this->get('/attendance');

        $response->assertSee('出勤中');

        $response->assertSee('休憩入');

        $this->assertNotNull(BreakTime::where('attendance_id', $attendance->id)->first()->break_end);
    }

    /** @test */
    public function break_times_are_recorded_on_attendance_list()
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
            'clock_in' => Carbon::now()->subHours(6),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->subHours(3)->toDateTimeString(),
            'break_end'   => Carbon::now()->subHours(2.5)->toDateTimeString(),
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => Carbon::now()->subHours(2)->toDateTimeString(),
            'break_end'   => Carbon::now()->subHours(1.5)->toDateTimeString(),
        ]);

        $breakTimes = BreakTime::where('attendance_id', $attendance->id)->get();
        $totalBreakMinutes = $breakTimes->reduce(function ($carry, $break) {
            return $carry + Carbon::parse($break->break_end)->diffInMinutes(Carbon::parse($break->break_start));
        }, 0);

        $hours   = floor($totalBreakMinutes / 60);
        $minutes = $totalBreakMinutes % 60;
        $breakTimeTotal = sprintf('%02d:%02d', $hours, $minutes);


        $response = $this->get('/attendance/list?month=' . today()->format('Y-m'));

        $response->assertSeeText($breakTimeTotal);
    }

    // 退勤機能
    /** @test */
    public function user_can_clock_out_successfully()
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
            'clock_in' => Carbon::now()->subHours(8),
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('退勤');

        $this->post('/attendance', [
            'action' => 'clock_out',
        ]);

        $response = $this->get('/attendance');
        $response->assertSee('退勤済');

        $this->assertNotNull($attendance->fresh()->clock_out);
    }

    /** @test */
    public function clock_out_time_is_displayed_on_attendance_list()
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
            'clock_in' => Carbon::now()->subHours(8),
            'clock_out' => now(),
        ]);

        $clockOutTime = Carbon::parse($attendance->clock_out)->format('H:i');

        $response = $this->get('/attendance/list');
        $response->assertSee($clockOutTime);
    }
}
