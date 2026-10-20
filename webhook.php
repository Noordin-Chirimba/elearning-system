<?php
require 'vendor/autoload.php';
require 'config.php'; // Include your database configuration

$stripeSecretKey = getenv('STRIPE_SECRET_KEY');
$endpoint_secret = getenv('STRIPE_WEBHOOK_SECRET');

if (!$stripeSecretKey || !$endpoint_secret) {
    error_log('Stripe webhook configuration is missing required environment variables.');
    http_response_code(500);
    exit;
}

\\Stripe\\Stripe::setApiKey($stripeSecretKey);

// Retrieve the request's body and parse it as JSON
$payload = @file_get_contents("php://input");
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    exit();
}

// Avoid writing payment/customer payloads to a publicly reachable file.

// Handle the event
switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;

        // Payment successful, insert details into the database
        $paymentId = $session->id;
        $amount = $session->amount_total;
        $currency = $session->currency;
        $email = $session->customer_details->email;

        // Retrieve user ID based on email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $userId = $user['id'];

            // Insert into the database
            $stmt = $pdo->prepare("INSERT INTO payments (payment_id, amount, currency, email, user_id) VALUES (:payment_id, :amount, :currency, :email, :user_id)");
            $stmt->execute([
                'payment_id' => $paymentId,
                'amount' => $amount,
                'currency' => $currency,
                'email' => $email,
                'user_id' => $userId,
            ]);
        } else {
            // Handle case where user is not found
            error_log("User not found for email: $email");
        }

        break;
    // ... handle other event types
    default:
        // Unexpected event type
        http_response_code(400);
        exit();
}

http_response_code(200);
?>
