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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_no = 'RIDE_' . time() . '_' . rand(1000, 9999);
    $amount = floatval($_POST['amount']);
    
    // Generate payment form and redirect
    $paymentForm = $yagoutPay->generatePaymentForm([
        'order_no' => $order_no,
        'amount' => (string)$amount,
        'success_url' => BASE_URL . '/success.php',
        'failure_url' => BASE_URL . '/failure.php',
        'email_id' => $_POST['email'] ?? '',
        'mobile_no' => $_POST['mobile'] ?? '',
        'cust_name' => $_POST['customer_name'] ?? '',
        'country' => 'ETH',
        'currency' => 'ETB',
        'txn_type' => 'SALE',
        'channel' => 'WEB',
        'bill_address' => $_POST['pickup_address'] ?? '',
        'bill_city' => 'Addis Ababa',
        'bill_state' => 'Addis Ababa',
        'bill_country' => 'Ethiopia',
        'bill_zip' => ''
    ]);
    echo $paymentForm;
    exit;
}

// Sample ride data
$rideTypes = [
    'economy' => [
        'name' => 'Economy',
        'icon' => '🚗',
        'description' => 'Affordable rides for everyday trips',
        'basePrice' => 150,
        'perKm' => 25,
        'eta' => '5-10 min',
        'features' => ['Air conditioning', 'Clean vehicle', 'Professional driver']
    ],
    'comfort' => [
        'name' => 'Comfort',
        'icon' => '🚙',
        'description' => 'Premium comfort with extra space',
        'basePrice' => 200,
        'perKm' => 35,
        'eta' => '3-7 min',
        'features' => ['Larger vehicle', 'Premium interior', 'Priority pickup']
    ],
    'premium' => [
        'name' => 'Premium',
        'icon' => '🚘',
        'description' => 'Luxury rides for special occasions',
        'basePrice' => 300,
        'perKm' => 50,
        'eta' => '2-5 min',
        'features' => ['Luxury vehicle', 'Professional chauffeur', 'Complimentary refreshments']
    ]
];

