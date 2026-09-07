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
        return [
            [
                'name' => 'Auto Repair & Garage POS SaaS Invitation',
                'category' => 'Auto Repair & Garage',
                'subject' => "Transform {{business_name}}'s Workshop Operations with Modern Garage POS & Billing",
                'description' => 'Comprehensive pitch for automotive repair shops & garages covering job cards, automated invoicing, customer vehicle history, and inventory.',
                'is_default' => true,
                'body' => '<p>Hello <strong>{{business_name}}</strong> Team,</p>'
                    . '<p>I came across your workshop in <strong>{{city}}</strong> and wanted to reach out because we help automotive repair garages and service centers simplify their daily operations and scale their business.</p>'
                    . '<p>Managing an auto repair shop with paperwork, manual job cards, and disconnected spreadsheets often leads to lost billable hours, untracked inventory, and missed customer follow-ups.</p>'
                    . '<p>Our cloud-based <strong>Automotive POS &amp; Garage Management System</strong> gives you everything needed to run your workshop effortlessly from any phone, tablet, or PC:</p>'
                    . '<ul>'
                    . '<li>🚗 <strong>Digital Job Cards &amp; Estimates:</strong> Create estimates in 30 seconds and send them directly to vehicle owners for instant SMS/WhatsApp approval.</li>'
                    . '<li>🧾 <strong>Instant POS Invoicing &amp; Billing:</strong> Generate professional invoices, split labor and parts, record tax, and accept multi-channel payments.</li>'
                    . '<li>📦 <strong>Live Spare Parts &amp; Inventory Tracking:</strong> Real-time alerts for low stock on filters, fluids, brake pads, and auto parts with automatic purchase order logs.</li>'
                    . '<li>🔍 <strong>Complete Vehicle Service History:</strong> Track VIN, mileage, past inspections, and repair logs for every customer.</li>'
                    . '<li>⏰ <strong>Automated Service &amp; Oil Reminders:</strong> Send automatic retention reminders to bring customers back for regular maintenance.</li>'
                    . '</ul>'
                    . '<p>We invite you to test drive our live cloud demo with zero commitments: <a href="{{pos_url}}" style="color: #7367f0; font-weight: 600;" target="_blank">https://pos.obtainsolutions.com/</a></p>'
                    . '<p>Would you have 5 minutes this week for a quick walkthrough or to set up your free 14-day trial?</p>'
                    . '<p>Best regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}} &bull; <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">Obtain Solutions POS</a><br>Platform: <a href="{{app_url}}" style="color: #7367f0; text-decoration: none;">{{app_url}}</a><br>Phone: {{phone}}</p>',
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
                    . '<ul>'
                    . '<li>🛢️ <strong>Quick-Pick Oil &amp; Filter Invoicing:</strong> Generate standard lube package bills (Engine Oil, Oil Filter, Air Filter, Cabin Filter) in under 15 seconds.</li>'
                    . '<li>📊 <strong>Bulk Fluid &amp; Drum Inventory:</strong> Track synthetic, semi-synthetic, and conventional oil drums down to the liter.</li>'
                    . '<li>📅 <strong>Automated Next-Service Reminders:</strong> Automatically calculate next oil change dates based on vehicle mileage and send automated SMS reminders.</li>'
                    . '<li>💳 <strong>Integrated POS Checkout:</strong> Print branded thermal receipts, digital invoices, and accept card/cash seamlessly.</li>'
                    . '</ul>'
                    . '<p>You can test drive our live cloud platform directly here: <a href="{{pos_url}}" style="color: #7367f0; font-weight: 600;" target="_blank">https://pos.obtainsolutions.com/</a></p>'
                    . '<p>Can I share a 3-minute interactive demo with you this week?</p>'
                    . '<p>Warm regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}} &bull; <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">Obtain Solutions POS</a></p>',
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
                    . '<ul>'
                    . '<li>🛞 <strong>Tyre Size &amp; Brand Quick Search:</strong> Instantly look up tyre dimensions (Width / Profile / Rim / Speed Rating) and stock levels in real time.</li>'
                    . '<li>📋 <strong>Wheel Alignment &amp; Tread Depth Inspection:</strong> Generate visual tyre condition reports to show customers why replacements or balancing are recommended.</li>'
                    . '<li>🧾 <strong>Combo Packages &amp; Labour Billing:</strong> Bundle tyre fitting, wheel balancing, valve replacement, and disposal fees in 1 click.</li>'
                    . '<li>🔔 <strong>Seasonal Rotation Reminders:</strong> Bring customers back every 6 months for tyre rotation and pressure checks automatically.</li>'
                    . '</ul>'
                    . '<p>Experience our live demo for tyre centers at: <a href="{{pos_url}}" style="color: #7367f0; font-weight: 600;" target="_blank">https://pos.obtainsolutions.com/</a></p>'
                    . '<p>Are you available for a brief chat to see how it works?</p>'
                    . '<p>Best regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}} &bull; <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">Obtain Solutions POS</a></p>',
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
                    . '<ul>'
                    . '<li>⚡ <strong>OBD Diagnostic Logs &amp; Fault Records:</strong> Attach diagnostic scan reports, fault codes (DTCs), and technician notes directly to the customer job card.</li>'
                    . '<li>❄️ <strong>AC Service &amp; Gas Consumables Tracking:</strong> Accurately bill for R134a / R1234yf refrigerants, compressor oil, and leak detection dyes.</li>'
                    . '<li>👨‍🔧 <strong>Technician Labor &amp; Commission Reports:</strong> Track billable diagnostic hours, labor efficiency, and technician payout breakdowns automatically.</li>'
                    . '<li>📲 <strong>WhatsApp &amp; SMS Status Updates:</strong> Keep car owners informed in real-time as repairs progress without spending hours on phone calls.</li>'
                    . '</ul>'
                    . '<p>Take a tour of our live workshop system: <a href="{{pos_url}}" style="color: #7367f0; font-weight: 600;" target="_blank">https://pos.obtainsolutions.com/</a></p>'
                    . '<p>Let me know if you would like me to set up a quick 1-on-1 demo for your shop this week.</p>'
                    . '<p>Best regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}} &bull; <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">Obtain Solutions POS</a></p>',
            ],
            [
                'name' => 'Automobile Workshop Growth & Interactive Demo',
                'category' => 'Special Offer',
                'subject' => "Exclusive Demo Invitation: Upgrade {{business_name}}'s Workshop with Cloud POS",
                'description' => 'Interactive demo invitation with link to test drive the POS application and claim special onboarding discount.',
                'is_default' => false,
                'body' => '<p>Hi <strong>{{business_name}}</strong> Team,</p>'
                    . '<p>Is your workshop ready to eliminate paperwork, boost daily turnover, and deliver a modern 5-star customer experience in <strong>{{city}}</strong>?</p>'
                    . '<p>Over hundreds of automotive repair centers, body shops, and lube stations have switched to our SaaS POS solution to run their business from one single dashboard.</p>'
                    . '<p><strong>What you get with our Auto POS SaaS:</strong></p>'
                    . '<ul>'
                    . '<li>✨ <strong>Zero Hardware Restrictions:</strong> Runs smoothly on iPads, Android tablets, touch monitors, POS terminals, and smartphones.</li>'
                    . '<li>💼 <strong>Complete Garage Operations:</strong> Job cards, spare parts inventory, estimates, invoices, customer CRM, and automated reminders.</li>'
                    . '<li>📈 <strong>Real-time Financial &amp; Profit Reports:</strong> Monitor daily revenue, gross profit per repair job, top-selling parts, and outstanding credit.</li>'
                    . '<li>🔒 <strong>Secure Cloud Backups:</strong> Never lose customer vehicle history or billing records.</li>'
                    . '</ul>'
                    . '<p>👉 <strong>Test drive our live interactive POS demo right now:</strong> <a href="{{pos_url}}" style="color: #7367f0; font-weight: 600;" target="_blank">https://pos.obtainsolutions.com/</a></p>'
                    . '<p>Looking forward to connecting with you!</p>'
                    . '<p>Warm regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}} &bull; <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">Obtain Solutions POS</a><br>Outreach Engine: <a href="{{app_url}}" style="color: #7367f0; text-decoration: none;">{{app_url}}</a></p>',
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
                    . '<p>Here is how we help auto workshops improve profitability in their first 30 days:</p>'
                    . '<ul>'
                    . '<li>✅ Prevent untracked parts and fluids from walking out the door without being billed.</li>'
                    . '<li>✅ Speed up checkout time by 60% with instant digital invoicing and printed receipts.</li>'
                    . '<li>✅ Increase return visits with automatic service reminders sent right before oil/brake service is due.</li>'
                    . '</ul>'
                    . '<p>Feel free to click through our live interactive demo in the meantime: <a href="{{pos_url}}" style="color: #7367f0; font-weight: 600;" target="_blank">https://pos.obtainsolutions.com/</a></p>'
                    . '<p>Would you have 10 minutes this Tuesday or Thursday for a quick phone call to see if this is a good fit for {{business_name}}?</p>'
                    . '<p>Thanks and best regards,<br><strong>{{sender_name}}</strong><br>{{sender_company}} &bull; <a href="{{pos_url}}" style="color: #7367f0; text-decoration: none; font-weight: 600;" target="_blank">Obtain Solutions POS</a></p>',
            ],
        ];
    }

    public static function seedDefaultTemplatesForTenant(?int $tenantId, ?int $userId = null): void
    {
        $presets = self::getDefaultPresets();
        foreach ($presets as $preset) {
            self::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name' => $preset['name'],
                ],
                [
                    'user_id' => $userId,
                    'category' => $preset['category'],
                    'subject' => $preset['subject'],
                    'body' => $preset['body'],
                    'description' => $preset['description'],
                    'is_default' => $preset['is_default'],
                ]
            );
        }
    }
}

