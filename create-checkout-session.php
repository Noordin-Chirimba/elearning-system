<?php
session_start();
require 'vendor/autoload.php';
require 'config.php';

// Set headers
header('Content-Type: application/json');

try {
    // Get JSON data from request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate input
    if (!$data) {
        throw new Exception('Invalid JSON data received');
    }

    if (!isset($data['name'], $data['phone'], $data['address'], $data['program'])) {
        throw new Exception('Missing required fields');
    }

    // Set your Stripe API key
    \Stripe\Stripe::setApiKey('YOUR_STRIPE_SECRET_KEY_HERE');

    // Map programs to prices
    $program_prices = [
        'IHHCM' => 300000,
        'PAC' => 350000,
        'PH' => 450000
    ];

    // Get program price
    if (!isset($program_prices[$data['program']])) {
        throw new Exception('Invalid program selected');
    }

    $amount = $program_prices[$data['program']];

    // Create Stripe checkout session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'mwk',
                'unit_amount' => $amount * 100, // Convert to cents
                'product_data' => [
                    'name' => $data['program'],
                    'description' => 'Program Registration Fee'
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $appBaseUrl . '/payment-success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $appBaseUrl . '/payment-cancel.php',
        'metadata' => [
            'student_name' => $data['name'],
            'phone' => $data['phone'],
            'program' => $data['program']
        ]
    ]);

    // Return successful response
    echo json_encode([
        'id' => $session->id
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
