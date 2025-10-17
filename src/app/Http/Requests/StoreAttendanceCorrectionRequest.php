<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'corrected_clock_in' => ['required', 'date_format:H:i'],
            'corrected_clock_out' => ['required', 'date_format:H:i'],
            'breaks.*.start' => ['nullable', 'date_format:H:i'],
            'breaks.*.end' => ['nullable', 'date_format:H:i'],
            'corrected_reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'corrected_clock_in.required' => '出勤時間を入力してください',
            'corrected_clock_out.required' => '退勤時間を入力してください',
            'corrected_reason.required' => '備考を記入してください',
            'corrected_reason.max' => '備考は255文字以内で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $clockIn  = $this->input('corrected_clock_in');
            $clockOut = $this->input('corrected_clock_out');

            $clockInTs  = $clockIn ? strtotime($clockIn) : strtotime('00:00');
            $clockOutTs = $clockOut ? strtotime($clockOut) : strtotime('23:59');

            if ($clockIn && $clockOut && $clockIn > $clockOut) {
                $validator->errors()->add('corrected_clock_in', '出勤時間もしくは退勤時間が不適切な値です');
            }

            $breaks = $this->input('breaks', []);

            $hasExistingBreaks = collect($breaks)->contains(fn($b) => !empty($b['id']));
            $hasAttendance = !empty($clockIn) || !empty($clockOut);

            foreach ($breaks as $index => $break) {
                $breakId = $break['id'] ?? null;
                $breakStart = isset($break['start']) && $break['start'] !== '' ? strtotime($break['start']) : null;
                $breakEnd = isset($break['end']) && $break['end'] !== '' ? strtotime($break['end']) : null;

                $isExistingBreak = !empty($breakId);
                $isEmptyBreak = empty($breakStart) && empty($breakEnd);

                if (!$hasAttendance) {
                    if (!$breakStart) {
                        $validator->errors()->add("breaks.$index.start", '休憩開始時刻を入力してください');
                        }
                    if (!$breakEnd) {
                        $validator->errors()->add("breaks.$index.end", '休憩終了時刻を入力してください');
                    }
                    continue;
                }

                if (!$isExistingBreak && !$isEmptyBreak) {
                    if (!$breakStart) {
                        $validator->errors()->add("breaks.$index.start", '休憩開始時刻を入力してください');
                    }
                    if (!$breakEnd) {
                        $validator->errors()->add("breaks.$index.end", '休憩終了時刻を入力してください');
                    }
                }

                if ($breakStart && $breakEnd) {
                    if ($breakStart < $clockInTs || $breakStart > $clockOutTs) {
                        $validator->errors()->add("breaks.$index.start", '休憩時間が不適切な値です');
                    }
                    if ($breakEnd > $clockOutTs) {
                        $validator->errors()->add("breaks.$index.end", '休憩時間もしくは退勤時間が不適切な値です');
                    }
                }
            }
        });
    }
}
