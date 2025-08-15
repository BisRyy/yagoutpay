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
                'status' => $response['status'] ?? 'FAILED',
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
            // 1. Update your database with failed transaction status
            // 2. Log the failure for analysis
            // 3. Send notification email if needed
            
        } catch (Exception $e) {
            $error = 'Error processing response: ' . $e->getMessage();
        }
    } else {
        // Handle cases where we don't have the expected response format
        // This could happen when user cancels or there's a network issue
        $transactionData = [
            'status' => 'CANCELLED',
            'order_no' => $_POST['order_no'] ?? $_GET['order_no'] ?? '',
            'amount' => $_POST['amount'] ?? $_GET['amount'] ?? '',
            'response_message' => 'Ride booking was cancelled or could not be processed',
            'response_code' => 'USER_CANCELLED'
        ];
        
        // Log the incomplete response for debugging
        error_log('Incomplete YagoutPay response: ' . json_encode($_POST));
    }
} else {
    // Handle GET requests (direct access or redirect)
    $transactionData = [
        'status' => $_GET['status'] ?? 'FAILED',
        'order_no' => $_GET['order_no'] ?? '',
        'amount' => $_GET['amount'] ?? '',
        'txn_id' => $_GET['txn_id'] ?? '',
        'bank_ref_no' => $_GET['bank_ref_no'] ?? '',
        'response_code' => $_GET['response_code'] ?? '',
        'response_message' => $_GET['response_message'] ?? 'Ride booking could not be processed',
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
    <title>Booking Failed - RideYagout</title>
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
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .failure-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 600px;
            width: 100%;
            margin: 20px;
        }

        .failure-icon {
            width: 100px;
            height: 100px;
            background: var(--danger-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 50px;
        }

        .failure-icon.cancelled {
            background: #6c757d;
        }

        .failure-icon.warning {
            background: var(--warning-color);
            color: var(--dark-color);
        }

        .failure-title {
            color: var(--danger-color);
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .failure-title.cancelled {
            color: #6c757d;
        }

        .failure-title.warning {
            color: var(--warning-color);
        }

        .failure-subtitle {
            color: #6c757d;
            font-size: 1.2rem;
            margin-bottom: 30px;
        }

        .booking-info {
            background: var(--light-color);
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }

        .booking-info h4 {
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

        .status-failed {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }

        .status-cancelled {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }

        .error-reasons {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }

        .error-reasons h5 {
            color: var(--warning-color);
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-reasons ul {
            margin: 0;
            padding-left: 20px;
        }

        .error-reasons li {
            margin-bottom: 8px;
            color: #6c757d;
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

        .support-info {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
        }

        .support-info h4 {
            margin-bottom: 15px;
            font-weight: 600;
        }

        .support-contact {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .support-contact i {
            width: 20px;
        }

        @media (max-width: 768px) {
            .failure-container {
                margin: 10px;
                padding: 30px 20px;
            }
            
            .failure-title {
                font-size: 2rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="failure-container">
        <?php 
        $status = strtoupper($transactionData['status']);
        $isCancelled = in_array($status, ['CANCELLED', 'ABORTED', 'USER_CANCELLED']);
        $isProcessing = in_array($status, ['PENDING', 'PROCESSING']);
        ?>
        
        <?php if ($error && !empty($transactionData['order_no'])): ?>
            <div class="failure-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="failure-title warning">Processing Error</h1>
            <p class="failure-subtitle">There was an issue processing your ride booking response. However, we have some information about your booking.</p>
            
            <div class="booking-info">
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
                    <span class="status-badge status-pending">UNKNOWN</span>
                </div>
            </div>
            
            <div class="support-info">
                <h4><i class="fas fa-headset"></i> Need Help?</h4>
                <div class="support-contact">
                    <i class="fas fa-phone"></i>
                    <span>Call us: +251 911 123 456</span>
                </div>
                <div class="support-contact">
                    <i class="fas fa-envelope"></i>
                    <span>Email: support@rideyagout.com</span>
                </div>
                <div class="support-contact">
                    <i class="fas fa-clock"></i>
                    <span>24/7 Customer Support</span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-redo me-2"></i>Try Again
                </a>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
            
        <?php elseif (!empty($transactionData['order_no']) || !empty($transactionData['response_message'])): ?>
            <?php if ($isCancelled): ?>
                <div class="failure-icon cancelled">
                    <i class="fas fa-times"></i>
                </div>
                <h1 class="failure-title cancelled">Booking Cancelled</h1>
                <p class="failure-subtitle">
                    <?php 
                        echo !empty($transactionData['response_message']) 
                            ? htmlspecialchars($transactionData['response_message'])
                            : 'Your ride booking was cancelled.';
                    ?>
                </p>
            <?php elseif ($isProcessing): ?>
                <div class="failure-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <h1 class="failure-title warning">Processing Your Booking</h1>
                <p class="failure-subtitle">
                    <?php 
                        echo !empty($transactionData['response_message']) 
                            ? htmlspecialchars($transactionData['response_message'])
                            : 'Your ride booking is being processed.';
                    ?>
                </p>
            <?php else: ?>
                <div class="failure-icon">
                    <i class="fas fa-times"></i>
                </div>
                <h1 class="failure-title">Booking Failed</h1>
                <p class="failure-subtitle">
                    <?php 
                        echo !empty($transactionData['response_message']) 
                            ? htmlspecialchars($transactionData['response_message'])
                            : 'Your ride booking could not be completed. Please try again.';
                    ?>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($transactionData['order_no'])): ?>
            <div class="booking-info">
                <h4><i class="fas fa-receipt"></i> Booking Details</h4>
                <div class="info-row">
                    <span class="info-label">Booking ID:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transactionData['order_no']); ?></span>
                </div>
                <?php if (!empty($transactionData['amount'])): ?>
                <div class="info-row">
                    <span class="info-label">Amount:</span>
                    <span class="info-value">ETB <?php echo htmlspecialchars($transactionData['amount']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($transactionData['txn_id'])): ?>
                <div class="info-row">
                    <span class="info-label">Transaction ID:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transactionData['txn_id']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($transactionData['response_code'])): ?>
                <div class="info-row">
                    <span class="info-label">Error Code:</span>
                    <span class="info-value"><?php echo htmlspecialchars($transactionData['response_code']); ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="status-badge <?php echo $isCancelled ? 'status-cancelled' : ($isProcessing ? 'status-pending' : 'status-failed'); ?>">
                        <?php echo htmlspecialchars($status); ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!$isCancelled && !$isProcessing): ?>
            <div class="error-reasons">
                <h5><i class="fas fa-info-circle"></i> Common reasons for booking failure:</h5>
                <ul>
                    <li>Insufficient funds in your account</li>
                    <li>Incorrect payment details</li>
                    <li>Transaction declined by your bank</li>
                    <li>Network connectivity issues</li>
                    <li>Daily transaction limit exceeded</li>
                    <li>Payment method not enabled for online transactions</li>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="support-info">
                <h4><i class="fas fa-headset"></i> Need Help?</h4>
                <div class="support-contact">
                    <i class="fas fa-phone"></i>
                    <span>Call us: +251 911 123 456</span>
                </div>
                <div class="support-contact">
                    <i class="fas fa-envelope"></i>
                    <span>Email: support@rideyagout.com</span>
                </div>
                <div class="support-contact">
                    <i class="fas fa-clock"></i>
                    <span>24/7 Customer Support</span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-redo me-2"></i>Try Again
                </a>
                <a href="index.php" class="btn btn-outline">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
            </div>
            
        <?php else: ?>
            <div class="failure-icon cancelled">
                <i class="fas fa-question"></i>
            </div>
            <h1 class="failure-title cancelled">No Booking Data</h1>
            <p class="failure-subtitle">No ride booking information was found.</p>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home me-2"></i>Book a Ride
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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
</body>
</html>
