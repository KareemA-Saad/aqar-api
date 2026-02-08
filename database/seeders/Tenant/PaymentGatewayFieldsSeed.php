<?php

namespace Database\Seeders\Tenant;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentGatewayFieldsSeed extends Seeder
{
    public static function execute()
    {
        $filePath = base_path('assets/tenant/page-layout/payment-gateway.json');
        if (!file_exists($filePath)) {
            \Illuminate\Support\Facades\Log::warning('PaymentGatewayFieldsSeed: payment-gateway.json not found, skipping');
            return;
        }

        $data = file_get_contents($filePath);
        $all_data_decoded = json_decode($data);

        if (empty($all_data_decoded)) {
            return;
        }

        $package = tenant()->paymentLog?->package ?? [];
        $all_features = $package->plan_features ?? [];
        $check_feature_name = $all_features->pluck('feature_name')->toArray();

        foreach ($all_data_decoded as $decoded){
            foreach ($decoded as $item){
                if(in_array($item->name,$check_feature_name)){
                    PaymentGateway::create([
                        'id' => $item->id,
                        'name' => $item->name,
                        'image' => $item->image,
                        'description' => $item->description,
                        'status' => $item->status,
                        'test_mode' => $item->test_mode,
                        'credentials' => $item->credentials,
                    ]);
                }
            }
        }
    }
}
