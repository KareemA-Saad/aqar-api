<?php

namespace Database\Seeders;

use App\Models\PricePlan;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PricePlanSeeder extends Seeder
{
    public function run()
    {
        // Clear existing plans and features
        DB::table('plan_features')->delete();
        DB::table('price_plans')->delete();

        $plans = [
            [
                'title' => 'Premium Monthly',
                'price' => 0,
                'type' => PricePlan::TYPE_MONTHLY,
                'subtitle' => 'You can easily create your website by Pica. We will provide all type of digital service for you.',
                'features_list' => [
                    'Page 20', 'Blog 20', 'Donation 20', 'Job 20', 'Event 20', 'Article 20',
                    'Dashboard', 'Admin', 'User', 'Brand', 'Custom domain', 'Testimonial',
                    'Form builder', 'Own order manage', 'Page', 'Blog', 'Service', 'Donation',
                    'Job', 'Event', 'Support ticket', 'Knowledgebase', 'Faq', 'Gallery',
                    'Video', 'Portfolio', 'Storage', 'Appearance settings', 'General settings',
                    'Language', 'Payment gateways', 'Themes', 'Paypal', 'Stripe',
                    'Theme-article-listing', 'Theme-hotel-booking', 'Theme-portfolio'
                ]
            ],
            [
                'title' => 'Basic Monthly',
                'price' => 50,
                'type' => PricePlan::TYPE_MONTHLY,
                'subtitle' => 'You can easily create your website by Pica. We will provide all type of digital service for you.',
                'features_list' => [
                    'Page 20', 'Blog 20', 'Donation 20', 'Job 30', 'Event 14', 'Article 28',
                    'Dashboard', 'Admin', 'User', 'Brand', 'Custom domain', 'Testimonial',
                    'Form builder', 'Own order manage', 'Page', 'Blog', 'Service', 'Donation',
                    'Job', 'Event', 'Support ticket', 'Knowledgebase', 'Faq', 'Gallery',
                    'Video', 'Portfolio', 'Storage', 'Appearance settings', 'General settings',
                    'Language', 'Payment gateways', 'Themes', 'Paytm', 'Stripe', 'Mollie',
                    'Midtrans', 'Cashfree', 'Theme-portfolio', 'Theme-photography'
                ]
            ],
            [
                'title' => 'Standard Monthly',
                'price' => 120,
                'type' => PricePlan::TYPE_MONTHLY,
                'subtitle' => 'You can easily create your website by Pica. We will provide all type of digital service for you.',
                'features_list' => [
                    'Page 20', 'Appointment 20', 'Blog 20', 'Product 50', 'Donation 20',
                    'Event 20', 'Article 20', 'Portfolio 20', 'Dashboard', 'Admin', 'User',
                    'Brand', 'Newsletter', 'Custom domain', 'Testimonial', 'Form builder',
                    'Own order manage', 'Page', 'Blog', 'Service', 'Donation', 'Job',
                    'Appointment', 'Event', 'Support ticket', 'Knowledgebase', 'Faq',
                    'Gallery', 'Video', 'Portfolio', 'ECommerce', 'Storage', 'Advertisement',
                    'Wedding price plan', 'Appearance settings', 'General settings',
                    'Language', 'Payment gateways', 'Themes', 'Product',
                    'Product simple search permission', 'Product advance search permission',
                    'Product duplication permission', 'Product bulk delete permission',
                    'Inventory', 'Inventory update product permission',
                    'Inventory simple search permission', 'Inventory advance search permission',
                    'Campaign', 'Paypal', 'Paytm', 'Stripe', 'Razorpay', 'Paystack',
                    'Mollie', 'Midtrans', 'Cashfree', 'Instamojo', 'Marcadopago', 'Zitopay',
                    'Theme-agency', 'Theme-article-listing', 'Theme-barber-shop',
                    'Theme-construction', 'Theme-consultancy', 'Theme-donation',
                    'Theme-eCommerce', 'Theme-event', 'Theme-job-find', 'Theme-newspaper',
                    'Theme-photography', 'Theme-portfolio', 'Theme-software-business',
                    'Theme-support-ticketing', 'Theme-wedding'
                ]
            ]
        ];

        foreach ($plans as $planData) {
            $featuresList = $planData['features_list'];
            $mappedFeatures = $this->mapFeatures($featuresList);
            
            $title = json_encode(['en_US' => $planData['title'], 'ar' => $planData['title']]);
            $subtitle = json_encode(['en_US' => $planData['subtitle'], 'ar' => $planData['subtitle']]);
            $features = json_encode([
                'en_US' => implode("\n", $featuresList),
                'ar' => implode("\n", $featuresList)
            ]);
            
            $insertData = [
                'title' => $title,
                'subtitle' => $subtitle,
                'price' => (float)$planData['price'],
                'type' => (int)$planData['type'],
                'status' => 1,
                'zero_price' => $planData['price'] == 0 ? 'on' : 'off',
                'has_trial' => 0,
                'trial_days' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Ensure all permission columns are present
            $permissionColumns = [
                'blog_permission_feature', 'page_permission_feature', 'service_permission_feature',
                'donation_permission_feature', 'job_permission_feature', 'event_permission_feature',
                'knowledgebase_permission_feature', 'product_create_permission', 'campaign_create_permission',
                'storage_permission_feature', 'appointment_permission_feature'
            ];

            foreach ($permissionColumns as $column) {
                $insertData[$column] = $mappedFeatures[$column] ?? null;
            }

            $planId = DB::table('price_plans')->insertGetId($insertData);

            // Sync with plan_features table
            foreach ($featuresList as $index => $featureName) {
                DB::table('plan_features')->insert([
                    'plan_id' => $planId,
                    'feature_name' => $featureName,
                    'status' => 1,
                    'order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function mapFeatures(array $features): array
    {
        $mapping = [
            'page_permission_feature' => '/^Page (\d+)$/i',
            'blog_permission_feature' => '/^Blog (\d+)$/i',
            'donation_permission_feature' => '/^Donation (\d+)$/i',
            'job_permission_feature' => '/^Job (\d+)$/i',
            'event_permission_feature' => '/^Event (\d+)$/i',
            'knowledgebase_permission_feature' => '/^Article (\d+)$/i',
            'appointment_permission_feature' => '/^Appointment (\d+)$/i',
            'product_create_permission' => '/^Product (\d+)$/i',
            'portfolio_permission_feature' => '/^Portfolio (\d+)$/i',
            'storage_permission_feature' => '/^Storage (\d+)$/i',
            'service_permission_feature' => '/^Service$/i',
        ];

        $result = [];
        foreach ($features as $feature) {
            foreach ($mapping as $column => $pattern) {
                if (preg_match($pattern, $feature, $matches)) {
                    $value = isset($matches[1]) ? (int)$matches[1] : 1;
                    $result[$column] = $value;
                }
            }
        }

        return $result;
    }
}
