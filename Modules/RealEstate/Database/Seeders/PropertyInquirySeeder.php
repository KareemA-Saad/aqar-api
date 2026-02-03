<?php

declare(strict_types=1);

namespace Modules\RealEstate\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyInquiry;

class PropertyInquirySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $properties = Property::where('is_published', true)
            ->inRandomOrder()
            ->limit(50)
            ->get();

        if ($properties->isEmpty()) {
            $this->command?->warn('No published properties found. Skipping inquiries.');
            return;
        }

        $statuses = ['new', 'contacted', 'qualified', 'converted', 'closed'];
        $sources = ['website', 'mobile_app', 'phone', 'walk_in', 'referral'];

        $firstNames = ['Ahmed', 'Mohamed', 'Omar', 'Youssef', 'Ali', 'Hassan', 'Mahmoud', 'Karim', 'Amr', 'Khaled'];
        $lastNames = ['Ibrahim', 'Hassan', 'Ali', 'Mohamed', 'Ahmed', 'Mahmoud', 'Youssef', 'Sayed', 'Mostafa', 'Ramadan'];

        $messages = [
            'I am interested in this property. Please contact me with more details.',
            'I would like to schedule a viewing for this property.',
            'Can you provide more information about the payment plans?',
            'Is this property still available? I am a serious buyer.',
            'I am looking for a similar property in this area. Can you help?',
            'What is the earliest delivery date for this unit?',
            'Are there any discounts for cash payment?',
            'I need more details about the amenities and facilities.',
        ];

        $adminNotes = [
            'Customer showed high interest. Follow up required.',
            'Discussed payment options. Waiting for decision.',
            'Scheduled site visit for next week.',
            'Customer is comparing with other properties.',
            'Hot lead - ready to make a decision soon.',
            'Budget constraint mentioned. Suggested alternatives.',
            'First-time buyer. Needs financing guidance.',
            null,
        ];

        foreach ($properties as $property) {
            try {
                // Generate 1-5 inquiries per property
                $inquiryCount = rand(1, 5);

                for ($i = 0; $i < $inquiryCount; $i++) {
                    $firstName = $firstNames[array_rand($firstNames)];
                    $lastName = $lastNames[array_rand($lastNames)];
                    
                    $status = $statuses[array_rand($statuses)];
                    $createdAt = now()->subDays(rand(1, 90));

                    $contactedAt = null;
                    $qualifiedAt = null;
                    $convertedAt = null;
                    $closedAt = null;

                    if (in_array($status, ['contacted', 'qualified', 'converted', 'closed'])) {
                        $contactedAt = $createdAt->copy()->addHours(rand(1, 48));
                    }
                    if (in_array($status, ['qualified', 'converted', 'closed'])) {
                        $qualifiedAt = $contactedAt?->copy()->addDays(rand(1, 7));
                    }
                    if (in_array($status, ['converted'])) {
                        $convertedAt = $qualifiedAt?->copy()->addDays(rand(3, 14));
                    }
                    if (in_array($status, ['closed'])) {
                        $closedAt = ($convertedAt ?? $qualifiedAt ?? $contactedAt)?->copy()->addDays(rand(1, 30));
                    }

                    PropertyInquiry::create([
                        'property_id' => $property->id,
                        'name' => $firstName . ' ' . $lastName,
                        'email' => strtolower($firstName) . '.' . strtolower($lastName) . rand(1, 99) . '@email.com',
                        'phone' => '+20' . rand(100, 199) . rand(1000000, 9999999),
                        'message' => $messages[array_rand($messages)],
                        'status' => $status,
                        'admin_notes' => $adminNotes[array_rand($adminNotes)],
                        'source' => $sources[array_rand($sources)],
                        'ip_address' => rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255),
                        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'referrer_url' => rand(1, 10) <= 7 ? 'https://example.com/properties' : null,
                        'contacted_at' => $contactedAt,
                        'qualified_at' => $qualifiedAt,
                        'converted_at' => $convertedAt,
                        'closed_at' => $closedAt,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt->copy()->addHours(rand(1, 48)),
                    ]);
                }
            } catch (\Exception $e) {
                $this->command?->error("Error seeding inquiries for property {$property->id}: " . $e->getMessage());
                Log::error("PropertyInquirySeeder error: " . $e->getMessage());
                continue;
            }
        }

        // Update inquiry counts on properties
        $this->updatePropertyInquiryCounts();
    }

    /**
     * Update inquiry counts on properties.
     */
    protected function updatePropertyInquiryCounts(): void
    {
        try {
            $counts = PropertyInquiry::select('property_id', DB::raw('COUNT(*) as count'))
                ->groupBy('property_id')
                ->pluck('count', 'property_id');

            foreach ($counts as $propertyId => $count) {
                Property::where('id', $propertyId)->update(['inquiry_count' => $count]);
            }
        } catch (\Exception $e) {
            $this->command?->warn('Could not update inquiry counts: ' . $e->getMessage());
        }
    }
}
