<?php

return [
    // Where Edfundo is told a merchant has submitted their onboarding for review.
    'notify_email' => env('ONBOARDING_NOTIFY_EMAIL'),

    // Link to the full merchant terms, shown beside the acceptance tick-box.
    'terms_url' => env('ONBOARDING_TERMS_URL'),

    // Days the emailed "set your password" link in the welcome email works.
    'welcome_link_days' => 7,
];
