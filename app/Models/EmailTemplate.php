<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'category',
        'subject',
        'body',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LeadEmailLog::class);
    }

    public function scopeForTenant(Builder $query, ?int $tenantId, bool $isSuperAdmin = false): Builder
    {
        if ($isSuperAdmin) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($tenantId): void {
            if ($tenantId) {
                $sub->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
            } else {
                $sub->whereNull('tenant_id');
            }
        });
    }

    public function renderForLead(ExtractedLead $lead, ?User $sender = null): array
    {
        $vars = [
            '{{business_name}}' => $lead->business_name ?? 'Business Owner',
            '{{email}}' => is_array($lead->emails) ? ($lead->emails[0] ?? '') : (string) ($lead->emails ?? ''),
            '{{phone}}' => $lead->phone ?? '',
            '{{website}}' => $lead->website ?? '',
            '{{category}}' => $lead->category ?? 'your business',
            '{{address}}' => $lead->address ?? '',
            '{{city}}' => $lead->city ?: (explode(',', $lead->address ?? '')[0] ?? ''),
            '{{rating}}' => $lead->rating ? (string) $lead->rating : '',
            '{{reviews}}' => $lead->review_count ? (string) $lead->review_count : '',
            '{{sender_name}}' => $sender?->name ?? 'Our Team',
            '{{sender_company}}' => $sender?->tenant?->name ?? config('app.name', 'VektorLeads'),
            '{{app_url}}' => config('app.url', 'https://leads.obtainsolutions.com'),
            '{{pos_url}}' => 'https://pos.obtainsolutions.com/',
        ];

        $renderedSubject = str_replace(array_keys($vars), array_values($vars), $this->subject);
        $renderedBody = str_replace(array_keys($vars), array_values($vars), $this->body);

        return [
            'subject' => $renderedSubject,
            'body' => $renderedBody,
        ];
    }

    public static function getDefaultPresets(): array
    {
        $ctaStyle = 'display: inline-block; background-color: #7367f0; color: #ffffff !important; text-decoration: none; padding: 12px 26px; font-weight: 700; font-size: 14px; border-radius: 8px; box-shadow: 0 4px 14px rgba(115, 103, 240, 0.35); margin: 6px 0;';
        $boxStyle = 'background: #f8f7fa; border: 1px solid #ebe8f4; border-radius: 10px; padding: 16px 20px; margin: 18px 0;';

        return [
            [
                'name' => 'Auto Repair & Garage POS SaaS Invitation',
                'category' => 'Auto Repair & Garage',
                'subject' => "Transform {{business_name}}'s Workshop Operations with Modern Garage POS & Billing",
                'description' => 'Comprehensive pitch for automotive repair shops & garages covering job cards, automated invoicing, customer vehicle history, and inventory.',
                'is_default' => true,
                'body' => '<p>Hello <strong>{{business_name}}</strong> Team,</p>'
                    . '<p>I came across your workshop in <strong>{{city}}</strong> and wanted to reach out because we help automotive repair garages and service centers simplify daily operations and scale their business.</p>'
                    . '<p>Managing an auto repair shop with paperwork, manual job cards, and disconnected spreadsheets often leads to lost billable hours, untracked inventory, and missed customer follow-ups.</p>'
                    . '<p>Our cloud-based <strong>Automotive POS &amp; Garage Management System</strong> gives you everything needed to run your workshop effortlessly from any phone, tablet, or PC:</p>'
                    . "<div style=\"{$boxStyle}\">"
                    . '<div style="font-weight: 700; color: #7367f0; font-size: 12px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Workshop Cloud Capabilities</div>'
                    . '<ul style="margin: 0; padding-left: 18px; color: #4b465c;">'
                    . '<li style="margin-bottom: 6px;">🚗 <strong>Digital Job Cards &amp; Estimates:</strong> Create estimates in 30 seconds and send them directly to vehicle owners for instant SMS/WhatsApp approval.</li>'
                    . '<li style="margin-bottom: 6px;">🧾 <strong>Instant POS Invoicing &amp; Billing:</strong> Generate professional invoices, split labor and parts, record tax, and accept multi-channel payments.</li>'
                    . '<li style="margin-bottom: 6px;">📦 <strong>Live Spare Parts &amp; Inventory Tracking:</strong> Real-time alerts for low stock on filters, fluids, brake pads, and auto parts with automatic purchase order logs.</li>'
                    . '<li style="margin-bottom: 6px;">🔍 <strong>Complete Vehicle Service History:</strong> Track VIN, mileage, past inspections, and repair logs for every customer.</li>'
                    . '<li style="margin-bottom: 0;">⏰ <strong>Automated Service &amp; Oil Reminders:</strong> Send automatic retention reminders to bring customers back for regular maintenance.</li>'
                    . '</ul>'
                    . '</div>'
                    . "<div style=\"text-align: center; margin: 24px 0 16px;\"><a href=\"{{pos_url}}\" style=\"{$ctaStyle}\" target=\"_blank\">🚀 Test Drive Live POS Demo &rarr;</a></div>"
                    . '<p style="text-align: center; font-size: 12px; color: #6e6b7b; margin: 0 0 16px;">'
                    . 'Explore live platform: <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">https://pos.obtainsolutions.com/</a>'
                    . ' &nbsp;|&nbsp; Application: <a href="{{app_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;">{{app_url}}</a>'
                    . '</p>'
                    . '<p>Would you have 5 minutes this week for a quick walkthrough or to set up your free 14-day trial?</p>'
                    . '<p>Best regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}} &bull; <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">Obtain Solutions POS</a><br>Phone: {{phone}}</p>',
            ],
            [
                'name' => 'Oil Change & Quick Lube POS System',
                'category' => 'Oil & Lube Service',
                'subject' => 'Speed Up Oil & Filter Change Billing for {{business_name}} + Automated Service Reminders',
                'description' => 'Fast 1-click lube invoicing, engine oil grade tracking, filter inventory, and automated mileage/date sticker reminders.',
                'is_default' => false,
                'body' => '<p>Hi <strong>{{business_name}}</strong> Team,</p>'
                    . '<p>Hope you are having a productive week in <strong>{{city}}</strong>.</p>'
                    . '<p>In the quick lube and oil change business, <strong>speed at the counter and customer retention</strong> are your biggest profit drivers.</p>'
                    . '<p>Our <strong>Auto Lube &amp; Service POS</strong> is tailored specifically for oil &amp; filter change bays to eliminate bottlenecks and keep bays turning over faster:</p>'
                    . "<div style=\"{$boxStyle}\">"
                    . '<div style="font-weight: 700; color: #7367f0; font-size: 12px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Quick Lube Features</div>'
                    . '<ul style="margin: 0; padding-left: 18px; color: #4b465c;">'
                    . '<li style="margin-bottom: 6px;">🛢️ <strong>Quick-Pick Oil &amp; Filter Invoicing:</strong> Generate standard lube package bills (Engine Oil, Oil Filter, Air Filter, Cabin Filter) in under 15 seconds.</li>'
                    . '<li style="margin-bottom: 6px;">📊 <strong>Bulk Fluid &amp; Drum Inventory:</strong> Track synthetic, semi-synthetic, and conventional oil drums down to the liter.</li>'
                    . '<li style="margin-bottom: 6px;">📅 <strong>Automated Next-Service Reminders:</strong> Automatically calculate next oil change dates based on vehicle mileage and send automated SMS reminders.</li>'
                    . '<li style="margin-bottom: 0;">💳 <strong>Integrated POS Checkout:</strong> Print branded thermal receipts, digital invoices, and accept card/cash seamlessly.</li>'
                    . '</ul>'
                    . '</div>'
                    . "<div style=\"text-align: center; margin: 24px 0 16px;\"><a href=\"{{pos_url}}\" style=\"{$ctaStyle}\" target=\"_blank\">🛢️ Test Drive Oil Change POS Demo &rarr;</a></div>"
                    . '<p style="text-align: center; font-size: 12px; color: #6e6b7b; margin: 0 0 16px;">'
                    . 'Live Platform: <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">https://pos.obtainsolutions.com/</a>'
                    . ' &nbsp;|&nbsp; Portal: <a href="{{app_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;">{{app_url}}</a>'
                    . '</p>'
                    . '<p>Can I share a 3-minute interactive demo with you this week?</p>'
                    . '<p>Warm regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}} &bull; <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">Obtain Solutions POS</a><br>Phone: {{phone}}</p>',
            ],
            [
                'name' => 'Tyre Shop & Wheel Alignment POS & Inventory',
                'category' => 'Tyre & Wheel Shop',
                'subject' => 'Smart Tyre Inventory, Tread Depth Reports & Quick Billing for {{business_name}}',
                'description' => 'Tyre size search (e.g. 205/55R16), dot codes, wheel alignment inspections, and rapid POS invoicing.',
                'is_default' => false,
                'body' => '<p>Hello <strong>{{business_name}}</strong> Team,</p>'
                    . '<p>Are you looking for an easier way to manage tyre inventory, wheel alignments, balancing packages, and customer bills in <strong>{{city}}</strong>?</p>'
                    . '<p>Managing tyre sizes, brands, speed ratings, and seasonal stock across hundreds of units can quickly become overwhelming without a dedicated point-of-sale system.</p>'
                    . '<p>Our <strong>Tyre Center POS Platform</strong> is purpose-built for tyre retailers and fitment centers:</p>'
                    . "<div style=\"{$boxStyle}\">"
                    . '<div style="font-weight: 700; color: #7367f0; font-size: 12px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Tyre Fitment &amp; Retail Tools</div>'
                    . '<ul style="margin: 0; padding-left: 18px; color: #4b465c;">'
                    . '<li style="margin-bottom: 6px;">🛞 <strong>Tyre Size &amp; Brand Quick Search:</strong> Instantly look up tyre dimensions (Width / Profile / Rim / Speed Rating) and stock levels in real time.</li>'
                    . '<li style="margin-bottom: 6px;">📋 <strong>Wheel Alignment &amp; Tread Depth Inspection:</strong> Generate visual tyre condition reports to show customers why replacements or balancing are recommended.</li>'
                    . '<li style="margin-bottom: 6px;">🧾 <strong>Combo Packages &amp; Labour Billing:</strong> Bundle tyre fitting, wheel balancing, valve replacement, and disposal fees in 1 click.</li>'
                    . '<li style="margin-bottom: 0;">🔔 <strong>Seasonal Rotation Reminders:</strong> Bring customers back every 6 months for tyre rotation and pressure checks automatically.</li>'
                    . '</ul>'
                    . '</div>'
                    . "<div style=\"text-align: center; margin: 24px 0 16px;\"><a href=\"{{pos_url}}\" style=\"{$ctaStyle}\" target=\"_blank\">🛞 Test Drive Tyre Center POS Demo &rarr;</a></div>"
                    . '<p style="text-align: center; font-size: 12px; color: #6e6b7b; margin: 0 0 16px;">'
                    . 'Live Platform: <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">https://pos.obtainsolutions.com/</a>'
                    . ' &nbsp;|&nbsp; Portal: <a href="{{app_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;">{{app_url}}</a>'
                    . '</p>'
                    . '<p>Are you available for a brief chat to see how it works?</p>'
                    . '<p>Best regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}} &bull; <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">Obtain Solutions POS</a><br>Phone: {{phone}}</p>',
            ],
            [
                'name' => 'Auto Electrical, AC & Diagnostic Workshop POS',
                'category' => 'Electrical & AC Repair',
                'subject' => 'Digital Diagnostics, AC Gas Tracking & Job Cards for {{business_name}}',
                'description' => 'Diagnostic code logs, AC refrigerant & parts billing, technician commission, and detailed electrical repair work orders.',
                'is_default' => false,
                'body' => '<p>Hello <strong>{{business_name}}</strong> Team,</p>'
                    . '<p>Running a specialized automotive electrical, air conditioning, and diagnostics workshop requires precision tracking of diagnostic hours, specialized parts, and refrigerant consumables.</p>'
                    . '<p>Our <strong>Automotive POS &amp; Workshop Cloud</strong> gives specialized technicians and shop managers full control over every job:</p>'
                    . "<div style=\"{$boxStyle}\">"
                    . '<div style="font-weight: 700; color: #7367f0; font-size: 12px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Diagnostics &amp; Electrical Tools</div>'
                    . '<ul style="margin: 0; padding-left: 18px; color: #4b465c;">'
                    . '<li style="margin-bottom: 6px;">⚡ <strong>OBD Diagnostic Logs &amp; Fault Records:</strong> Attach diagnostic scan reports, fault codes (DTCs), and technician notes directly to the customer job card.</li>'
                    . '<li style="margin-bottom: 6px;">❄️ <strong>AC Service &amp; Gas Consumables Tracking:</strong> Accurately bill for R134a / R1234yf refrigerants, compressor oil, and leak detection dyes.</li>'
                    . '<li style="margin-bottom: 6px;">👨‍🔧 <strong>Technician Labor &amp; Commission Reports:</strong> Track billable diagnostic hours, labor efficiency, and technician payout breakdowns automatically.</li>'
                    . '<li style="margin-bottom: 0;">📲 <strong>WhatsApp &amp; SMS Status Updates:</strong> Keep car owners informed in real-time as repairs progress without spending hours on phone calls.</li>'
                    . '</ul>'
                    . '</div>'
                    . "<div style=\"text-align: center; margin: 24px 0 16px;\"><a href=\"{{pos_url}}\" style=\"{$ctaStyle}\" target=\"_blank\">⚡ Test Drive Diagnostic POS Demo &rarr;</a></div>"
                    . '<p style="text-align: center; font-size: 12px; color: #6e6b7b; margin: 0 0 16px;">'
                    . 'Tour live system: <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">https://pos.obtainsolutions.com/</a>'
                    . ' &nbsp;|&nbsp; Portal: <a href="{{app_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;">{{app_url}}</a>'
                    . '</p>'
                    . '<p>Let me know if you would like me to set up a quick 1-on-1 demo for your shop this week.</p>'
                    . '<p>Best regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}} &bull; <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">Obtain Solutions POS</a><br>Phone: {{phone}}</p>',
            ],
            [
                'name' => 'Automobile Workshop Growth & Interactive Demo',
                'category' => 'Special Offer',
                'subject' => "Exclusive Demo Invitation: Upgrade {{business_name}}'s Workshop with Cloud POS",
                'description' => 'Interactive demo invitation with link to test drive the POS application and claim special onboarding discount.',
                'is_default' => false,
                'body' => '<p>Hi <strong>{{business_name}}</strong> Team,</p>'
                    . '<p>Is your workshop ready to eliminate paperwork, boost daily turnover, and deliver a modern 5-star customer experience in <strong>{{city}}</strong>?</p>'
                    . '<p>Hundreds of automotive repair centers, body shops, and lube stations have switched to our SaaS POS solution to run their business from one single dashboard.</p>'
                    . "<div style=\"{$boxStyle}\">"
                    . '<div style="font-weight: 700; color: #7367f0; font-size: 12px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Why Garages Choose Obtain POS</div>'
                    . '<ul style="margin: 0; padding-left: 18px; color: #4b465c;">'
                    . '<li style="margin-bottom: 6px;">✨ <strong>Zero Hardware Restrictions:</strong> Runs smoothly on iPads, Android tablets, touch monitors, POS terminals, and smartphones.</li>'
                    . '<li style="margin-bottom: 6px;">💼 <strong>Complete Garage Operations:</strong> Job cards, spare parts inventory, estimates, invoices, customer CRM, and automated reminders.</li>'
                    . '<li style="margin-bottom: 6px;">📈 <strong>Real-time Financial &amp; Profit Reports:</strong> Monitor daily revenue, gross profit per repair job, top-selling parts, and outstanding credit.</li>'
                    . '<li style="margin-bottom: 0;">🔒 <strong>Secure Cloud Backups:</strong> Never lose customer vehicle history or billing records.</li>'
                    . '</ul>'
                    . '</div>'
                    . "<div style=\"text-align: center; margin: 24px 0 16px;\"><a href=\"{{pos_url}}\" style=\"{$ctaStyle}\" target=\"_blank\">✨ Launch Interactive Live Demo &rarr;</a></div>"
                    . '<p style="text-align: center; font-size: 12px; color: #6e6b7b; margin: 0 0 16px;">'
                    . 'Interactive Demo: <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">https://pos.obtainsolutions.com/</a>'
                    . ' &nbsp;|&nbsp; Outreach Engine: <a href="{{app_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;">{{app_url}}</a>'
                    . '</p>'
                    . '<p>Looking forward to connecting with you!</p>'
                    . '<p>Warm regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}} &bull; <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">Obtain Solutions POS</a><br>Phone: {{phone}}</p>',
            ],
            [
                'name' => 'Garage POS Follow-up & Profitability Consultation',
                'category' => 'Follow-up',
                'subject' => 'Following up: Streamlining invoicing and inventory for {{business_name}}',
                'description' => 'Gentle follow-up offering a 10-minute workshop workflow audit and tailored POS setup assistance.',
                'is_default' => false,
                'body' => '<p>Hi <strong>{{business_name}}</strong> Team,</p>'
                    . '<p>I wanted to quickly follow up on my previous message regarding modernizing workshop operations for your team in <strong>{{city}}</strong>.</p>'
                    . '<p>We know how busy garage owners and service advisors get during the week managing vehicles in the bays. That is why our POS system is designed to take less than <strong>15 minutes to set up</strong> and requires zero technical training for your mechanics.</p>'
                    . "<div style=\"{$boxStyle}\">"
                    . '<div style="font-weight: 700; color: #7367f0; font-size: 12px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">First 30 Days Profitability Impact</div>'
                    . '<ul style="margin: 0; padding-left: 18px; color: #4b465c;">'
                    . '<li style="margin-bottom: 6px;">✅ Prevent untracked parts and fluids from walking out the door without being billed.</li>'
                    . '<li style="margin-bottom: 6px;">✅ Speed up checkout time by 60% with instant digital invoicing and printed receipts.</li>'
                    . '<li style="margin-bottom: 0;">✅ Increase return visits with automatic service reminders sent right before oil/brake service is due.</li>'
                    . '</ul>'
                    . '</div>'
                    . "<div style=\"text-align: center; margin: 24px 0 16px;\"><a href=\"{{pos_url}}\" style=\"{$ctaStyle}\" target=\"_blank\">📅 Test Drive Live Demo &amp; Explore POS &rarr;</a></div>"
                    . '<p style="text-align: center; font-size: 12px; color: #6e6b7b; margin: 0 0 16px;">'
                    . 'Demo System: <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">https://pos.obtainsolutions.com/</a>'
                    . ' &nbsp;|&nbsp; Application: <a href="{{app_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;">{{app_url}}</a>'
                    . '</p>'
                    . '<p>Would you have 10 minutes this Tuesday or Thursday for a quick phone call to see if this is a good fit for {{business_name}}?</p>'
                    . '<p>Thanks and best regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}} &bull; <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">Obtain Solutions POS</a><br>Phone: {{phone}}</p>',
            ],
        ];
    }

    public static function seedDefaultTemplatesForTenant(?int $tenantId, ?int $userId = null, bool $force = false): void
    {
        $presets = self::getDefaultPresets();
        foreach ($presets as $preset) {
            $existing = self::where('tenant_id', $tenantId)->where('name', $preset['name'])->first();

            if ($existing) {
                if ($force || ! str_contains($existing->body, 'pos_url') || ! str_contains($existing->body, 'Test Drive Live POS Demo')) {
                    $existing->update([
                        'category' => $preset['category'],
                        'subject' => $preset['subject'],
                        'body' => $preset['body'],
                        'description' => $preset['description'],
                    ]);
                }
            } else {
                self::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId,
                    'name' => $preset['name'],
                    'category' => $preset['category'],
                    'subject' => $preset['subject'],
                    'body' => $preset['body'],
                    'description' => $preset['description'],
                    'is_default' => $preset['is_default'],
                ]);
            }
        }
    }

    public static function syncAllDefaultTemplates(bool $force = false): void
    {
        // Global / superadmin templates
        self::seedDefaultTemplatesForTenant(null, null, $force);

        // All active tenants
        $tenants = \App\Models\Tenant::all();
        foreach ($tenants as $tenant) {
            $adminUser = \App\Models\User::where('tenant_id', $tenant->id)
                ->whereIn('role', [\App\Models\User::ADMIN, 'tenant_admin'])
                ->first();
            self::seedDefaultTemplatesForTenant($tenant->id, $adminUser?->id, $force);
        }
    }
}

