<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Super Admin (Global Platform Owner, no tenant_id)
        User::updateOrCreate(
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
                'leads_extracted_count' => 0,
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

        // 5. Seed default email outreach templates
        $this->seedEmailTemplates($tenant, $admin);
    }

    private function seedEmailTemplates(Tenant $tenant, User $user): void
    {
        EmailTemplate::where('tenant_id', $tenant->id)->delete();

        EmailTemplate::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => 'B2B Partnership / Introduction',
            'category' => 'Introduction',
            'subject' => 'Quick inquiry for {{business_name}}',
            'body' => '<p>Hi <strong>{{business_name}}</strong> Team,</p><p>I came across your company in {{city}} and was very impressed with your work in {{category}}.</p><p>We specialize in helping businesses like yours scale customer acquisition and digital visibility.</p><p>Would you have 5-10 minutes this week for a brief exploratory call?</p><p>Best regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}}</p>',
            'description' => 'General cold outreach introduction template.',
            'is_default' => true,
        ]);

        EmailTemplate::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'name' => 'Local Business Growth Proposal',
            'category' => 'Proposal',
            'subject' => 'Growth & lead generation opportunity for {{business_name}}',
            'body' => '<p>Hello <strong>{{business_name}}</strong> Management,</p><p>We noticed your {{rating}}★ rating in {{city}} and wanted to congratulate you on your reputation.</p><p>We have helped several {{category}} providers in your area increase qualified customer inquiries by over 40%.</p><p>If you are interested in reviewing a customized growth proposal, let us know and we will send it right over.</p><p>Warm regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}}</p>',
            'description' => 'Targeted proposal template highlighting ratings and local authority.',
            'is_default' => false,
        ]);

        EmailTemplate::create([
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
}
