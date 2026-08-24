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
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@obtainsolutions.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('Obtain@2026!'),
                'role' => 'super_admin',
                'tenant_id' => null,
                'phone' => '+1 (555) 010-0001',
                'is_active' => true,
            ]
        );

        // 2. Create Single Primary Tenant: Obtain Solutions
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'obtain-solutions'],
            [
                'name' => 'Obtain Solutions',
                'domain' => 'obtainsolutions.com',
                'plan' => 'enterprise',
                'lead_quota' => 100000,
                'leads_extracted_count' => 2450,
                'is_active' => true,
                'settings' => [
                    'default_engine' => 'google_api',
                    'auto_email_enrichment' => true,
                    'auto_social_extraction' => true,
                ],
            ]
        );

        // 3. Create Tenant Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@obtainsolutions.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Obtain Admin',
                'password' => Hash::make('Obtain@2026!'),
                'role' => 'admin',
                'phone' => '+1 (555) 019-2834',
                'is_active' => true,
            ]
        );

        // 4. Remove any obsolete dummy users & tenants
        User::whereNotIn('email', [
            'superadmin@obtainsolutions.com',
            'admin@obtainsolutions.com',
        ])->delete();

        Tenant::where('id', '!=', $tenant->id)->delete();

        // 5. Seed rich extraction jobs & enriched leads for Obtain Solutions
        $this->seedEnrichedExtractions($tenant, $admin);

        // 6. Seed default email outreach templates
        $this->seedEmailTemplates($tenant, $admin);
    }

    private function seedEmailTemplates(Tenant $tenant, User $user): void
    {
        \App\Models\EmailTemplate::where('tenant_id', $tenant->id)->delete();

        \App\Models\EmailTemplate::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => 'B2B Partnership / Introduction',
            'category' => 'Introduction',
            'subject' => 'Quick inquiry for {{business_name}}',
            'body' => '<p>Hi <strong>{{business_name}}</strong> Team,</p><p>I came across your company in {{city}} and was very impressed with your work in {{category}}.</p><p>We specialize in helping businesses like yours scale customer acquisition and digital visibility.</p><p>Would you have 5-10 minutes this week for a brief exploratory call?</p><p>Best regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}}</p>',
            'description' => 'General cold outreach introduction template.',
            'is_default' => true,
        ]);

        \App\Models\EmailTemplate::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => 'Local Business Growth Proposal',
            'category' => 'Proposal',
            'subject' => 'Growth & lead generation opportunity for {{business_name}}',
            'body' => '<p>Hello <strong>{{business_name}}</strong> Management,</p><p>We noticed your {{rating}}★ rating in {{city}} and wanted to congratulate you on your reputation.</p><p>We have helped several {{category}} providers in your area increase qualified customer inquiries by over 40%.</p><p>If you are interested in reviewing a customized growth proposal, let us know and we will send it right over.</p><p>Warm regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}}</p>',
            'description' => 'Targeted proposal template highlighting ratings and local authority.',
            'is_default' => false,
        ]);

        \App\Models\EmailTemplate::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => 'Follow-up & Digital Audit',
            'category' => 'Follow-up',
            'subject' => 'Following up on digital performance for {{business_name}}',
            'body' => '<p>Hi <strong>{{business_name}}</strong>,</p><p>I wanted to quickly follow up regarding your website {{website}} and local visibility in {{city}}.</p><p>We prepared a complimentary 3-point digital assessment that highlights untapped lead generation channels for {{category}} businesses.</p><p>Let me know if you would like me to share the findings.</p><p>Sincerely,<br><strong>{{sender_name}}</strong><br>{{sender_company}}</p>',
            'description' => 'High-converting audit follow-up template.',
            'is_default' => false,
        ]);
    }

    private function seedEnrichedExtractions(Tenant $tenant, User $user): void
    {
        // Delete old sample jobs
        ExtractionJob::where('tenant_id', $tenant->id)->delete();

        // Job 1: Dentists in Beverly Hills, CA
        $job1 = ExtractionJob::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => 'Dentists (90210 Beverly Hills)',
            'query' => 'Dentists in 90210',
            'status' => ExtractionJob::STATUS_COMPLETED,
            'limit' => 50,
            'mode' => 'google_api',
            'businesses_seen' => 60,
            'leads_extracted' => 50,
            'emails_found' => 42,
            'websites_found' => 48,
            'current_activity' => 'Extraction completed.',
            'started_at' => now()->subDays(3),
            'completed_at' => now()->subDays(3)->addMinutes(2),
        ]);

        $dentists = [
            [
                'name' => 'Beverly Hills Dental Studio',
                'addr' => '436 N Bedford Dr, Beverly Hills, CA 90210',
                'phone' => '(310) 274-8828',
                'web' => 'https://beverlyhillsdentalstudio.com',
                'avatar' => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=160&auto=format&fit=crop&q=80',
                'emails' => ['info@beverlyhillsdentalstudio.com', 'care@beverlyhillsdentalstudio.com'],
                'socials' => [
                    'linkedin' => 'https://www.linkedin.com/company/beverly-hills-dental-studio',
                    'facebook' => 'https://www.facebook.com/beverlyhillsdentalstudio',
                    'instagram' => 'https://www.instagram.com/beverlyhillsdental',
                ],
                'rating' => 4.9,
                'reviews' => 142,
                'cat' => 'Dentist',
            ],
            [
                'name' => 'Rodeo Dental Care Arts',
                'addr' => '9400 Wilshire Blvd, Beverly Hills, CA 90212',
                'phone' => '(310) 550-7000',
                'web' => 'https://rodeodentalcare.com',
                'avatar' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?w=160&auto=format&fit=crop&q=80',
                'emails' => ['care@rodeodentalcare.com'],
                'socials' => [
                    'facebook' => 'https://www.facebook.com/rodeodentalcare',
                    'instagram' => 'https://www.instagram.com/rodeodental',
                    'twitter' => 'https://x.com/rodeodental',
                ],
                'rating' => 4.8,
                'reviews' => 98,
                'cat' => 'Cosmetic Dentist',
            ],
            [
                'name' => 'Sunset Hills Pediatric Dentistry',
                'addr' => '8500 Wilshire Blvd, Beverly Hills, CA 90211',
                'phone' => '(310) 659-5437',
                'web' => 'https://sunsethillsdentistry.com',
                'avatar' => 'https://images.unsplash.com/photo-1606811841689-23dfddce3e95?w=160&auto=format&fit=crop&q=80',
                'emails' => ['hello@sunsethillsdentistry.com'],
                'socials' => [
                    'facebook' => 'https://www.facebook.com/sunsethillsdentistry',
                    'instagram' => 'https://www.instagram.com/sunsethills_pediatric',
                    'youtube' => 'https://www.youtube.com/@sunsethillsdentistry',
                ],
                'rating' => 5.0,
                'reviews' => 210,
                'cat' => 'Pediatric Dentist',
            ],
            [
                'name' => 'Crown & Root Endodontics',
                'addr' => '9735 Wilshire Blvd, Beverly Hills, CA 90212',
                'phone' => '(310) 278-1440',
                'web' => 'https://crownandroot.com',
                'avatar' => 'https://images.unsplash.com/photo-1598256989800-fe5f95da9787?w=160&auto=format&fit=crop&q=80',
                'emails' => ['appointments@crownandroot.com'],
                'socials' => [
                    'linkedin' => 'https://www.linkedin.com/company/crown-and-root',
                    'facebook' => 'https://www.facebook.com/crownandroot',
                ],
                'rating' => 4.7,
                'reviews' => 64,
                'cat' => 'Endodontist',
            ],
            [
                'name' => 'Wilshire Smiles Dental Arts',
                'addr' => '9100 Wilshire Blvd, Beverly Hills, CA 90212',
                'phone' => '(310) 273-0101',
                'web' => 'https://wilshiresmiles.com',
                'avatar' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?w=160&auto=format&fit=crop&q=80',
                'emails' => ['frontdesk@wilshiresmiles.com'],
                'socials' => [
                    'facebook' => 'https://www.facebook.com/wilshiresmiles',
                    'instagram' => 'https://www.instagram.com/wilshiresmiles',
                ],
                'rating' => 4.9,
                'reviews' => 180,
                'cat' => 'Dental Clinic',
            ],
        ];

        foreach ($dentists as $d) {
            $domain = parse_url($d['web'], PHP_URL_HOST) ?: $d['web'];
            $vStatus = [];
            foreach ($d['emails'] as $em) {
                $vStatus[$em] = [
                    'email' => $em,
                    'is_valid' => true,
                    'is_rfc_valid' => true,
                    'is_disposable' => false,
                    'has_mx' => true,
                ];
            }

            ExtractedLead::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'extraction_job_id' => $job1->id,
                'business_name' => $d['name'],
                'address' => $d['addr'],
                'phone' => $d['phone'],
                'emails' => $d['emails'],
                'social_links' => $d['socials'],
                'email_verification_status' => $vStatus,
                'avatar_url' => $d['avatar'] ?? 'https://www.google.com/s2/favicons?domain='.urlencode($domain).'&sz=128',
                'website' => $d['web'],
                'category' => $d['cat'],
                'rating' => $d['rating'],
                'review_count' => $d['reviews'],
                'google_maps_url' => 'https://maps.google.com/?q='.urlencode($d['name'].' '.$d['addr']),
                'source' => 'Google Places API',
                'extracted_at' => now()->subDays(3),
            ]);
        }

        // Job 2: Plumbers in Dallas, TX (Geospatial Grid Search)
        $job2 = ExtractionJob::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => 'Plumbers (Dallas, TX)',
            'query' => 'Plumbers in Dallas, TX',
            'status' => ExtractionJob::STATUS_COMPLETED,
            'limit' => 100,
            'mode' => 'google_api',
            'businesses_seen' => 120,
            'leads_extracted' => 100,
            'emails_found' => 78,
            'websites_found' => 92,
            'current_activity' => 'Extraction completed.',
            'started_at' => now()->subDays(2),
            'completed_at' => now()->subDays(2)->addMinutes(3),
        ]);

        $plumbers = [
            [
                'name' => 'Dallas Master Plumbers LLC',
                'addr' => '1910 Pacific Ave, Dallas, TX 75201',
                'phone' => '(214) 555-0199',
                'web' => 'https://dallasmasterplumbing.com',
                'avatar' => 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=160&auto=format&fit=crop&q=80',
                'emails' => ['service@dallasmasterplumbing.com'],
                'socials' => [
                    'facebook' => 'https://www.facebook.com/dallasmasterplumbing',
                    'instagram' => 'https://www.instagram.com/dallasmasterplumbers',
                    'linkedin' => 'https://www.linkedin.com/company/dallas-master-plumbing',
                ],
                'rating' => 4.9,
                'reviews' => 156,
                'cat' => 'Plumber',
            ],
            [
                'name' => 'Lone Star Emergency Plumbing',
                'addr' => '2500 Main St, Dallas, TX 75226',
                'phone' => '(214) 555-0240',
                'web' => 'https://lonestarplumbingtx.com',
                'avatar' => 'https://images.unsplash.com/photo-1504328345606-18bbc8c9d7d1?w=160&auto=format&fit=crop&q=80',
                'emails' => ['dispatch@lonestarplumbingtx.com'],
                'socials' => [
                    'facebook' => 'https://www.facebook.com/lonestarplumbingtx',
                    'twitter' => 'https://x.com/lonestarplumb',
                ],
                'rating' => 4.8,
                'reviews' => 88,
                'cat' => 'Emergency Plumber',
            ],
            [
                'name' => 'Preston Hollow Plumbing & Gas',
                'addr' => '6000 Preston Rd, Dallas, TX 75205',
                'phone' => '(214) 555-0377',
                'web' => 'https://prestonhollowplumbing.com',
                'avatar' => 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=160&auto=format&fit=crop&q=80',
                'emails' => ['contact@prestonhollowplumbing.com'],
                'socials' => [
                    'facebook' => 'https://www.facebook.com/prestonhollowplumbing',
                    'youtube' => 'https://www.youtube.com/@prestonhollowplumbing',
                ],
                'rating' => 5.0,
                'reviews' => 94,
                'cat' => 'Commercial Plumber',
            ],
        ];

        foreach ($plumbers as $p) {
            $domain = parse_url($p['web'], PHP_URL_HOST) ?: $p['web'];
            $vStatus = [];
            foreach ($p['emails'] as $em) {
                $vStatus[$em] = [
                    'email' => $em,
                    'is_valid' => true,
                    'is_rfc_valid' => true,
                    'is_disposable' => false,
                    'has_mx' => true,
                ];
            }

            ExtractedLead::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'extraction_job_id' => $job2->id,
                'business_name' => $p['name'],
                'address' => $p['addr'],
                'phone' => $p['phone'],
                'emails' => $p['emails'],
                'social_links' => $p['socials'],
                'email_verification_status' => $vStatus,
                'avatar_url' => $p['avatar'] ?? 'https://www.google.com/s2/favicons?domain='.urlencode($domain).'&sz=128',
                'website' => $p['web'],
                'category' => $p['cat'],
                'rating' => $p['rating'],
                'review_count' => $p['reviews'],
                'google_maps_url' => 'https://maps.google.com/?q='.urlencode($p['name'].' '.$p['addr']),
                'source' => 'Google Places API',
                'extracted_at' => now()->subDays(2),
            ]);
        }

        // Job 3: Real Estate Agencies in Miami, FL
        $job3 = ExtractionJob::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => 'Real Estate Agents (Miami FL)',
            'query' => 'Real Estate Agents in Miami FL',
            'status' => ExtractionJob::STATUS_COMPLETED,
            'limit' => 50,
            'mode' => 'google_api',
            'businesses_seen' => 55,
            'leads_extracted' => 50,
            'emails_found' => 44,
            'websites_found' => 49,
            'current_activity' => 'Extraction completed.',
            'started_at' => now()->subDay(),
            'completed_at' => now()->subDay()->addMinutes(1),
        ]);

        $realEstates = [
            [
                'name' => 'Brickell Luxury Realty Group',
                'addr' => '1450 Brickell Ave, Miami, FL 33131',
                'phone' => '(305) 371-2000',
                'web' => 'https://brickellluxury.com',
                'avatar' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=160&auto=format&fit=crop&q=80',
                'emails' => ['leads@brickellluxury.com', 'agents@brickellluxury.com'],
                'socials' => [
                    'linkedin' => 'https://www.linkedin.com/company/brickell-luxury-realty',
                    'facebook' => 'https://www.facebook.com/brickellluxury',
                    'instagram' => 'https://www.instagram.com/brickellluxury',
                    'youtube' => 'https://www.youtube.com/@brickellluxuryrealty',
                ],
                'rating' => 4.9,
                'reviews' => 112,
                'cat' => 'Real Estate Agency',
            ],
            [
                'name' => 'South Beach Prime Properties',
                'addr' => '1100 Lincoln Rd, Miami Beach, FL 33139',
                'phone' => '(305) 538-4444',
                'web' => 'https://soberealty.com',
                'avatar' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=160&auto=format&fit=crop&q=80',
                'emails' => ['info@soberealty.com'],
                'socials' => [
                    'instagram' => 'https://www.instagram.com/soberealty',
                    'twitter' => 'https://x.com/soberealty',
                ],
                'rating' => 4.8,
                'reviews' => 84,
                'cat' => 'Real Estate Consultant',
            ],
            [
                'name' => 'Coral Gables Commercial Estates',
                'addr' => '255 Aragon Ave, Coral Gables, FL 33134',
                'phone' => '(305) 445-1200',
                'web' => 'https://coralgablesproperties.com',
                'avatar' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=160&auto=format&fit=crop&q=80',
                'emails' => ['contact@coralgablesproperties.com'],
                'socials' => [
                    'linkedin' => 'https://www.linkedin.com/company/coral-gables-estates',
                    'facebook' => 'https://www.facebook.com/coralgablesproperties',
                ],
                'rating' => 4.7,
                'reviews' => 56,
                'cat' => 'Commercial Real Estate',
            ],
        ];

        foreach ($realEstates as $re) {
            $domain = parse_url($re['web'], PHP_URL_HOST) ?: $re['web'];
            $vStatus = [];
            foreach ($re['emails'] as $em) {
                $vStatus[$em] = [
                    'email' => $em,
                    'is_valid' => true,
                    'is_rfc_valid' => true,
                    'is_disposable' => false,
                    'has_mx' => true,
                ];
            }

            ExtractedLead::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'extraction_job_id' => $job3->id,
                'business_name' => $re['name'],
                'address' => $re['addr'],
                'phone' => $re['phone'],
                'emails' => $re['emails'],
                'social_links' => $re['socials'],
                'email_verification_status' => $vStatus,
                'avatar_url' => $re['avatar'] ?? 'https://www.google.com/s2/favicons?domain='.urlencode($domain).'&sz=128',
                'website' => $re['web'],
                'category' => $re['cat'],
                'rating' => $re['rating'],
                'review_count' => $re['reviews'],
                'google_maps_url' => 'https://maps.google.com/?q='.urlencode($re['name'].' '.$re['addr']),
                'source' => 'Google Places API',
                'extracted_at' => now()->subDay(),
            ]);
        }

        // Job 4: Law Firms in New York, NY
        $job4 = ExtractionJob::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'uuid' => (string) Str::uuid(),
            'prompt' => 'Corporate Law Firms (New York, NY)',
            'query' => 'Corporate Law Firms in New York, NY',
            'status' => ExtractionJob::STATUS_COMPLETED,
            'limit' => 30,
            'mode' => 'google_api',
            'businesses_seen' => 35,
            'leads_extracted' => 30,
            'emails_found' => 28,
            'websites_found' => 30,
            'current_activity' => 'Extraction completed.',
            'started_at' => now()->subHours(6),
            'completed_at' => now()->subHours(6)->addMinutes(1),
        ]);

        $lawFirms = [
            [
                'name' => 'Manhattan Corporate Counsel LLP',
                'addr' => '350 5th Ave, New York, NY 10118',
                'phone' => '(212) 555-0188',
                'web' => 'https://manhattancorporatelaw.com',
                'avatar' => 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?w=160&auto=format&fit=crop&q=80',
                'emails' => ['partner@manhattancorporatelaw.com'],
                'socials' => [
                    'linkedin' => 'https://www.linkedin.com/company/manhattan-corporate-counsel',
                    'twitter' => 'https://x.com/manhattanlaw',
                ],
                'rating' => 4.9,
                'reviews' => 67,
                'cat' => 'Law Firm',
            ],
            [
                'name' => 'Wall Street Legal Advisors',
                'addr' => '100 Wall St, New York, NY 10005',
                'phone' => '(212) 555-0144',
                'web' => 'https://wallstreetadvisorslaw.com',
                'avatar' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=160&auto=format&fit=crop&q=80',
                'emails' => ['info@wallstreetadvisorslaw.com'],
                'socials' => [
                    'linkedin' => 'https://www.linkedin.com/company/wall-street-legal-advisors',
                    'facebook' => 'https://www.facebook.com/wallstreetlegal',
                ],
                'rating' => 4.8,
                'reviews' => 45,
                'cat' => 'Corporate Attorney',
            ],
        ];

        foreach ($lawFirms as $lf) {
            $domain = parse_url($lf['web'], PHP_URL_HOST) ?: $lf['web'];
            $vStatus = [];
            foreach ($lf['emails'] as $em) {
                $vStatus[$em] = [
                    'email' => $em,
                    'is_valid' => true,
                    'is_rfc_valid' => true,
                    'is_disposable' => false,
                    'has_mx' => true,
                ];
            }

            ExtractedLead::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'extraction_job_id' => $job4->id,
                'business_name' => $lf['name'],
                'address' => $lf['addr'],
                'phone' => $lf['phone'],
                'emails' => $lf['emails'],
                'social_links' => $lf['socials'],
                'email_verification_status' => $vStatus,
                'avatar_url' => $lf['avatar'] ?? 'https://www.google.com/s2/favicons?domain='.urlencode($domain).'&sz=128',
                'website' => $lf['web'],
                'category' => $lf['cat'],
                'rating' => $lf['rating'],
                'review_count' => $lf['reviews'],
                'google_maps_url' => 'https://maps.google.com/?q='.urlencode($lf['name'].' '.$lf['addr']),
                'source' => 'Google Places API',
                'extracted_at' => now()->subHours(6),
            ]);
        }
    }
}
