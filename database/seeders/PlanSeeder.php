<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'Ideal for small outreach teams and boutique agencies.',
                'price' => 29.00,
                'billing_interval' => 'monthly',
                'lead_quota' => 5000,
                'max_staff_members' => 5,
                'features' => [
                    '5,000 Verified Leads Monthly',
                    'Up to 5 Staff Team Members',
                    'Cloud Lead Discovery Engine',
                    'Social & Email Extraction',
                    'Export to Excel & CSV',
                ],
                'is_active' => true,
                'is_default' => false,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Pro',
                'description' => 'Designed for growing sales departments scaling client discovery.',
                'price' => 79.00,
                'billing_interval' => 'monthly',
                'lead_quota' => 25000,
                'max_staff_members' => 5,
                'features' => [
                    '25,000 Verified Leads Monthly',
                    'Up to 5 Staff Team Members',
                    'Priority Cloud Discovery Search',
                    'Instant Website Spec Generator',
                    'Email Outreach Campaigns',
                    'Export to Excel, CSV & JSON',
                ],
                'is_active' => true,
                'is_default' => true,
            ]
        );

        Plan::updateOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'description' => 'Full-scale agency operations with high-volume lead discovery.',
                'price' => 199.00,
                'billing_interval' => 'monthly',
                'lead_quota' => 100000,
                'max_staff_members' => 10,
                'features' => [
                    '100,000 Verified Leads Monthly',
                    'Up to 10 Staff Team Members',
                    'Dedicated Engine Platform Keys',
                    'High Velocity Sublocality Discovery',
                    'AI Spec Website Generator',
                    'Dedicated Priority Support',
                ],
                'is_active' => true,
                'is_default' => false,
            ]
        );
    }
}