$popularDestinations = [
    'addis_ababa_airport' => 'Addis Ababa Bole Airport',
    'meskel_square' => 'Meskel Square',
    'unity_park' => 'Unity Park',
    'national_museum' => 'National Museum'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RideYagout - Book Your Ride</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px auto;
            max-width: 1000px;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .booking-form {
            padding: 40px;
        }

        .form-section {
            background: var(--light-color);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .form-section h3 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ride-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .ride-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .ride-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
        }

        .ride-card.selected {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }

        .ride-card.selected::before {
            content: '✓';
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--primary-color);
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .ride-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .ride-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .ride-description {
            color: #6c757d;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .ride-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .ride-features {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .ride-features li {
            padding: 5px 0;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .ride-features li::before {
            content: '✓';
            color: var(--success-color);
            font-weight: bold;
            margin-right: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .price-summary {
            background: linear-gradient(135deg, var(--success-color) 0%, #20c997 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
        }

        .price-summary h4 {
            margin-bottom: 15px;
            font-weight: 600;
        }

        .price-breakdown {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .total-price {
            font-size: 1.5rem;
            font-weight: 700;
            border-top: 2px solid rgba(255,255,255,0.3);
            padding-top: 15px;
            margin-top: 15px;
        }

        .eta-display {
            background: var(--warning-color);
            color: var(--dark-color);
            border-radius: 10px;
            padding: 10px 15px;
            text-align: center;
            font-weight: 600;
            margin-top: 15px;
        }

        .popular-destinations {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .destination-btn {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .destination-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .booking-form {
                padding: 20px;
            }
            
            .ride-options {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <div class="header">
                <h1><i class="fas fa-car"></i> 🚗 Ride Yagout PHP</h1>
                <p>Book your ride with ease and comfort</p>
            </div>

            <div class="booking-form">
                <form method="POST" id="bookingForm">
                    <!-- Ride Selection -->
                    <div class="form-section">
                        <h3><i class="fas fa-car"></i> Choose Your Ride</h3>
                        <div class="ride-options">
                            <?php foreach ($rideTypes as $key => $ride): ?>
                            <div class="ride-card <?php echo $key === 'comfort' ? 'selected' : ''; ?>" data-ride="<?php echo $key; ?>" data-base-price="<?php echo $ride['basePrice']; ?>" data-per-km="<?php echo $ride['perKm']; ?>" data-eta="<?php echo $ride['eta']; ?>">
                                <div class="ride-icon"><?php echo $ride['icon']; ?></div>
                                <div class="ride-name"><?php echo $ride['name']; ?></div>
                                <div class="ride-description"><?php echo $ride['description']; ?></div>
                                <div class="ride-price">ETB <?php echo $ride['basePrice']; ?> + ETB <?php echo $ride['perKm']; ?>/km</div>
                                <ul class="ride-features">
                                    <?php foreach ($ride['features'] as $feature): ?>
                                    <li><?php echo $feature; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Trip Details -->
                    <div class="form-section">
                        <h3><i class="fas fa-route"></i> Trip Details</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Pickup Location</label>
                                    <input type="text" class="form-control" name="pickup_address" value="Meskel Square, Addis Ababa" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Drop-off Location</label>
                                    <input type="text" class="form-control" name="dropoff_address" value="Addis Ababa Bole Airport" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Distance (km)</label>
                                    <input type="number" class="form-control" id="distance" name="distance" value="12" min="1" step="0.1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="customer_name" placeholder="Enter your full name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" name="mobile" placeholder="Enter phone number" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" placeholder="Enter email address" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="popular-destinations">
                            <strong>Popular Destinations:</strong>
                            <?php foreach ($popularDestinations as $key => $destination): ?>
                            <button type="button" class="destination-btn" onclick="setDestination('<?php echo $destination; ?>')">
                                <?php echo $destination; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Price Summary -->
                    <div class="price-summary">
                        <h4><i class="fas fa-calculator"></i> Price Summary</h4>
                        <div class="price-breakdown">
                            <span>Base Fare:</span>
                            <span id="baseFare">ETB 200</span>
                        </div>
                        <div class="price-breakdown">
                            <span>Distance Charge:</span>
                            <span id="distanceCharge">ETB 420</span>
                        </div>
                        <div class="price-breakdown">
                            <span>Service Fee:</span>
                            <span>ETB 50</span>
                        </div>
                        <div class="price-breakdown total-price">
                            <span><strong>Total:</strong></span>
                            <span id="totalPrice"><strong>ETB 670</strong></span>
                        </div>
                        <div class="eta-display">
                            <i class="fas fa-clock"></i> Estimated arrival: <span id="eta">3-7 min</span>
                        </div>
                    </div>

                    <!-- Hidden fields for payment -->
                    <input type="hidden" name="amount" id="totalAmount" value="670">

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg" id="bookRideBtn">
                            <i class="fas fa-credit-card"></i> Book & Pay Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedRide = {
            key: 'comfort',
            basePrice: 200,
            perKm: 35,
            eta: '3-7 min'
        };
        const serviceFee = 50;

        // Ride selection
        document.querySelectorAll('.ride-card').forEach(card => {
            card.addEventListener('click', function() {
                // Remove previous selection
                document.querySelectorAll('.ride-card').forEach(c => c.classList.remove('selected'));
                
                // Add selection to clicked card
                this.classList.add('selected');
                
                selectedRide = {
                    key: this.dataset.ride,
                    basePrice: parseInt(this.dataset.basePrice),
                    perKm: parseInt(this.dataset.perKm),
                    eta: this.dataset.eta
                };
                
                updatePriceSummary();
                updateEta();
            });
        });

        // Distance input
        document.getElementById('distance').addEventListener('input', function() {
            updatePriceSummary();
        });

        // Update price summary
        function updatePriceSummary() {
            const distance = parseFloat(document.getElementById('distance').value) || 0;
            
            if (selectedRide) {
                const baseFare = selectedRide.basePrice;
                const distanceCharge = distance * selectedRide.perKm;
                const total = baseFare + distanceCharge + serviceFee;
                
                document.getElementById('baseFare').textContent = `ETB ${baseFare}`;
                document.getElementById('distanceCharge').textContent = `ETB ${distanceCharge}`;
                document.getElementById('totalPrice').innerHTML = `<strong>ETB ${total}</strong>`;
                document.getElementById('totalAmount').value = total;
            }
        }

        // Update ETA
        function updateEta() {
            if (selectedRide) {
                document.getElementById('eta').textContent = selectedRide.eta;
            }
        }

        // Set destination
        function setDestination(destination) {
            document.querySelector('input[name="dropoff_address"]').value = destination;
        }

        // Form submission
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            // Show loading state
            const submitBtn = document.getElementById('bookRideBtn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
