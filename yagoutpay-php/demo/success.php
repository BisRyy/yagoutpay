<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config.php';

use YagoutPay\YagoutPaySDK;

// Initialize SDK
$yagoutPay = new YagoutPaySDK(
    MERCHANT_ID,
    ENCRYPTION_KEY,
    YAGOUT_ENVIRONMENT
);

$response = null;
$error = '';
$transactionData = [];
$hasValidResponse = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if we have the required fields for parsing
    if (isset($_POST['merchant_response']) && isset($_POST['hash'])) {
        try {
            // Parse the response from YagoutPay
            $response = $yagoutPay->parseResponse($_POST);
            $hasValidResponse = true;
            
            // Store transaction data for display
            $transactionData = [
                'status' => $response['status'] ?? 'UNKNOWN',
                'order_no' => $response['order_no'] ?? '',
                'amount' => $response['amount'] ?? '',
                'txn_id' => $response['txn_id'] ?? '',
                'bank_ref_no' => $response['bank_ref_no'] ?? '',
                'response_code' => $response['response_code'] ?? '',
                'response_message' => $response['response_message'] ?? '',
                'payment_mode' => $response['payment_mode'] ?? '',
                'card_type' => $response['card_type'] ?? '',
                'masked_card_no' => $response['masked_card_no'] ?? ''
            ];
            
            // Here you would typically:
            // 1. Update your database with transaction status
            // 2. Send confirmation email to customer
            // 3. Update inventory if applicable
            // 4. Log the transaction
            
        } catch (Exception $e) {
            $error = 'Error processing response: ' . $e->getMessage();
        }
    } else {
        // Handle cases where we don't have the expected response format
        // This could happen when user is redirected without proper response
        $transactionData = [
            'status' => 'PENDING',
            'order_no' => $_POST['order_no'] ?? $_GET['order_no'] ?? '',
            'amount' => $_POST['amount'] ?? $_GET['amount'] ?? '',
            'response_message' => 'Your ride is being processed. You will receive a confirmation shortly.',
            'response_code' => 'PROCESSING'
        ];
        
        // Log the incomplete response for debugging
        error_log('Incomplete YagoutPay success response: ' . json_encode($_POST));
    }
} else {
    // Handle GET requests (direct access or redirect)
    $transactionData = [
        'status' => $_GET['status'] ?? 'UNKNOWN',
        'order_no' => $_GET['order_no'] ?? '',
        'amount' => $_GET['amount'] ?? '',
        'txn_id' => $_GET['txn_id'] ?? '',
        'bank_ref_no' => $_GET['bank_ref_no'] ?? '',
        'response_code' => $_GET['response_code'] ?? '',
        'response_message' => $_GET['response_message'] ?? 'Ride booking completed successfully',
        'payment_mode' => $_GET['payment_mode'] ?? '',
        'card_type' => $_GET['card_type'] ?? '',
        'masked_card_no' => $_GET['masked_card_no'] ?? ''
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Confirmed - RideYagout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --dark-color: #343a40;
            --light-color: #f8f9fa;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 600px;
            width: 100%;
            margin: 20px;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: var(--success-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 50px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .success-title {
            color: var(--success-color);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .success-subtitle {
            color: #6c757d;
            font-size: 1.2rem;
            margin-bottom: 30px;
        }

        .ride-info {
            background: var(--light-color);
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }

        .ride-info h4 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--dark-color);
        }

        .info-value {
            color: #6c757d;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-success {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }

        .driver-info {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
        }

        .driver-info h4 {
            margin-bottom: 15px;
            font-weight: 600;
        }

        .driver-details {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .driver-avatar {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .driver-text h5 {
            margin: 0;
            font-weight: 600;
        }

        .driver-text p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: var(--success-color);
            animation: confetti-fall 3s linear infinite;
        }

        @keyframes confetti-fall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(360deg); opacity: 0; }
        }

        @media (max-width: 768px) {
            .success-container {
                margin: 10px;
                padding: 30px 20px;
            }
            
            .success-title {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <?php if ($error && !empty($transactionData['order_no'])): ?>
            <div class="success-icon bg-warning">
                <i class="fas fa-clock"></i>
            </div>
            <h1 class="success-title text-warning">Processing Your Ride</h1>
            <p class="success-subtitle">There was an issue processing your payment response. However, we have some information about your booking.</p>
            
            <div class="ride-info">
                <h4><i class="fas fa-info-circle"></i> Available Booking Details</h4>
                <?php if (!empty($transactionData['order_no'])): ?>
                <div class="info-row">
                    <span class="info-label">Booking ID:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transactionData['order_no']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($transactionData['amount'])): ?>
                <div class="info-row">
                    <span class="info-label">Amount:</span>
                    <span class="info-value">ETB <?php echo htmlspecialchars($transactionData['amount']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="status-badge status-pending">PROCESSING</span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
            
        <?php elseif (!empty($transactionData['order_no'])): ?>
            <?php 
            $status = strtoupper($transactionData['status']);
            $isSuccess = in_array($status, ['SUCCESS', 'CAPTURED', 'COMPLETED']);
            $isPending = in_array($status, ['PENDING', 'PROCESSING']);
            ?>
            
            <?php if ($isSuccess): ?>
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h1 class="success-title">Ride Confirmed!</h1>
                <p class="success-subtitle">Your ride has been successfully booked and payment completed.</p>
                
                <div class="driver-info">
                    <h4><i class="fas fa-user-tie"></i> Your Driver</h4>
                    <div class="driver-details">
                        <div class="driver-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="driver-text">
                            <h5>Driver Name</h5>
                            <p>Vehicle: Toyota Corolla • License: ABC123</p>
                        </div>
                    </div>
                    <p><i class="fas fa-phone"></i> <strong>+251 911 123 456</strong></p>
                    <p><i class="fas fa-clock"></i> <strong>Arriving in 5-10 minutes</strong></p>
                </div>
                
            <?php elseif ($isPending): ?>
                <div class="success-icon bg-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <h1 class="success-title text-warning">Processing Your Ride</h1>
                <p class="success-subtitle">Your ride booking is being processed. You will receive a confirmation shortly.</p>
                
            <?php else: ?>
                <div class="success-icon bg-secondary">
                    <i class="fas fa-info-circle"></i>
                </div>
                <h1 class="success-title text-secondary">Booking Status: <?php echo htmlspecialchars($status); ?></h1>
                <p class="success-subtitle"><?php echo htmlspecialchars($transactionData['response_message'] ?? 'Your ride booking has been processed.'); ?></p>
            <?php endif; ?>
            
            <div class="ride-info">
                <h4><i class="fas fa-receipt"></i> Booking Details</h4>
                
                <?php if (!empty($transactionData['order_no'])): ?>
                <div class="info-row">
                    <span class="info-label">Booking ID:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transactionData['order_no']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($transactionData['txn_id'])): ?>
                <div class="info-row">
                    <span class="info-label">Transaction ID:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transactionData['txn_id']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($transactionData['amount'])): ?>
                <div class="info-row">
                    <span class="info-label">Amount Paid:</span>
                    <span class="info-value">ETB <?php echo htmlspecialchars($transactionData['amount']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($transactionData['payment_mode'])): ?>
                <div class="info-row">
                    <span class="info-label">Payment Method:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transactionData['payment_mode']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($transactionData['bank_ref_no'])): ?>
                <div class="info-row">
                    <span class="info-label">Bank Reference:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transactionData['bank_ref_no']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="status-badge <?php echo $isSuccess ? 'status-success' : ($isPending ? 'status-pending' : 'status-pending'); ?>">
                        <?php echo htmlspecialchars($status); ?>
                    </span>
                </div>
            </div>
            
            <?php if ($isSuccess): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-envelope"></i> A confirmation email has been sent to your registered email address.
            </div>
            <?php endif; ?>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Book Another Ride
                </a>
                <a href="#" class="btn btn-outline">
                    <i class="fas fa-phone me-2"></i>Contact Support
                </a>
            </div>
            
        <?php else: ?>
            <div class="success-icon bg-secondary">
                <i class="fas fa-question"></i>
            </div>
            <h1 class="success-title text-secondary">No Booking Data</h1>
            <p class="success-subtitle">No ride booking information was found.</p>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Book a Ride
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($isSuccess): ?>
    <script>
        // Create confetti effect
        function createConfetti() {
            const colors = ['#28a745', '#20c997', '#17a2b8', '#6f42c1', '#fd7e14'];
            
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.animationDelay = Math.random() * 3 + 's';
                confetti.style.animationDuration = (Math.random() * 3 + 2) + 's';
                
                document.body.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }
        }
        
        // Trigger confetti on page load
        setTimeout(createConfetti, 500);
        
        // Auto-redirect after 30 seconds
        let countdown = 30;
        const countdownElement = document.createElement('div');
        countdownElement.className = 'text-muted small mt-3';
        countdownElement.innerHTML = `Redirecting to home page in <span id="countdown">${countdown}</span> seconds...`;
        
        document.querySelector('.action-buttons').appendChild(countdownElement);
        
        const timer = setInterval(() => {
            countdown--;
            const countdownSpan = document.getElementById('countdown');
            if (countdownSpan) {
                countdownSpan.textContent = countdown;
            }
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = 'index.php';
            }
        }, 1000);
        
        // Clear timer if user clicks any button
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('click', () => clearInterval(timer));
        });
    </script>
    <?php endif; ?>
</body>
</html>
