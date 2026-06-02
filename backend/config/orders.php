<?php

return [
    'restaurant_acceptance_ttl_minutes' => (int) env('ORDER_RESTAURANT_ACCEPTANCE_TTL_MINUTES', 120),
    'pending_payment_ttl_minutes' => (int) env('ORDER_PENDING_PAYMENT_TTL_MINUTES', 120),
];
