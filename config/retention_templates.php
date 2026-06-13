<?php

return [
    'segments' => [
        'general_service_due' => [
            'template_key' => 'retention_general_service_due_v1',
            'template_label' => 'General Service Due',
            'event_aliases' => ['retention_general_service_due', 'retention.general_service'],
            'required_variables' => ['client_name', 'vehicle_name', 'last_service_date'],
            'fallback_message' => 'Hi {{client_name}}, your {{vehicle_name}} may be due for a general service check. Would you like us to help schedule a convenient time?',
        ],
        'oil_change_due' => [
            'template_key' => 'retention_oil_change_due_v1',
            'template_label' => 'Oil Change Due',
            'event_aliases' => ['retention_oil_change_due', 'retention.oil_service'],
            'required_variables' => ['client_name', 'vehicle_name'],
            'fallback_message' => 'Hi {{client_name}}, your {{vehicle_name}} may be due for an oil change soon. Would you like us to help schedule a quick service appointment?',
        ],
        'tyre_check_due' => [
            'template_key' => 'retention_tyre_check_due',
            'template_label' => 'Tyre Check Due',
            'event_aliases' => ['retention_tyre_check_due', 'retention.tyres'],
            'required_variables' => ['client_name', 'vehicle_name'],
            'fallback_message' => 'Hi {{client_name}}, your {{vehicle_name}} may be due for a tyre check. Would you like us to arrange a quick inspection?',
        ],
        'battery_follow_up' => [
            'template_key' => 'retention_battery_follow_up_v1',
            'template_label' => 'Battery Follow-up',
            'event_aliases' => ['retention_battery_follow_up', 'retention.battery'],
            'required_variables' => ['client_name', 'vehicle_name'],
            'fallback_message' => 'Hi {{client_name}}, just checking in on your {{vehicle_name}} battery. Would you like us to inspect it before it causes trouble?',
        ],
        'ac_service_reminder' => [
            'template_key' => 'retention_ac_service_reminder',
            'template_label' => 'AC Service Reminder',
            'event_aliases' => ['retention_ac_service_reminder', 'retention.ac'],
            'required_variables' => ['client_name', 'vehicle_name'],
            'fallback_message' => 'Hi {{client_name}}, with UAE weather, it may be a good time to check your {{vehicle_name}} AC. Would you like to book an AC inspection?',
        ],
        'brake_check_reminder' => [
            'template_key' => 'retention_brake_check_reminder',
            'template_label' => 'Brake Check Reminder',
            'event_aliases' => ['retention_brake_check_reminder', 'retention.brakes'],
            'required_variables' => ['client_name', 'vehicle_name'],
            'fallback_message' => 'Hi {{client_name}}, your {{vehicle_name}} may be due for a brake check. Would you like us to arrange a safety inspection?',
        ],
        'insurance_expiry_reminder' => [
            'template_key' => 'retention_insurance_expiry_v1',
            'template_label' => 'Insurance Expiry Reminder',
            'event_aliases' => ['retention_insurance_expiry'],
            'required_variables' => ['client_name', 'vehicle_name', 'follow_up_date'],
            'fallback_message' => 'Hi {{client_name}}, your {{vehicle_name}} insurance may be due for renewal soon. Would you like assistance with the renewal?',
        ],
        'mulkia_renewal_reminder' => [
            'template_key' => 'retention_mulkia_renewal_v1',
            'template_label' => 'Mulkia Renewal Reminder',
            'event_aliases' => ['retention_mulkia_renewal', 'retention.mulkia_renewal'],
            'required_variables' => ['client_name', 'vehicle_name', 'follow_up_date'],
            'fallback_message' => 'Hi {{client_name}}, your {{vehicle_name}} registration renewal may be coming up soon. Would you like us to help with the process?',
        ],
        'inactive_customer_winback' => [
            'template_key' => 'retention_winback',
            'template_label' => 'Inactive Customer Winback',
            'event_aliases' => ['retention_winback'],
            'required_variables' => ['client_name', 'vehicle_name'],
            'fallback_message' => 'Hi {{client_name}}, we have not seen your {{vehicle_name}} for a while. Would you like to book a quick inspection or service check?',
        ],
        'vip_follow_up' => [
            'template_key' => 'retention_vip_followup',
            'template_label' => 'VIP Follow-up',
            'event_aliases' => ['retention_vip_followup'],
            'required_variables' => ['client_name'],
            'fallback_message' => 'Hi {{client_name}}, we would be happy to help with your next service visit. Would you like us to arrange a convenient time?',
        ],
    ],
];
