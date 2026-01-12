<?php

declare(strict_types=1);

namespace Modules\Appointment\Services;

use App\Helpers\SanitizeInput;
use App\Models\MetaInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Appointment\Entities\AdditionalAppointment;
use Modules\Appointment\Entities\Appointment;
use Modules\Appointment\Entities\AppointmentTax;

/**
 * Service class for managing appointments.
 *
 * Handles appointment CRUD operations, package limits, and related operations.
 */
final class AppointmentService
{
    /**
     * Get paginated list of appointments with optional filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAppointments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Appointment::query()
            ->with(['category', 'subcategory', 'metainfo', 'tax'])
            ->withCount('comments');

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->where('appointment_category_id', $filters['category_id']);
        }

        // Subcategory filter
        if (!empty($filters['subcategory_id'])) {
            $query->where('appointment_subcategory_id', $filters['subcategory_id']);
        }

        // Status filter
        if (isset($filters['status'])) {
            $query->where('status', (bool) $filters['status']);
        }

        // Popular filter
        if (isset($filters['is_popular'])) {
            $query->where('is_popular', (bool) $filters['is_popular']);
        }

        // Price range filter
        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }
        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['title', 'price', 'views', 'created_at', 'updated_at', 'status'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get published/active appointments for public display.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublishedAppointments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['status'] = true;
        return $this->getAppointments($filters, $perPage);
    }

    /**
     * Get a single appointment by ID with relations.
     *
     * @param int $id
     * @param array<string> $relations
     * @return Appointment|null
     */
    public function findById(int $id, array $relations = []): ?Appointment
    {
        $defaultRelations = ['category', 'subcategory', 'metainfo', 'tax', 'sub_appointments'];
        $relations = array_merge($defaultRelations, $relations);

        return Appointment::with($relations)->find($id);
    }

    /**
     * Get a single appointment by slug.
     *
     * @param string $slug
     * @param bool $incrementViews
     * @return Appointment|null
     */
    public function findBySlug(string $slug, bool $incrementViews = false): ?Appointment
    {
        $appointment = Appointment::with([
            'category',
            'subcategory',
            'metainfo',
            'tax',
            'sub_appointments',
        ])->where('slug', $slug)->first();

        if ($appointment && $incrementViews) {
            $appointment->increment('views');
        }

        return $appointment;
    }

    /**
     * Check if tenant can create more appointments based on package limits.
     *
     * @return array{allowed: bool, message: string, current: int, limit: int|null}
     */
    public function checkPackageLimit(): array
    {
        $currentPackage = tenant()->payment_log()->first()?->package ?? null;
        $currentCount = Appointment::count();
        $permissionLimit = $currentPackage?->appointment_permission_feature ?? null;

        if ($permissionLimit !== null && $currentCount >= $permissionLimit) {
            return [
                'allowed' => false,
                'message' => sprintf('You cannot create more than %d appointments in this package', $permissionLimit),
                'current' => $currentCount,
                'limit' => $permissionLimit,
            ];
        }

        return [
            'allowed' => true,
            'message' => 'Appointment creation allowed',
            'current' => $currentCount,
            'limit' => $permissionLimit,
        ];
    }

    /**
     * Create a new appointment.
     *
     * @param array<string, mixed> $data
     * @return Appointment
     * @throws \Exception
     */
    public function createAppointment(array $data): Appointment
    {
        // Check package limit
        $limitCheck = $this->checkPackageLimit();
        if (!$limitCheck['allowed']) {
            throw new \Exception($limitCheck['message']);
        }

        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            $slug = $data['slug'] ?? Str::slug($data['title']);
            $slug = $this->generateUniqueSlug($slug);

            // Create appointment
            $appointment = Appointment::create([
                'appointment_category_id' => $data['category_id'] ?? null,
                'appointment_subcategory_id' => $data['subcategory_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'] ?? 0,
                'slug' => $slug,
                'status' => $data['status'] ?? true,
                'is_popular' => $data['is_popular'] ?? false,
                'image' => $data['image'] ?? null,
                'person' => $data['person'] ?? 1,
                'sub_appointment_status' => $data['sub_appointment_status'] ?? false,
                'tax_status' => $data['tax_status'] ?? false,
                'key' => $data['key'] ?? null,
            ]);

            // Create meta info if provided
            if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
                $this->createMetaInfo($appointment, $data);
            }

            // Create tax info if tax_status is enabled
            if (!empty($data['tax_status']) && !empty($data['tax_amount'])) {
                $this->createTaxInfo($appointment, $data);
            }

