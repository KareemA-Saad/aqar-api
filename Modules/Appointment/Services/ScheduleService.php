<?php

declare(strict_types=1);

namespace Modules\Appointment\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Appointment\Entities\AppointmentDay;
use Modules\Appointment\Entities\AppointmentDayType;
use Modules\Appointment\Entities\AppointmentSchedule;

/**
 * Service class for managing appointment schedules.
 *
 * Handles schedule CRUD, day management, and time slot operations.
 */
final class ScheduleService
{
    /**
     * Get all appointment days with their schedules.
     *
     * @return Collection
     */
    public function getAllDays(): Collection
    {
        return AppointmentDay::with(['schedules', 'schedules.type'])
            ->orderByRaw("FIELD(key, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
            ->get();
    }

    /**
     * Get all active appointment days.
     *
     * @return Collection
     */
    public function getActiveDays(): Collection
    {
        return AppointmentDay::with(['schedules' => function ($query) {
            $query->where('status', true)->with('type');
        }])
            ->where('status', true)
            ->orderByRaw("FIELD(key, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
            ->get();
    }

    /**
     * Get day by key (e.g., 'Monday', 'Tuesday').
     *
     * @param string $key
     * @return AppointmentDay|null
     */
    public function getDayByKey(string $key): ?AppointmentDay
    {
        return AppointmentDay::with(['schedules' => function ($query) {
            $query->where('status', true)->with('type');
        }])
            ->where('key', $key)
            ->first();
    }

    /**
     * Get all day types (Morning, Afternoon, Evening, etc.).
     *
     * @return Collection
     */
    public function getAllDayTypes(): Collection
    {
        return AppointmentDayType::all();
    }

    /**
     * Get active day types.
     *
     * @return Collection
     */
    public function getActiveDayTypes(): Collection
    {
        return AppointmentDayType::where('status', true)->get();
    }

    /**
     * Get paginated schedules with filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getSchedules(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AppointmentSchedule::with(['day', 'type']);

        // Filter by day
        if (!empty($filters['day_id'])) {
            $query->where('day_id', $filters['day_id']);
        }

        // Filter by day type
        if (!empty($filters['day_type'])) {
            $query->where('day_type', $filters['day_type']);
        }

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', (bool) $filters['status']);
        }

        // Filter by allow_multiple
        if (isset($filters['allow_multiple'])) {
            $query->where('allow_multiple', (bool) $filters['allow_multiple']);
        }

        return $query->orderBy('day_id')->orderBy('time')->paginate($perPage);
    }

    /**
     * Get schedules for a specific day.
     *
     * @param int $dayId
     * @param bool $activeOnly
     * @return Collection
     */
    public function getSchedulesForDay(int $dayId, bool $activeOnly = true): Collection
    {
        $query = AppointmentSchedule::with('type')
            ->where('day_id', $dayId);

        if ($activeOnly) {
            $query->where('status', true);
        }

        return $query->orderBy('time')->get();
    }

    /**
     * Get schedules grouped by day type.
     *
     * @param int $dayId
     * @return Collection
     */
    public function getSchedulesGroupedByType(int $dayId): Collection
    {
        return AppointmentSchedule::with('type')
            ->where('day_id', $dayId)
            ->where('status', true)
            ->get()
            ->groupBy('day_type');
    }

    /**
     * Create a new day.
     *
     * @param array<string, mixed> $data
     * @return AppointmentDay
     * @throws \Exception
     */
    public function createDay(array $data): AppointmentDay
    {
        // Check max 7 days limit
        $dayCount = AppointmentDay::count();
        if ($dayCount >= 7) {
            throw new \Exception('Maximum of 7 days allowed');
        }

        // Check if day key already exists
        if (AppointmentDay::where('key', $data['key'])->exists()) {
            throw new \Exception('Day already exists: ' . $data['key']);
        }

        return AppointmentDay::create([
            'day' => $data['day'],
            'key' => $data['key'],
            'status' => $data['status'] ?? true,
        ]);
    }

    /**
     * Update a day.
     *
     * @param AppointmentDay $day
     * @param array<string, mixed> $data
     * @return AppointmentDay
     */
    public function updateDay(AppointmentDay $day, array $data): AppointmentDay
    {
        $day->update([
            'day' => $data['day'] ?? $day->day,
            'status' => $data['status'] ?? $day->status,
        ]);

        return $day->fresh();
    }

    /**
     * Delete a day and its schedules.
     *
     * @param AppointmentDay $day
     * @return bool
     */
    public function deleteDay(AppointmentDay $day): bool
    {
        return DB::transaction(function () use ($day) {
            // Delete all schedules for this day
            $day->schedules()->delete();
            return $day->delete();
        });
    }

    /**
     * Toggle day status.
     *
     * @param AppointmentDay $day
     * @return AppointmentDay
     */
    public function toggleDayStatus(AppointmentDay $day): AppointmentDay
    {
        $day->update(['status' => !$day->status]);
        return $day->fresh();
    }

    /**
     * Create a new schedule (time slot).
     *
     * @param array<string, mixed> $data
     * @return AppointmentSchedule
     */
    public function createSchedule(array $data): AppointmentSchedule
    {
        return AppointmentSchedule::create([
            'day_id' => $data['day_id'],
            'time' => $data['time'], // Format: "09:00 - 10:00"
            'day_type' => $data['day_type'] ?? null,
            'allow_multiple' => $data['allow_multiple'] ?? false,
            'status' => $data['status'] ?? true,
        ]);
    }

    /**
     * Update a schedule.
     *
     * @param AppointmentSchedule $schedule
     * @param array<string, mixed> $data
     * @return AppointmentSchedule
     */
    public function updateSchedule(AppointmentSchedule $schedule, array $data): AppointmentSchedule
    {
        $schedule->update([
            'day_id' => $data['day_id'] ?? $schedule->day_id,
            'time' => $data['time'] ?? $schedule->time,
            'day_type' => $data['day_type'] ?? $schedule->day_type,
            'allow_multiple' => $data['allow_multiple'] ?? $schedule->allow_multiple,
            'status' => $data['status'] ?? $schedule->status,
        ]);

        return $schedule->fresh(['day', 'type']);
    }

    /**
     * Delete a schedule.
     *
     * @param AppointmentSchedule $schedule
     * @return bool
     */
    public function deleteSchedule(AppointmentSchedule $schedule): bool
    {
        return $schedule->delete();
    }

    /**
     * Toggle schedule status.
     *
     * @param AppointmentSchedule $schedule
     * @return AppointmentSchedule
     */
    public function toggleScheduleStatus(AppointmentSchedule $schedule): AppointmentSchedule
    {
        $schedule->update(['status' => !$schedule->status]);
        return $schedule->fresh();
    }

    /**
     * Block a time slot (set status to false).
     *
     * @param int $scheduleId
     * @return AppointmentSchedule
     */
    public function blockSchedule(int $scheduleId): AppointmentSchedule
    {
        $schedule = AppointmentSchedule::findOrFail($scheduleId);
        $schedule->update(['status' => false]);
        return $schedule->fresh(['day', 'type']);
    }

    /**
     * Unblock a time slot (set status to true).
     *
     * @param int $scheduleId
     * @return AppointmentSchedule
     */
    public function unblockSchedule(int $scheduleId): AppointmentSchedule
    {
        $schedule = AppointmentSchedule::findOrFail($scheduleId);
        $schedule->update(['status' => true]);
        return $schedule->fresh(['day', 'type']);
    }

    /**
     * Create a day type.
     *
     * @param array<string, mixed> $data
     * @return AppointmentDayType
     */
    public function createDayType(array $data): AppointmentDayType
    {
        return AppointmentDayType::create([
            'title' => $data['title'],
            'status' => $data['status'] ?? true,
        ]);
    }

    /**
     * Update a day type.
     *
     * @param AppointmentDayType $dayType
     * @param array<string, mixed> $data
     * @return AppointmentDayType
     */
    public function updateDayType(AppointmentDayType $dayType, array $data): AppointmentDayType
    {
        $dayType->update([
            'title' => $data['title'] ?? $dayType->title,
            'status' => $data['status'] ?? $dayType->status,
        ]);

        return $dayType->fresh();
    }

    /**
     * Delete a day type.
     *
     * @param AppointmentDayType $dayType
     * @return bool
     */
    public function deleteDayType(AppointmentDayType $dayType): bool
    {
        // Nullify references in schedules
        AppointmentSchedule::where('day_type', $dayType->id)->update(['day_type' => null]);
        return $dayType->delete();
    }

    /**
     * Bulk create schedules for a day.
     *
     * @param int $dayId
     * @param array<array<string, mixed>> $schedules
     * @return Collection
     */
    public function bulkCreateSchedules(int $dayId, array $schedules): Collection
    {
        $createdSchedules = collect();

        foreach ($schedules as $scheduleData) {
            $scheduleData['day_id'] = $dayId;
            $createdSchedules->push($this->createSchedule($scheduleData));
        }

        return $createdSchedules;
    }

    /**
     * Replace all schedules for a day.
     *
     * @param int $dayId
     * @param array<array<string, mixed>> $schedules
     * @return Collection
     */
    public function replaceSchedulesForDay(int $dayId, array $schedules): Collection
    {
        return DB::transaction(function () use ($dayId, $schedules) {
            // Delete existing schedules
            AppointmentSchedule::where('day_id', $dayId)->delete();

            // Create new schedules
            return $this->bulkCreateSchedules($dayId, $schedules);
        });
    }

    /**
     * Parse time string to start and end times.
     *
     * @param string $timeString Format: "09:00 - 10:00"
     * @return array{start: string, end: string}
     */
    public function parseTimeString(string $timeString): array
    {
        $parts = explode(' - ', $timeString);
        return [
            'start' => trim($parts[0] ?? ''),
            'end' => trim($parts[1] ?? ''),
        ];
    }

    /**
     * Format start and end times to time string.
     *
     * @param string $startTime
     * @param string $endTime
     * @return string
     */
    public function formatTimeString(string $startTime, string $endTime): string
    {
        return "{$startTime} - {$endTime}";
    }
}
