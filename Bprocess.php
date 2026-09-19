<?php
session_start();
require 'config.php';  // Database connection

$conn = connectDB();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Student_Login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $program_value = $_POST['program']; // This gets the radio button value (IHHCM, PAC, PH)
    
    // Map the program value to full title
    $program_titles = [
        'IHHCM' => 'In-Home Health Care Management',
        'PAC' => 'Patient Attendant Course',
        'PH' => 'Public Health'
    ];
    
    $program = $program_titles[$program_value]; // Get full program title
    
    // Step 1: Update or insert the student's data into the table
    $stmt = $conn->prepare("UPDATE students SET name = ?, phone = ?, address = ?, program = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $phone, $address, $program, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Step 2: Get the id of the student
        $student_id = $_SESSION['user_id'];
        
        // Step 3: Generate the custom student_id using program abbreviation for the ID only
        $current_year = date("Y");
        $custom_student_id = $current_year . "-" . $student_id . "-" . $program_value;
        
        // Step 4: Update the student record with the generated student_id
        $stmt2 = $conn->prepare("UPDATE students SET student_id = ? WHERE id = ?");
        $stmt2->bind_param("si", $custom_student_id, $student_id);
        $stmt2->execute();
        
        // Get program_id from programs table
        $stmt3 = $conn->prepare("SELECT program_id FROM programs WHERE title = ?");
        $stmt3->bind_param("s", $program);
        $stmt3->execute();
        $result = $stmt3->get_result();
        $program_data = $result->fetch_assoc();
        
        if ($program_data) {
            // Create initial enrollment record
            $enrollment_date = date('Y-m-d');
            $status = 'pending';
            
            // First check if enrollment already exists
            $check_stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND program_id = ? AND status = 'pending'");
            $check_stmt->bind_param("ii", $student_id, $program_data['program_id']);
            $check_stmt->execute();
            $existing = $check_stmt->get_result();
            
            if ($existing->num_rows === 0) {
                // Insert new enrollment
                $stmt4 = $conn->prepare("INSERT INTO enrollments (student_id, program_id, enrollment_date, status) 
                                       VALUES (?, ?, ?, ?)");
                $stmt4->bind_param("iiss", $student_id, $program_data['program_id'], $enrollment_date, $status);
                $stmt4->execute();
                
                $_SESSION['enrollment_id'] = $conn->insert_id;
            }
        }
        
        echo "Profile updated successfully. Your Student ID is: " . $custom_student_id;
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Step Form</title>
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f5f5f5;
            --text-color: #333;
            --border-color: #ddd;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--secondary-color);
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        h2 {
            text-align: center;
            color: var(--primary-color);
            margin-top: 0;
            padding: 20px 0;
            background-color: var(--secondary-color);
        }

        .form-step {
            display: none;
            padding: 20px;
        }

        .form-step.active {
            display: block;
        }

        .step-indicators {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 0 20px;
        }

        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #fff;
        }

        .step.active {
            background-color: var(--primary-color);
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="tel"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            box-sizing: border-box;
        }

        input[type="radio"] {
            margin-right: 10px;
        }

        button {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #3a7bc8;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .error {
            color: red;
            font-size: 14px;
            margin-top: 5px;
        }

        @media (max-width: 600px) {
            .container {
                width: 100%;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Complete Your Setup</h2>
        <div class="step-indicators">
            <div class="step active">1</div>
            <div class="step">2</div>
            <div class="step">3</div>
            <div class="step">4</div>
            <div class="step">5</div>
            <div class="step">6</div>
        </div>

        <form id="multi-step-form" method="POST" action="Bprocess.php">
            <div class="form-step active" id="step-1">
                <h3>Complete Your Profile</h3>
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required>
                <label for="address">Address</label>
                <input type="text" id="address" name="address" required>
                <div class="button-group">
                    <button type="button" onclick="nextStep()">Next</button>
                </div>
            </div>

            <div class="form-step" id="step-2">
                <h3>Select a Program</h3>
                <label>
                    <input type="radio" name="program" value="IHHCM" required>
                    In-Home Health Care Management (MK300,000)
                </label>
                <label>
                    <input type="radio" name="program" value="PAC" required>
                    Patient Attendant Course (MK350,000)
                </label>
                <label>
                    <input type="radio" name="program" value="PH" required>
                    Public Health (MK450,000)
                </label>
                <div class="button-group">
                    <button type="button" onclick="prevStep()">Previous</button>
                    <button type="button" onclick="nextStep()">Next</button>
                </div>
            </div>

            <div class="form-step" id="step-3">
                <h3>Agree to Terms and Policies</h3>
                <textarea readonly rows="10">Terms and policies content...</textarea>
                <label>
                    <input type="checkbox" name="agree" required>
                    I agree to the terms and policies.
                </label>
                <div class="button-group">
                    <button type="button" onclick="prevStep()">Previous</button>
                    <button type="button" onclick="nextStep()">Next</button>
                </div>
            </div>

            <div class="form-step" id="step-4">
                <h3>Orientation</h3>
                <p>Welcome to the orientation module. Please go through the following content to understand how to use the platform.</p>
                <p>Orientation content...</p>
                <div class="button-group">
                    <button type="button" onclick="prevStep()">Previous</button>
                    <button type="button" onclick="nextStep()">Next</button>
                </div>
            </div>

            <div class="form-step" id="step-5">
                <h3>Complete Your Submission</h3>
                <p>Please review your information and submit your application.</p>
                <button type="submit" id="submit-button">Submit Application</button>
                <div class="button-group">
                    <button type="button" onclick="prevStep()">Previous</button>
                    <button type="button" onclick="nextStep()">Next</button>
                </div>
            </div>

            <div class="form-step" id="step-6">
                <h3>Payment</h3>
                <p>Your application has been submitted successfully!</p>
                <p>Click the button below to proceed with payment.</p>
                <button type="button" id="pay-button" disabled>Proceed to Payment</button>
                <div class="button-group">
                    <button type="button" onclick="prevStep()">Previous</button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        let currentStep = 1;
        const totalSteps = 6; // Update total steps to 6

        function nextStep() {
            const activeStep = document.getElementById(`step-${currentStep}`);
            const inputs = activeStep.querySelectorAll('input[required], select[required], textarea[required]');
            let valid = true;

            // Special validation for step 2 (program selection)
            if (currentStep === 2) {
                const selectedProgram = activeStep.querySelector('input[name="program"]:checked');
                if (!selectedProgram) {
                    alert('Please select a program before proceeding.');
                    return;
                }
            }

            // Check if trying to proceed to step 6 without submission
            if (currentStep === 5) {
                const payButton = document.getElementById('pay-button');
                if (payButton.disabled) {
                    alert('Please submit your application before proceeding to payment.');
                    return;
                }
            }

            inputs.forEach(input => {
                if (!input.value) {
                    valid = false;
                    showError(input, 'This field is required');
                } else {
                    clearError(input);
                }
            });

            if (valid && currentStep < totalSteps) {
                document.getElementById(`step-${currentStep}`).classList.remove('active');
                currentStep++;
                document.getElementById(`step-${currentStep}`).classList.add('active');
                updateStepIndicator();
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                document.getElementById(`step-${currentStep}`).classList.remove('active');
                currentStep--;
                document.getElementById(`step-${currentStep}`).classList.add('active');
                updateStepIndicator();
            }
        }

        function updateStepIndicator() {
            const steps = document.querySelectorAll('.step-indicators .step');
            steps.forEach((step, index) => {
                if (index < currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });
        }

        function showError(input, message) {
            const errorElement = input.nextElementSibling;
            if (errorElement && errorElement.classList.contains('error')) {
                errorElement.textContent = message;
            } else {
                const error = document.createElement('div');
                error.className = 'error';
                error.textContent = message;
                input.parentNode.insertBefore(error, input.nextSibling);
            }
            input.classList.add('invalid');
        }

        function clearError(input) {
            const errorElement = input.nextElementSibling;
            if (errorElement && errorElement.classList.contains('error')) {
                errorElement.remove();
            }
            input.classList.remove('invalid');
        }

        document.getElementById('multi-step-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            // Convert FormData to URLSearchParams for proper POST submission
            const data = new URLSearchParams(formData);

            fetch('Bprocess.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: data
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Form submission failed');
                }
                return response.text();
            })
            .then(data => {
                alert('Submission successful! You can now proceed to payment.');
                document.getElementById('pay-button').disabled = false;
                document.getElementById('submit-button').disabled = true;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting the form. Please try again.');
            });
        });

    document.getElementById('pay-button').addEventListener('click', function(event) {
        event.preventDefault();

        if (this.disabled) {
            alert('Please submit the form before proceeding to payment.');
            return;
        }

    // Create payment data object
    const paymentData = {
        name: document.getElementById('name').value,
        phone: document.getElementById('phone').value,
        address: document.getElementById('address').value,
        program: document.querySelector('input[name="program"]:checked').value
    };

    // Disable button and show loading state
    this.disabled = true;
    this.textContent = 'Processing...';

    fetch('create-checkout-session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(paymentData)
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
                throw new Error('Server response: ' + text);
            });
        }
        return response.json();
    })
    .then(session => {
        if (session.error) {
            throw new Error(session.error);
        }
        const stripe = Stripe('pk_test_51PbJwdK01wZwbESlr9HnZkw58xymTq7YqXiXcq64VTlEO4YnfuI78P2ecNOPLmVPU0WSPJhFQnTiww53TZyuhbOR00d4oEkZ94');
        return stripe.redirectToCheckout({ sessionId: session.id });
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Payment processing error: ' + error.message);
        // Reset button state
        this.disabled = false;
        this.textContent = 'Proceed to Payment';
    });
});
    </script>
</body>
</html>