            // Attach sub-appointments if provided
            if (!empty($data['sub_appointment_ids'])) {
                $this->syncSubAppointments($appointment, $data['sub_appointment_ids']);
            }

            return $appointment->fresh(['category', 'subcategory', 'metainfo', 'tax', 'sub_appointments']);
        });
    }

    /**
     * Update an existing appointment.
     *
     * @param Appointment $appointment
     * @param array<string, mixed> $data
     * @return Appointment
     */
    public function updateAppointment(Appointment $appointment, array $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            // Update slug if title changed
            if (isset($data['title']) && $data['title'] !== $appointment->title) {
                $slug = $data['slug'] ?? Str::slug($data['title']);
                $data['slug'] = $this->generateUniqueSlug($slug, $appointment->id);
            }

            // Map field names
            $updateData = [
                'appointment_category_id' => $data['category_id'] ?? $appointment->appointment_category_id,
                'appointment_subcategory_id' => $data['subcategory_id'] ?? $appointment->appointment_subcategory_id,
                'title' => $data['title'] ?? $appointment->title,
                'description' => $data['description'] ?? $appointment->description,
                'price' => $data['price'] ?? $appointment->price,
                'slug' => $data['slug'] ?? $appointment->slug,
                'status' => $data['status'] ?? $appointment->status,
                'is_popular' => $data['is_popular'] ?? $appointment->is_popular,
                'image' => $data['image'] ?? $appointment->image,
                'person' => $data['person'] ?? $appointment->person,
                'sub_appointment_status' => $data['sub_appointment_status'] ?? $appointment->sub_appointment_status,
                'tax_status' => $data['tax_status'] ?? $appointment->tax_status,
            ];

            $appointment->update($updateData);

            // Update meta info
            if (isset($data['meta_title']) || isset($data['meta_description'])) {
                $this->updateMetaInfo($appointment, $data);
            }

            // Update tax info
            if (isset($data['tax_status'])) {
                $this->updateTaxInfo($appointment, $data);
            }

            // Sync sub-appointments if provided
            if (isset($data['sub_appointment_ids'])) {
                $this->syncSubAppointments($appointment, $data['sub_appointment_ids']);
            }

            return $appointment->fresh(['category', 'subcategory', 'metainfo', 'tax', 'sub_appointments']);
        });
    }

    /**
     * Delete an appointment.
     *
     * @param Appointment $appointment
     * @return bool
     */
    public function deleteAppointment(Appointment $appointment): bool
    {
        return DB::transaction(function () use ($appointment) {
            // Delete meta info
            if ($appointment->metainfo) {
                $appointment->metainfo->delete();
            }

            // Delete tax info
            if ($appointment->tax) {
                $appointment->tax->delete();
            }

            // Delete additional appointments (sub-appointment links)
            $appointment->additional_appointments()->delete();

            return $appointment->delete();
        });
    }

    /**
     * Clone an existing appointment.
     *
     * @param Appointment $appointment
     * @return Appointment
     * @throws \Exception
     */
    public function cloneAppointment(Appointment $appointment): Appointment
    {
        // Check package limit
        $limitCheck = $this->checkPackageLimit();
        if (!$limitCheck['allowed']) {
            throw new \Exception($limitCheck['message']);
        }

        return DB::transaction(function () use ($appointment) {
            $newSlug = $this->generateUniqueSlug($appointment->slug . '-copy');

            $newAppointment = $appointment->replicate();
            $newAppointment->slug = $newSlug;
            $newAppointment->views = 0;
            $newAppointment->save();

            // Clone meta info
            if ($appointment->metainfo) {
                $newAppointment->metainfo()->create($appointment->metainfo->toArray());
            }

            // Clone tax info
            if ($appointment->tax) {
                $newAppointment->tax()->create([
                    'tax_type' => $appointment->tax->tax_type,
                    'tax_amount' => $appointment->tax->tax_amount,
                ]);
            }

            // Clone sub-appointment links
            $subAppointmentIds = $appointment->additional_appointments->pluck('sub_appointment_id')->toArray();
            if (!empty($subAppointmentIds)) {
                $this->syncSubAppointments($newAppointment, $subAppointmentIds);
            }

            return $newAppointment->fresh(['category', 'subcategory', 'metainfo', 'tax', 'sub_appointments']);
        });
    }

    /**
     * Toggle appointment status.
     *
     * @param Appointment $appointment
     * @return Appointment
     */
    public function toggleStatus(Appointment $appointment): Appointment
    {
        $appointment->update(['status' => !$appointment->status]);
        return $appointment->fresh();
    }

    /**
     * Bulk delete appointments.
     *
     * @param array<int> $ids
     * @return int Number of deleted appointments
     */
    public function bulkDelete(array $ids): int
    {
        $appointments = Appointment::whereIn('id', $ids)->get();
        $count = 0;

        foreach ($appointments as $appointment) {
            if ($this->deleteAppointment($appointment)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get appointments by category.
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getPublishedAppointments(['category_id' => $categoryId], $perPage);
    }

    /**
     * Get popular appointments.
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopular(int $limit = 10): Collection
    {
        return Appointment::with(['category', 'metainfo'])
            ->where('status', true)
            ->where('is_popular', true)
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get related appointments.
     *
     * @param Appointment $appointment
     * @param int $limit
     * @return Collection
     */
    public function getRelated(Appointment $appointment, int $limit = 4): Collection
    {
        return Appointment::with(['category', 'metainfo'])
            ->where('status', true)
            ->where('id', '!=', $appointment->id)
            ->where(function ($query) use ($appointment) {
                $query->where('appointment_category_id', $appointment->appointment_category_id)
                    ->orWhere('appointment_subcategory_id', $appointment->appointment_subcategory_id);
            })
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate a unique slug.
     *
     * @param string $slug
     * @param int|null $excludeId
     * @return string
     */
    private function generateUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        $query = Appointment::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $query = Appointment::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            $counter++;
        }

        return $slug;
    }

    /**
     * Create meta info for an appointment.
     *
     * @param Appointment $appointment
     * @param array<string, mixed> $data
     * @return void
     */
    private function createMetaInfo(Appointment $appointment, array $data): void
    {
        $appointment->metainfo()->create([
            'title' => $data['meta_title'] ?? null,
            'description' => $data['meta_description'] ?? null,
            'image' => $data['meta_image'] ?? null,
            'tw_image' => $data['meta_tw_image'] ?? null,
            'fb_image' => $data['meta_fb_image'] ?? null,
        ]);
    }

    /**
     * Update meta info for an appointment.
     *
     * @param Appointment $appointment
     * @param array<string, mixed> $data
     * @return void
     */
    private function updateMetaInfo(Appointment $appointment, array $data): void
    {
        $metaData = [
            'title' => $data['meta_title'] ?? null,
            'description' => $data['meta_description'] ?? null,
            'image' => $data['meta_image'] ?? $appointment->metainfo?->image,
            'tw_image' => $data['meta_tw_image'] ?? $appointment->metainfo?->tw_image,
            'fb_image' => $data['meta_fb_image'] ?? $appointment->metainfo?->fb_image,
        ];

        if ($appointment->metainfo) {
            $appointment->metainfo->update($metaData);
        } else {
            $appointment->metainfo()->create($metaData);
        }
    }

    /**
     * Create tax info for an appointment.
     *
     * @param Appointment $appointment
     * @param array<string, mixed> $data
     * @return void
     */
    private function createTaxInfo(Appointment $appointment, array $data): void
    {
        AppointmentTax::create([
            'appointment_id' => $appointment->id,
            'tax_type' => $data['tax_type'] ?? 'exclusive',
            'tax_amount' => $data['tax_amount'] ?? 0,
        ]);
    }

    /**
     * Update tax info for an appointment.
     *
     * @param Appointment $appointment
     * @param array<string, mixed> $data
     * @return void
     */
    private function updateTaxInfo(Appointment $appointment, array $data): void
    {
        if (!$data['tax_status']) {
            // Remove tax if disabled
            $appointment->tax?->delete();
            return;
        }

        $taxData = [
            'tax_type' => $data['tax_type'] ?? 'exclusive',
            'tax_amount' => $data['tax_amount'] ?? 0,
        ];

        if ($appointment->tax) {
            $appointment->tax->update($taxData);
        } else {
            AppointmentTax::create(array_merge($taxData, ['appointment_id' => $appointment->id]));
        }
    }

    /**
     * Sync sub-appointments for an appointment.
     *
     * @param Appointment $appointment
     * @param array<int> $subAppointmentIds
     * @return void
     */
    private function syncSubAppointments(Appointment $appointment, array $subAppointmentIds): void
    {
        // Remove existing links
        $appointment->additional_appointments()->delete();

        // Create new links
        foreach ($subAppointmentIds as $subAppointmentId) {
            AdditionalAppointment::create([
                'appointment_id' => $appointment->id,
                'sub_appointment_id' => $subAppointmentId,
            ]);
        }
    }
}
