<?php

namespace Database\Seeders;

use App\Models\ExtractedLead;
use App\Models\ExtractionJob;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Super Admin (Global Platform Owner, no tenant_id)
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@leads.test'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'tenant_id' => null,
                'phone' => '+1 (555) 010-0001',
                'is_active' => true,
            ]
        );

        // 2. Create Tenant 1: Acme Corporation
        $acme = Tenant::firstOrCreate(
            ['slug' => 'acme'],
            [
                'name' => 'Acme Corporation',
                'domain' => 'acme.leads.test',
                'plan' => 'enterprise',
                'lead_quota' => 50000,
                'leads_extracted_count' => 1420,
                'is_active' => true,
                'settings' => [
                    'default_engine' => 'google_api',
                    'auto_email_enrichment' => true,
                ],
            ]
        );

        $acmeAdmin = User::firstOrCreate(
            ['email' => 'admin@acme.com'],
            [
                'tenant_id' => $acme->id,
                'name' => 'Acme Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+1 (555) 019-2834',
                'is_active' => true,
            ]
        );

        $acmeUser = User::firstOrCreate(
            ['email' => 'user@acme.com'],
            [
                'tenant_id' => $acme->id,
                'name' => 'Sarah Connor',
                'password' => Hash::make('password'),
                'role' => 'user',
                'phone' => '+1 (555) 019-2835',
                'is_active' => true,
            ]
        );

        // 3. Create Tenant 2: Nexus Digital Marketing
        $nexus = Tenant::firstOrCreate(
            ['slug' => 'nexus'],
            [
                'name' => 'Nexus Digital Marketing',
                'domain' => 'nexus.leads.test',
                'plan' => 'pro',
                'lead_quota' => 15000,
                'leads_extracted_count' => 640,
                'is_active' => true,
                'settings' => [
                    'default_engine' => 'google_api',
                ],
            ]
        );

        $nexusAdmin = User::firstOrCreate(
            ['email' => 'admin@nexus.com'],
            [
                'tenant_id' => $nexus->id,
                'name' => 'Nexus Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '+1 (555) 018-9921',
                'is_active' => true,
            ]
        );

        // 4. Seed sample extraction jobs & leads for Acme
        $this->seedSampleExtractionsForTenant($acme, $acmeAdmin);
    }

    private function seedSampleExtractionsForTenant(Tenant $tenant, User $user): void
    {
        // Sample Job 1: Dentists in 90210
        $job1 = ExtractionJob::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => 'Dentists (90210 Beverly Hills)',
            'query' => 'Dentists in 90210',
            'status' => ExtractionJob::STATUS_COMPLETED,
            'limit' => 25,
            'mode' => 'google_api',
            'businesses_seen' => 30,
            'leads_extracted' => 25,
            'emails_found' => 18,
            'websites_found' => 24,
            'current_activity' => 'Extraction completed.',
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2)->addMinutes(1),
        ]);

        $dentists = [
            ['name' => 'Beverly Hills Dental Studio', 'addr' => '436 N Bedford Dr, Beverly Hills, CA 90210', 'phone' => '(310) 274-8828', 'web' => 'https://beverlyhillsdentalstudio.com', 'emails' => ['info@beverlyhillsdentalstudio.com', 'contact@beverlyhillsdentalstudio.com'], 'rating' => 4.9, 'reviews' => 142, 'cat' => 'Dentist'],
            ['name' => 'Rodeo Dental Care', 'addr' => '9400 Wilshire Blvd, Beverly Hills, CA 90212', 'phone' => '(310) 550-7000', 'web' => 'https://rodeodentalcare.com', 'emails' => ['care@rodeodentalcare.com'], 'rating' => 4.8, 'reviews' => 98, 'cat' => 'Cosmetic Dentist'],
            ['name' => 'Sunset Hills Pediatric Dentistry', 'addr' => '8500 Wilshire Blvd, Beverly Hills, CA 90211', 'phone' => '(310) 659-5437', 'web' => 'https://sunsethillsdentistry.com', 'emails' => ['hello@sunsethillsdentistry.com'], 'rating' => 5.0, 'reviews' => 210, 'cat' => 'Pediatric Dentist'],
            ['name' => 'Crown & Root Endodontics', 'addr' => '9735 Wilshire Blvd, Beverly Hills, CA 90212', 'phone' => '(310) 278-1440', 'web' => 'https://crownandroot.com', 'emails' => ['appointments@crownandroot.com'], 'rating' => 4.7, 'reviews' => 64, 'cat' => 'Endodontist'],
            ['name' => 'Wilshire Smiles Dental Arts', 'addr' => '9100 Wilshire Blvd, Beverly Hills, CA 90212', 'phone' => '(310) 273-0101', 'web' => 'https://wilshiresmiles.com', 'emails' => ['frontdesk@wilshiresmiles.com'], 'rating' => 4.9, 'reviews' => 180, 'cat' => 'Dental Clinic'],
        ];

        foreach ($dentists as $d) {
            $domain = parse_url($d['web'], PHP_URL_HOST) ?: $d['web'];
            ExtractedLead::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'extraction_job_id' => $job1->id,
                'business_name' => $d['name'],
                'address' => $d['addr'],
                'phone' => $d['phone'],
                'emails' => $d['emails'],
                'avatar_url' => 'https://www.google.com/s2/favicons?domain='.urlencode($domain).'&sz=128',
                'website' => $d['web'],
                'category' => $d['cat'],
                'rating' => $d['rating'],
                'review_count' => $d['reviews'],
                'google_maps_url' => 'https://maps.google.com/?q='.urlencode($d['name'].' '.$d['addr']),
                'source' => 'Google Places API',
                'extracted_at' => now()->subDays(2),
            ]);
        }

        // Sample Job 2: Real Estate Agents in Miami
        $job2 = ExtractionJob::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => 'Real Estate Agents (Miami FL)',
            'query' => 'Real Estate Agents in Miami FL',
            'status' => ExtractionJob::STATUS_COMPLETED,
            'limit' => 20,
            'mode' => 'google_api',
            'businesses_seen' => 24,
            'leads_extracted' => 20,
            'emails_found' => 15,
            'websites_found' => 19,
            'current_activity' => 'Extraction completed.',
            'started_at' => now()->subHours(12),
            'completed_at' => now()->subHours(12)->addSeconds(45),
        ]);

        $realEstates = [
            ['name' => 'Brickell Luxury Real Estate', 'addr' => '1450 Brickell Ave, Miami, FL 33131', 'phone' => '(305) 371-2000', 'web' => 'https://brickellluxury.com', 'emails' => ['leads@brickellluxury.com'], 'rating' => 4.9, 'reviews' => 112, 'cat' => 'Real Estate Agency'],
            ['name' => 'South Beach Realty Group', 'addr' => '1100 Lincoln Rd, Miami Beach, FL 33139', 'phone' => '(305) 538-4444', 'web' => 'https://soberealty.com', 'emails' => ['info@soberealty.com', 'agents@soberealty.com'], 'rating' => 4.8, 'reviews' => 84, 'cat' => 'Real Estate Consultant'],
            ['name' => 'Coral Gables Properties', 'addr' => '255 Aragon Ave, Coral Gables, FL 33134', 'phone' => '(305) 445-1200', 'web' => 'https://coralgablesproperties.com', 'emails' => ['contact@coralgablesproperties.com'], 'rating' => 4.7, 'reviews' => 56, 'cat' => 'Commercial Real Estate'],
        ];

        foreach ($realEstates as $re) {
            $domain = parse_url($re['web'], PHP_URL_HOST) ?: $re['web'];
            ExtractedLead::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'extraction_job_id' => $job2->id,
                'business_name' => $re['name'],
                'address' => $re['addr'],
                'phone' => $re['phone'],
                'emails' => $re['emails'],
                'avatar_url' => 'https://www.google.com/s2/favicons?domain='.urlencode($domain).'&sz=128',
                'website' => $re['web'],
                'category' => $re['cat'],
                'rating' => $re['rating'],
                'review_count' => $re['reviews'],
                'google_maps_url' => 'https://maps.google.com/?q='.urlencode($re['name'].' '.$re['addr']),
                'source' => 'Google Places API',
                'extracted_at' => now()->subHours(12),
            ]);
        }
    }
}
