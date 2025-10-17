<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Carbon;

class UserAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function work_start_time_must_be_before_work_end_time()
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

        $response = $this->post("/attendance/detail/{$attendance->id}", [
            'date' => '2025-10-15',
            'corrected_clock_in' => '18:00',
            'corrected_clock_out' => '09:00',
            'breaks' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'corrected_reason' => 'Test reason',
        ]);

        $response->assertSessionHasErrors('corrected_clock_in');

        $errors = session('errors');
        $this->assertEquals('出勤時間もしくは退勤時間が不適切な値です', $errors->first('corrected_clock_in'));
    }

    /** @test */
    public function break_start_must_be_before_work_end_time()
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
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $existingBreak = $attendance->breakTimes()->create([
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]);

        $response = $this->post(route('attendance.correction', $attendance->id), [
            'corrected_clock_in' => '09:00',
            'corrected_clock_out' => '18:00',
            'breaks' => [
                [
                    'id' => $existingBreak->id,
                    'start' => '19:00',
                    'end' => '20:00',
                ],
            ],
            'corrected_reason' => 'Test reason',
        ]);

        $response->assertSessionHasErrors('breaks.0.start');

        $errors = session('errors');
        $this->assertEquals(
            '休憩時間が不適切な値です',
            $errors->first('breaks.0.start')
        );
    }

    /** @test */
    public function break_end_must_be_before_work_end_time()
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
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $existingBreak = $attendance->breakTimes()->create([
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]);

        $response = $this->post(route('attendance.correction', $attendance->id), [
            'corrected_clock_in' => '09:00',
            'corrected_clock_out' => '18:00',
            'breaks' => [
                [
                    'id' => $existingBreak->id,
                    'start' => '12:00',
                    'end' => '19:00',
                ],
            ],
            'corrected_reason' => 'Test reason',
        ]);

        $response->assertSessionHasErrors('breaks.0.end');

        $errors = session('errors');
        $this->assertEquals('休憩時間もしくは退勤時間が不適切な値です', $errors->first('breaks.0.end'));
    }

    /** @test */
    public function reason_is_required()
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

        $response = $this->post("/attendance/detail/{$attendance->id}", [
            'date' => '2025-10-15',
            'corrected_clock_in' => '09:00',
            'corrected_clock_out' => '18:00',
            'breaks' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'corrected_reason' => '',
        ]);

        $response->assertSessionHasErrors('corrected_reason');

        $errors = session('errors');
        $this->assertEquals('備考を記入してください', $errors->first('corrected_reason'));
    }

    /** @test */
    public function correction_request_is_created_and_displayed_in_admin_and_user_request_lists()
    {
        Carbon::setTestNow(Carbon::create(2025, 10, 16));

        $user = User::create([
            'name' => '山田太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user, 'web');

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $existingBreak = $attendance->breakTimes()->create([
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]);

        $response = $this->post(route('attendance.correction', $attendance->id), [
            'corrected_clock_in' => '09:30',
            'corrected_clock_out' => '18:30',
            'breaks' => [
                [
                    'id' => $existingBreak->id,
                    'start' => '12:00',
                    'end' => '13:00',
                ],
            ],
            'corrected_reason' => 'Test reason',
        ]);

        $response->assertRedirect(route('attendance.show', ['id' => $attendance->id]));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'corrected_clock_in' => '09:30:00',
            'corrected_clock_out' => '18:30:00',
            'status' => 'pending',
            'corrected_reason' => 'Test reason',
        ]);

        $response = $this->actingAs($user, 'web')->get(route('requests.index', ['tab' => 'pending']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('承認待ち', $html);
        $this->assertStringContainsString('山田太郎', $html);
        $this->assertStringContainsString('Test reason', $html);
        $this->assertStringContainsString('2025/10/16', $html);
        $this->assertStringContainsString('詳細', $html);

        $response = $this->actingAs($admin)->get(route('admin.requests.index', ['tab' => 'pending']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('承認待ち', $html);
        $this->assertStringContainsString('山田太郎', $html);
        $this->assertStringContainsString('Test reason', $html);
        $this->assertStringContainsString('2025/10/16', $html);
        $this->assertStringContainsString('詳細', $html);
    }

    /** @test */
    public function correction_request_is_created_and_shown_in_approval_and_list()
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

        $attendanceId = 1;

        $response = $this->post("/attendance/detail/{$attendanceId}", [
            'date' => '2025-10-15',
            'corrected_clock_in' => '09:00',
            'corrected_clock_out' => '18:00',
            'breaks' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'corrected_reason' => 'Test reason',
            'corrected_date' => Carbon::now(),
        ]);

        $attendance = Attendance::latest()->first();

        $response = $this->get(route('requests.index', ['tab' => 'pending']));

        $response->assertStatus(200);

        $response->assertSeeText('承認待ち');
        $response->assertSeeText($user->name);
        $response->assertSeeText(Carbon::parse($attendance->date)->format('Y/m/d'));
        $response->assertSeeText($attendance->corrected_reason);
    }

    /** @test */
    public function approved_correction_requests_are_shown_in_approved_tab()
    {
        $user = User::create([
            'name' => '山田太郎',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($user);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2025-10-15',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'corrected_clock_in' => '09:00',
            'corrected_clock_out' => '18:00',
            'breaks' => [['start' => '12:00', 'end' => '13:00']],
            'corrected_reason' => 'Test reason',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.corrections.approved', $attendance->id));

        $response = $this->actingAs($admin)
            ->get(route('admin.requests.index', ['tab' => 'approved']));

        $response->assertStatus(200);
        $response->assertSeeText('承認済み');
        $response->assertSeeText($user->name);
        $response->assertSeeText(Carbon::parse($attendance->date)->format('Y/m/d'));
        $response->assertSeeText($attendance->corrected_reason);
    }

    /** @test */
    public function clicking_request_detail_redirects_to_attendance_detail()
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
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $this->post("/attendance/detail/{$attendance->id}", [
            'date' => '2025-10-15',
            'corrected_clock_in' => '09:00',
            'corrected_clock_out' => '18:00',
            'breaks' => [
                ['start' => '12:00', 'end' => '13:00'],
            ],
            'corrected_reason' => 'Test reason',
            'corrected_date' => Carbon::now(),
        ]);

        $response = $this->get(route('attendance.show', $attendance->id));
        $response->assertStatus(200);
        $html = $response->getContent();
        $this->assertStringContainsString('value="09:00"', $html);
        $this->assertStringContainsString('value="18:00"', $html);
        $this->assertStringContainsString('value="12:00:00"', $html);
        $this->assertStringContainsString('value="13:00:00"', $html);
        $this->assertStringContainsString('Test reason', $html);
    }
}
