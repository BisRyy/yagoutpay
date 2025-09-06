using Microsoft.AspNetCore.Mvc;
using YagoutPay;

var builder = WebApplication.CreateBuilder(args);
var app = builder.Build();

// Serve static files
app.UseStaticFiles();

// Home page - ride booking interface
app.MapGet("/", () => Results.Content(GetHomePageHtml(), "text/html"));

// Process payment
app.MapPost("/pay", (HttpContext context) =>
{
    try
    {
        // Get credentials from environment
        var merchantId = Environment.GetEnvironmentVariable("MERCHANT_ID") ?? "your_merchant_id";
        var encryptionKey = Environment.GetEnvironmentVariable("ENCRYPTION_KEY") ?? "your_base64_32byte_key";
        
        if (merchantId == "your_merchant_id" || encryptionKey == "your_base64_32byte_key")
        {
            return Results.BadRequest(@"
                <div style='font-family: Arial; padding: 40px; text-align: center; background: #f8f9fa;'>
                    <h2 style='color: #dc3545;'>⚠️ Demo Configuration Required</h2>
                    <p>Please set your YagoutPay credentials in environment variables:</p>
                    <div style='background: #e9ecef; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;'>
                        <code>MERCHANT_ID=your_actual_merchant_id<br/>
                        ENCRYPTION_KEY=your_actual_base64_32byte_key</code>
                    </div>
                    <p>Contact YagoutPay team to get your test credentials.</p>
                </div>
            ");
        }

        // Get form data
        var form = context.Request.Form;
        var productName = form["product_name"].ToString();
        var amount = decimal.Parse(form["amount"].ToString());
        var customerName = form["customer_name"].ToString();
        var emailId = form["email_id"].ToString();
        var mobileNo = form["mobile_no"].ToString();
        var pickupAddress = form["pickup_address"].ToString();

        // Validate required fields
        if (string.IsNullOrWhiteSpace(customerName) || string.IsNullOrWhiteSpace(emailId) || 
            string.IsNullOrWhiteSpace(mobileNo) || amount <= 0)
        {
            return Results.BadRequest("Missing required fields");
        }
        
        var client = new YagoutPayClient(merchantId, encryptionKey);
        var baseUrl = Environment.GetEnvironmentVariable("BASE_URL") ?? "http://localhost:5001";
        var orderNo = $"RIDE_{DateTimeOffset.UtcNow.ToUnixTimeMilliseconds()}_{new Random().Next(1000, 9999)}";
        
        var paymentRequest = new PaymentRequest
        {
            Transaction = new TransactionDetails
            {
                OrderNo = orderNo,
                Amount = amount,
                Currency = "ETB",
                TxnType = "SALE",
                SuccessUrl = $"{baseUrl}/success?order_no={orderNo}&product_name={Uri.EscapeDataString(productName)}&amount={amount}",
                FailureUrl = $"{baseUrl}/failure?order_no={orderNo}&reason=payment_failed"
            },
            Customer = new CustomerDetails
            {
                CustName = customerName,
                EmailId = emailId,
                MobileNo = mobileNo
            },
            Billing = new BillingDetails
            {
                BillAddress = pickupAddress,
                BillCity = "Addis Ababa",
                BillState = "Addis Ababa", 
                BillCountry = "Ethiopia",
                BillZip = ""
            }
        };

        var html = client.CreatePaymentForm(paymentRequest);
        return Results.Content(html, "text/html");
    }
    catch (Exception ex)
    {
        return Results.BadRequest($"Error: {ex.Message}");
    }
});

// Success page
app.MapMethods("/success", ["GET", "POST"], (string order_no, string product_name, string amount) =>
{
    return Results.Content($@"
        <div style='font-family: Arial; padding: 40px; text-align: center; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; min-height: 100vh;'>
            <div style='background: white; color: #333; border-radius: 15px; padding: 40px; max-width: 600px; margin: 0 auto; box-shadow: 0 20px 40px rgba(0,0,0,0.1);'>
                <h1 style='color: #28a745; margin-bottom: 20px;'>🎉 Ride Booked Successfully!</h1>
                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>Order Number:</strong> {order_no}</p>
                    <p><strong>Service:</strong> {product_name}</p>
                    <p><strong>Amount Paid:</strong> ETB {amount}</p>
                </div>
                <div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin: 20px 0; color: #0066cc;'>
                    <p><strong>📱 Your driver will contact you shortly!</strong></p>
                    <p>Estimated arrival: 5-10 minutes</p>
                </div>
                <a href='/' style='display: inline-block; background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; margin-top: 20px;'>Book Another Ride</a>
            </div>
        </div>
    ", "text/html");
});

// Failure page
app.MapMethods("/failure", ["GET", "POST"], (string order_no, string reason = "payment_failed") =>
{
    var displayReason = reason?.Replace("_", " ").ToUpper() ?? "PAYMENT FAILED";
    return Results.Content($@"
        <div style='font-family: Arial; padding: 40px; text-align: center; background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); color: white; min-height: 100vh;'>
            <div style='background: white; color: #333; border-radius: 15px; padding: 40px; max-width: 600px; margin: 0 auto; box-shadow: 0 20px 40px rgba(0,0,0,0.1);'>
                <h1 style='color: #dc3545; margin-bottom: 20px;'>❌ Booking Failed</h1>
                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>Order Number:</strong> {order_no}</p>
                    <p><strong>Reason:</strong> {displayReason}</p>
                </div>
                <div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; color: #856404;'>
                    <p><strong>💡 What happened?</strong></p>
                    <p>Your payment could not be processed. Please try again or contact support.</p>
                </div>
                <a href='/' style='display: inline-block; background: #dc3545; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; margin-top: 20px;'>Try Again</a>
            </div>
        </div>
    ", "text/html");
});

app.Run();

static string GetHomePageHtml()
{
    return @"<!DOCTYPE html>
<html lang=""en"">
<head>
    <meta charset=""UTF-8"">
    <meta name=""viewport"" content=""width=device-width, initial-scale=1.0"">
    <title>RideYagout - Book Your Ride</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; color: white; margin-bottom: 30px; }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .card { background: white; border-radius: 15px; padding: 30px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .ride-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .ride-card { border: 2px solid #e9ecef; border-radius: 10px; padding: 20px; cursor: pointer; transition: all 0.3s; text-align: center; }
        .ride-card:hover, .ride-card.selected { border-color: #667eea; background: #f8f9ff; }
        .ride-card h3 { color: #333; margin-bottom: 10px; }
        .ride-card .price { font-size: 1.5em; font-weight: bold; color: #667eea; }
        .ride-card .features { color: #666; font-size: 0.9em; margin-top: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input, select { width: 100%; padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; font-size: 16px; }
        input:focus, select:focus { outline: none; border-color: #667eea; }
        .btn { background: #667eea; color: white; padding: 15px 30px; border: none; border-radius: 25px; font-size: 18px; font-weight: 600; cursor: pointer; width: 100%; transition: all 0.3s; }
        .btn:hover { background: #5a6fd8; transform: translateY(-2px); }
        .price-summary { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .price-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .total { font-weight: bold; font-size: 1.2em; border-top: 2px solid #dee2e6; padding-top: 10px; }
        .error { color: #dc3545; font-size: 14px; margin-top: 5px; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } .ride-options { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class=""container"">
        <div class=""header"">
            <h1>🚗 RideYagout</h1>
            <p>Your reliable ride booking service</p>
        </div>

        <div class=""card"">
            <h2 style=""margin-bottom: 25px; color: #333;"">Choose Your Ride</h2>
            
            <form id=""rideForm"" action=""/pay"" method=""POST"">
                <div class=""ride-options"">
                    <div class=""ride-card"" data-product=""ride_economy"" data-name=""RideYagout - Economy"" data-price=""450"">
                        <h3>🚙 Economy</h3>
                        <div class=""price"">ETB 450</div>
                        <div class=""features"">Standard ride • 4 seats</div>
                    </div>
                    <div class=""ride-card selected"" data-product=""ride_comfort"" data-name=""RideYagout - Comfort"" data-price=""670"">
                        <h3>🚗 Comfort</h3>
                        <div class=""price"">ETB 670</div>
                        <div class=""features"">Comfortable • AC • 4 seats</div>
                    </div>
                    <div class=""ride-card"" data-product=""ride_premium"" data-name=""RideYagout - Premium"" data-price=""950"">
                        <h3>🏎️ Premium</h3>
                        <div class=""price"">ETB 950</div>
                        <div class=""features"">Luxury • Premium AC • 4 seats</div>
                    </div>
                </div>

                <h3 style=""margin-bottom: 20px; color: #333;"">Trip Details</h3>
                <div class=""form-row"">
                    <div class=""form-group"">
                        <label for=""pickup_address"">Pickup Location</label>
                        <input type=""text"" id=""pickup_address"" name=""pickup_address"" value=""Meskel Square"" required>
                    </div>
                    <div class=""form-group"">
                        <label for=""dropoff_address"">Dropoff Location</label>
                        <input type=""text"" id=""dropoff_address"" name=""dropoff_address"" value=""Bole Airport"" required>
                    </div>
                </div>
                <div class=""form-group"">
                    <label for=""distance"">Distance (km)</label>
                    <input type=""number"" id=""distance"" name=""distance"" value=""12"" min=""1"" max=""100"" required>
                </div>

                <h3 style=""margin-bottom: 20px; color: #333;"">Customer Information</h3>
                <div class=""form-row"">
                    <div class=""form-group"">
                        <label for=""customer_name"">Full Name</label>
                        <input type=""text"" id=""customer_name"" name=""customer_name"" required>
                    </div>
                    <div class=""form-group"">
                        <label for=""email_id"">Email Address</label>
                        <input type=""email"" id=""email_id"" name=""email_id"" required>
                    </div>
                </div>
                <div class=""form-group"">
                    <label for=""mobile_no"">Mobile Number</label>
                    <input type=""tel"" id=""mobile_no"" name=""mobile_no"" pattern=""09[0-9]{8}"" title=""Ethiopian mobile number (e.g., 0912345678)"" required>
                    <div class=""error"" id=""mobile_error"" style=""display: none;"">Please enter a valid Ethiopian mobile number (09xxxxxxxx)</div>
                </div>

                <div class=""price-summary"">
                    <div class=""price-row"">
                        <span>Base Fare:</span>
                        <span id=""base_fare"">ETB 670</span>
                    </div>
                    <div class=""price-row"">
                        <span>Distance (12 km):</span>
                        <span id=""distance_fare"">ETB 0</span>
                    </div>
                    <div class=""price-row total"">
                        <span>Total:</span>
                        <span id=""total_amount"">ETB 670</span>
                    </div>
                </div>

                <input type=""hidden"" id=""product_id"" name=""product_id"" value=""ride_comfort"">
                <input type=""hidden"" id=""product_name"" name=""product_name"" value=""RideYagout - Comfort"">
                <input type=""hidden"" id=""amount"" name=""amount"" value=""670"">

                <button type=""submit"" class=""btn"">🚗 Book & Pay Now</button>
            </form>
        </div>
    </div>

    <script>
        // Ride selection logic
        document.querySelectorAll('.ride-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.ride-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                
                document.getElementById('product_id').value = this.dataset.product;
                document.getElementById('product_name').value = this.dataset.name;
                document.getElementById('amount').value = this.dataset.price;
                
                updatePriceSummary();
            });
        });

        // Price calculation
        function updatePriceSummary() {
            const basePrice = parseInt(document.getElementById('amount').value);
            const distance = parseInt(document.getElementById('distance').value) || 0;
            const distanceFare = Math.max(0, (distance - 10) * 15); // Extra charge for >10km
            const total = basePrice + distanceFare;
            
            document.getElementById('base_fare').textContent = `ETB ${basePrice}`;
            document.getElementById('distance_fare').textContent = `ETB ${distanceFare}`;
            document.getElementById('total_amount').textContent = `ETB ${total}`;
            document.getElementById('amount').value = total;
        }

        // Distance change handler
        document.getElementById('distance').addEventListener('input', updatePriceSummary);

        // Mobile validation
        document.getElementById('mobile_no').addEventListener('input', function() {
            const mobile = this.value;
            const error = document.getElementById('mobile_error');
            if (mobile && !mobile.match(/^09[0-9]{8}$/)) {
                error.style.display = 'block';
            } else {
                error.style.display = 'none';
            }
        });

        // Form validation
        document.getElementById('rideForm').addEventListener('submit', function(e) {
            const mobile = document.getElementById('mobile_no').value;
            if (!mobile.match(/^09[0-9]{8}$/)) {
                e.preventDefault();
                document.getElementById('mobile_error').style.display = 'block';
                document.getElementById('mobile_no').focus();
            }
        });

        // Initialize
        updatePriceSummary();
    </script>
</body>
</html>";
}

