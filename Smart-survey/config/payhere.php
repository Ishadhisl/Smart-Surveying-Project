<?php

return [
    /*
    |─────────────────────────────────────────────────────────────────
    | PayHere Sri Lanka Payment Gateway Configuration
    |─────────────────────────────────────────────────────────────────
    | 1. Register at https://www.payhere.lk
    | 2. Get Merchant ID & Secret from PayHere Dashboard
    | 3. Set these in your .env file:
    |
    |    PAYHERE_MERCHANT_ID=your_merchant_id
    |    PAYHERE_MERCHANT_SECRET=your_merchant_secret
    |    PAYHERE_SANDBOX=true   <-- set to false in production
    |
    */

    'merchant_id'     => env('PAYHERE_MERCHANT_ID'),
    'merchant_secret' => env('PAYHERE_MERCHANT_SECRET'),
    'sandbox'         => env('PAYHERE_SANDBOX', true),

    /*
    |─────────────────────────────────────────────────────────────────
    | Bank Transfer Details (shown to client as alternative)
    |─────────────────────────────────────────────────────────────────
    */
    'bank_name'          => env('BANK_NAME', 'People\'s Bank'),
    'bank_account_name'  => env('BANK_ACCOUNT_NAME', 'Smart Survey Systems'),
    'bank_account_number'=> env('BANK_ACCOUNT_NUMBER', '123-4567-8901'),
    'bank_branch'        => env('BANK_BRANCH', 'Colombo 03'),
];
