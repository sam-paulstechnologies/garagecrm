<?php

namespace App\Services\PlatformMarketing\Ai;

class PlatformSalesKnowledgeBase
{
    public function facts(): array
    {
        return [
            'product' => 'SayaraForce',
            'positioning' => 'CRM and WhatsApp automation for garages and automotive service businesses.',
            'target_customer' => 'Garages, service centers, and automotive businesses that manage leads, bookings, jobs, and customer follow-up.',
            'features' => [
                'WhatsApp inbox and automation',
                'Lead and opportunity tracking',
                'Bookings and job workflow',
                'Customer, vehicle, invoice, and operational reporting views',
                'Manager dashboard for daily garage operations',
            ],
            'demo_process' => 'A short online demo can be booked with the PaulsTechnologies team.',
            'website' => 'https://sayaraforce.com',
            'support_contact' => 'PaulsTechnologies LLC',
            'must_not_promise' => [
                'Guaranteed revenue or ROI',
                'Unpublished pricing',
                'Features not currently available in the product',
                'Customer results without proof',
            ],
        ];
    }
}
