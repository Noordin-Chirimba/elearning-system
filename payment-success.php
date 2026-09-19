<?php
session_start();
require 'config.php';
require 'vendor/autoload.php';

$conn = connectDB();

if (isset($_GET['session_id'])) {
    \Stripe\Stripe::setApiKey('YOUR_STRIPE_SECRET_KEY_HERE');
    
    try {
        $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);
        
        if ($session->payment_status === 'paid') {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update enrollment status to active
                $stmt = $conn->prepare("UPDATE enrollments 
                                      SET status = 'active' 
                                      WHERE student_id = ? AND status = 'pending'
                                      ORDER BY enrollment_date DESC LIMIT 1");
                if (!$stmt->bind_param("i", $_SESSION['user_id']) || !$stmt->execute()) {
                    throw new Exception("Failed to update enrollment status");
                }
                
                // Update payment status in students table
                $stmt_payment = $conn->prepare("UPDATE students 
                                             SET payment = 1 
                                             WHERE id = ?");
                if (!$stmt_payment->bind_param("i", $_SESSION['user_id']) || !$stmt_payment->execute()) {
                    throw new Exception("Failed to update payment status");
                }
                
                // Get the program_id for the enrollment
                $stmt2 = $conn->prepare("SELECT program_id FROM enrollments 
                                       WHERE student_id = ? AND status = 'active'
                                       ORDER BY enrollment_date DESC LIMIT 1");
                if (!$stmt2->bind_param("i", $_SESSION['user_id']) || !$stmt2->execute()) {
                    throw new Exception("Failed to get program_id");
                }
                
                $result = $stmt2->get_result();
                $enrollment = $result->fetch_assoc();
                
                if ($enrollment) {
                    // Update total_enrollments in programs table
                    $stmt3 = $conn->prepare("UPDATE programs 
                                           SET total_enrollments = total_enrollments + 1 
                                           WHERE program_id = ?");
                    if (!$stmt3->bind_param("i", $enrollment['program_id']) || !$stmt3->execute()) {
                        throw new Exception("Failed to update total enrollments");
                    }
                }
                
                // Commit transaction
                $conn->commit();
                
                // Store payment success in session
                $_SESSION['payment_status'] = true;
                
                echo "<div style='text-align: center; margin-top: 50px;'>
                        <h2>Payment Successful!</h2>
                        <p>You are now enrolled in the program.</p>
                        <p>Redirecting to dashboard in 3 seconds...</p>
                      </div>";
                
                echo "<script>
                        setTimeout(function() {
                            window.location.href = 'student_dashboard.php';
                        }, 3000);
                      </script>";
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                throw $e;
            }
        }
    } catch (Exception $e) {
        echo "Error processing payment: " . $e->getMessage();
    }
}
?>

<style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f0f2f5;
        margin: 0;
        padding: 20px;
    }
    
    div {
        background-color: white;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        max-width: 500px;
        margin: 50px auto;
    }
    
    h2 {
        color: #2ecc71;
        margin-bottom: 20px;
    }
    
    p {
        color: #666;
        margin: 10px 0;
    }
</style>
