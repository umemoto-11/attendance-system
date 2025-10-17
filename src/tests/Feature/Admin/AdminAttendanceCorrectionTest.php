<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Support\Carbon;

class AdminAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function selected_attendance_is_displayed_on_detail_page()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $attendance = Attendance::create([
            'user_id' => $admin->id,
            'date' => '2025-10-15',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->get("/admin/attendances/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('2025年');
        $response->assertSee('10月15日');
    }

    /** @test */
    public function work_start_time_must_be_before_work_end_time()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $attendance = Attendance::create([
            'user_id' => $admin->id,
            'date' => '2025-10-15',
        ]);

        $response = $this->post("/admin/attendances/{$attendance->id}", [
            'date' => '2025-10-15',
            'corrected_clock_in' => '18:00',
            'corrected_clock_out' => '09:00', 'breaks' => [
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
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $attendance = Attendance::create([
            'user_id' => $admin->id,
            'date' => '2025-10-15',
        ]);

        $existingBreak = $attendance->breakTimes()->create([
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]);

        $response = $this->post(route('admin.attendances.store', $attendance->id), [
            'corrected_clock_in' => '09:00',
            'corrected_clock_out' => '18:00',
            'breaks' => [
                ['id' => $existingBreak->id, 'start' => '19:00', 'end' => '20:00'],
            ],
            'corrected_reason' => 'Test reason',
        ]);

        $response->assertSessionHasErrors('breaks.0.start');

        $errors = session('errors');
        $this->assertEquals('休憩時間が不適切な値です', $errors->first('breaks.0.start'));
    }

    /** @test */
    public function break_end_must_be_before_work_end_time()
    {
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $attendance = Attendance::create([
            'user_id' => $admin->id,
            'date' => '2025-10-15',
        ]);

        $existingBreak = $attendance->breakTimes()->create([
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]);

        $response = $this->post(route('admin.attendances.store', $attendance->id), [
            'corrected_clock_in' => '09:00',
            'corrected_clock_out' => '18:00',
            'breaks' => [
                ['id' => $existingBreak->id, 'start' => '12:00', 'end' => '19:00'],
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
        $admin = User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $attendance = Attendance::create([
            'user_id' => $admin->id,
            'date' => '2025-10-15',
        ]);

        $response = $this->post("/admin/attendances/{$attendance->id}", [
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
}
