<?php

declare(strict_types=1);

namespace Modules\Appointment\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Appointment\Entities\AppointmentDay;
use Modules\Appointment\Entities\AppointmentPaymentLog;
use Modules\Appointment\Entities\AppointmentSchedule;

/**
 * Service class for checking slot availability.
 *
 * Handles slot availability checks, timezone conversion, and booking conflict detection.
 */
final class SlotAvailabilityService
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
    ) {}

    /**
     * Get available slots for a specific date.
     *
     * @param string $date Date string (Y-m-d)
     * @param int|null $appointmentId Optional appointment ID for specific availability
     * @return array<string, mixed>
     */
    public function getAvailableSlotsForDate(string $date, ?int $appointmentId = null): array
    {
        // Parse date and get day name
        $carbonDate = $this->parseDate($date);
        $dayName = $carbonDate->format('l'); // e.g., 'Monday'

        // Get the day configuration
        $day = $this->scheduleService->getDayByKey($dayName);

        if (!$day || !$day->status) {
            return [
                'date' => $date,
                'day' => $dayName,
                'available' => false,
                'message' => 'No schedules available for this day',
                'slots' => [],
            ];
        }

        // Get schedules for this day
        $schedules = $day->schedules->where('status', true);

        if ($schedules->isEmpty()) {
            return [
                'date' => $date,
                'day' => $dayName,
                'available' => false,
                'message' => 'No time slots configured for this day',
                'slots' => [],
            ];
        }

        // Get existing bookings for this date
        $existingBookings = $this->getExistingBookingsForDate($date, $appointmentId);

        // Build available slots
        $slots = $this->buildAvailableSlots($schedules, $existingBookings, $date);

        // Group slots by day type
        $groupedSlots = $this->groupSlotsByType($slots);

        return [
            'date' => $date,
            'day' => $dayName,
            'day_translated' => $day->day,
            'available' => !empty($slots),
            'total_slots' => count($slots),
            'available_slots' => count(array_filter($slots, fn($s) => $s['available'])),
            'slots' => $slots,
            'slots_by_type' => $groupedSlots,
        ];
    }

    /**
     * Check if a specific slot is available.
     *
     * @param string $date
     * @param string $time Time string (e.g., "09:00 - 10:00")
     * @param int|null $appointmentId
     * @return array{available: bool, message: string, schedule: AppointmentSchedule|null}
     */
    public function checkSlotAvailability(string $date, string $time, ?int $appointmentId = null): array
    {
        $carbonDate = $this->parseDate($date);
        $dayName = $carbonDate->format('l');

        // Get the day
        $day = $this->scheduleService->getDayByKey($dayName);
        if (!$day || !$day->status) {
            return [
                'available' => false,
                'message' => 'This day is not available for appointments',
                'schedule' => null,
            ];
        }

        // Find the schedule
        $schedule = AppointmentSchedule::where('day_id', $day->id)
            ->where('time', $time)
            ->where('status', true)
            ->first();

        if (!$schedule) {
            return [
                'available' => false,
                'message' => 'This time slot does not exist or is not active',
                'schedule' => null,
            ];
        }

        // Check if slot allows multiple bookings
        if ($schedule->allow_multiple) {
            return [
                'available' => true,
                'message' => 'Slot available (allows multiple bookings)',
                'schedule' => $schedule,
            ];
        }

        // Check for existing bookings
        $existingBooking = $this->findExistingBooking($date, $time, $appointmentId);

        if ($existingBooking) {
            return [
                'available' => false,
                'message' => 'This time slot is already booked',
                'schedule' => $schedule,
            ];
        }

        return [
            'available' => true,
            'message' => 'Slot available',
            'schedule' => $schedule,
        ];
    }

    /**
     * Get availability for a date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $appointmentId
     * @return array<string, array>
     */
    public function getAvailabilityForDateRange(string $startDate, string $endDate, ?int $appointmentId = null): array
    {
        $start = $this->parseDate($startDate);
        $end = $this->parseDate($endDate);
        $availability = [];

        while ($start->lte($end)) {
            $dateString = $start->format('Y-m-d');
            $dayAvailability = $this->getAvailableSlotsForDate($dateString, $appointmentId);

            $availability[$dateString] = [
                'date' => $dateString,
                'day' => $dayAvailability['day'],
                'available' => $dayAvailability['available'],
                'total_slots' => $dayAvailability['total_slots'] ?? 0,
                'available_slots' => $dayAvailability['available_slots'] ?? 0,
            ];

            $start->addDay();
        }

        return $availability;
    }

    /**
     * Get next available slot.
     *
     * @param int|null $appointmentId
     * @param int $daysAhead Number of days to look ahead
     * @return array|null
     */
    public function getNextAvailableSlot(?int $appointmentId = null, int $daysAhead = 30): ?array
    {
        $date = Carbon::now($this->getTenantTimezone());

        for ($i = 0; $i < $daysAhead; $i++) {
            $dateString = $date->format('Y-m-d');
            $availability = $this->getAvailableSlotsForDate($dateString, $appointmentId);

            if ($availability['available'] && !empty($availability['slots'])) {
                foreach ($availability['slots'] as $slot) {
                    if ($slot['available']) {
                        return [
                            'date' => $dateString,
                            'day' => $availability['day'],
                            'slot' => $slot,
                        ];
                    }
                }
            }

            $date->addDay();
        }

        return null;
    }

    /**
     * Get existing bookings for a date.
     *
     * @param string $date
     * @param int|null $appointmentId
     * @return Collection
     */
    private function getExistingBookingsForDate(string $date, ?int $appointmentId = null): Collection
    {
        $query = AppointmentPaymentLog::where('appointment_date', $date)
            ->whereIn('status', ['pending', 'complete', 'confirmed']);

        if ($appointmentId) {
            $query->where('appointment_id', $appointmentId);
        }

        return $query->get();
    }

    /**
     * Find an existing booking for a specific date and time.
     *
     * @param string $date
     * @param string $time
     * @param int|null $appointmentId
     * @return AppointmentPaymentLog|null
     */
    private function findExistingBooking(string $date, string $time, ?int $appointmentId = null): ?AppointmentPaymentLog
    {
        $query = AppointmentPaymentLog::where('appointment_date', $date)
            ->where('appointment_time', $time)
            ->whereIn('status', ['pending', 'complete', 'confirmed']);

        if ($appointmentId) {
            $query->where('appointment_id', $appointmentId);
        }

        return $query->first();
    }

    /**
     * Build available slots from schedules.
     *
     * @param Collection $schedules
     * @param Collection $existingBookings
     * @param string $date
     * @return array
     */
    private function buildAvailableSlots(Collection $schedules, Collection $existingBookings, string $date): array
    {
        $slots = [];
        $bookedTimes = $existingBookings->pluck('appointment_time')->toArray();

        foreach ($schedules as $schedule) {
            $isBooked = in_array($schedule->time, $bookedTimes);
            $isAvailable = !$isBooked || $schedule->allow_multiple;

            // Parse time for better display
            $timeParts = $this->scheduleService->parseTimeString($schedule->time);

            $slots[] = [
                'schedule_id' => $schedule->id,
                'time' => $schedule->time,
                'start_time' => $timeParts['start'],
                'end_time' => $timeParts['end'],
                'day_type_id' => $schedule->day_type,
                'day_type' => $schedule->type?->title ?? null,
                'allow_multiple' => (bool) $schedule->allow_multiple,
                'available' => $isAvailable,
                'booked' => $isBooked,
            ];
        }

        // Sort by start time
        usort($slots, function ($a, $b) {
            return strcmp($a['start_time'], $b['start_time']);
        });

        return $slots;
    }

    /**
     * Group slots by day type.
     *
     * @param array $slots
     * @return array
     */
    private function groupSlotsByType(array $slots): array
    {
        $grouped = [];

        foreach ($slots as $slot) {
            $typeKey = $slot['day_type_id'] ?? 'other';
            $typeName = $slot['day_type'] ?? 'Other';

            if (!isset($grouped[$typeKey])) {
                $grouped[$typeKey] = [
                    'type_id' => $typeKey,
                    'type_name' => $typeName,
                    'slots' => [],
                ];
            }

            $grouped[$typeKey]['slots'][] = $slot;
        }

        return array_values($grouped);
    }

    /**
     * Parse date string to Carbon instance with tenant timezone.
     *
     * @param string $date
     * @return Carbon
     */
    private function parseDate(string $date): Carbon
    {
        return Carbon::parse($date, $this->getTenantTimezone());
    }

    /**
     * Get tenant timezone.
     *
     * Uses tenant settings if available, falls back to app timezone.
     *
     * @return string
     */
    private function getTenantTimezone(): string
    {
        // Try to get from tenant settings
        try {
            $tenantTimezone = tenant()?->settings?->timezone
                ?? get_static_option('timezone')
                ?? config('app.timezone', 'UTC');

            return $tenantTimezone;
        } catch (\Exception $e) {
            return config('app.timezone', 'UTC');
        }
    }

    /**
     * Check if a date is in the past.
     *
     * @param string $date
     * @return bool
     */
    public function isDateInPast(string $date): bool
    {
        $carbonDate = $this->parseDate($date);
        $now = Carbon::now($this->getTenantTimezone());

        return $carbonDate->lt($now->startOfDay());
    }

    /**
     * Check if a slot time has passed for today.
     *
     * @param string $date
     * @param string $time
     * @return bool
     */
    public function isSlotTimePassed(string $date, string $time): bool
    {
        $carbonDate = $this->parseDate($date);
        $now = Carbon::now($this->getTenantTimezone());

        // If not today, use date comparison
        if (!$carbonDate->isToday()) {
            return $carbonDate->lt($now->startOfDay());
        }

        // For today, check if slot start time has passed
        $timeParts = $this->scheduleService->parseTimeString($time);
        $slotStart = Carbon::parse($date . ' ' . $timeParts['start'], $this->getTenantTimezone());

        return $slotStart->lt($now);
    }

    /**
     * Get booking statistics for a date range.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int|null $appointmentId
     * @return array
     */
    public function getBookingStats(string $startDate, string $endDate, ?int $appointmentId = null): array
    {
        $query = AppointmentPaymentLog::whereBetween('appointment_date', [$startDate, $endDate]);

        if ($appointmentId) {
            $query->where('appointment_id', $appointmentId);
        }

        $bookings = $query->get();

        return [
            'total_bookings' => $bookings->count(),
            'pending' => $bookings->where('status', 'pending')->count(),
            'confirmed' => $bookings->where('status', 'confirmed')->count(),
            'completed' => $bookings->where('status', 'complete')->count(),
            'cancelled' => $bookings->where('status', 'cancelled')->count(),
            'revenue' => $bookings->whereIn('status', ['complete', 'confirmed'])->sum('total_amount'),
        ];
    }
}
