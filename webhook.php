<?php
require 'vendor/autoload.php';
require 'config.php'; // Include your database configuration

\Stripe\Stripe::setApiKey('YOUR_STRIPE_SECRET_KEY_HERE'); // Replace with your actual Stripe secret key

// This is your Stripe CLI webhook secret for testing your endpoint locally.
$endpoint_secret = 'whsec_455ef85f3f944515fcf52e5b05af759d8f1249c594863d8c2fc3f5456306d534';

// Retrieve the request's body and parse it as JSON
$payload = @file_get_contents("php://input");
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
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

// Log the event for debugging
file_put_contents('webhook.log', date('Y-m-d H:i:s') . ' ' . json_encode($event) . PHP_EOL, FILE_APPEND);

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
