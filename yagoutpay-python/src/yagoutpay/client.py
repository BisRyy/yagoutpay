"""
Main YagoutPay client for Python SDK
"""

from typing import Dict, Any, Optional
from .models import PaymentRequest, PaymentResponse, PaymentCallback
from .crypto import YagoutPayCrypto


class YagoutPay:
    """Main YagoutPay client for payment integration"""
    
    # Payment gateway URLs
    TEST_POST_URL = "https://uatcheckout.yagoutpay.com/ms-transaction-core-1-0/paymentRedirection/checksumGatewayPage"
    PROD_POST_URL = "https://checkout.yagoutpay.com/ms-transaction-core-1-0/paymentRedirection/checksumGatewayPage"
    
    def __init__(self, merchant_id: str, encryption_key: str, environment: str = "test"):
        """
        Initialize YagoutPay client
        
        Args:
            merchant_id: Your merchant ID
            encryption_key: Your 32-character encryption key
            environment: 'test' or 'production'
        """
        self.merchant_id = merchant_id
        self.encryption_key = encryption_key
        self.environment = environment.lower()
        
        # Initialize crypto utilities
        self.crypto = YagoutPayCrypto(encryption_key)
        
        # Set post URL based on environment
        self.post_url = self.TEST_POST_URL if self.environment == "test" else self.PROD_POST_URL
    
    def create_payment(self, payment_request: PaymentRequest) -> PaymentResponse:
        """
        Create a payment request
        
        Args:
            payment_request: PaymentRequest object with transaction, customer, and billing details
            
        Returns:
            PaymentResponse with encrypted data for gateway
        """
        # Prepare transaction details
        txn_details = {
            "ag_id": "yagout",
            "me_id": self.merchant_id,
            "order_no": payment_request.transaction.order_no,
            "amount": str(payment_request.transaction.amount),
            "country": payment_request.transaction.country,
            "currency": payment_request.transaction.currency,
            "txn_type": payment_request.transaction.txn_type,
            "success_url": payment_request.transaction.success_url,
            "failure_url": payment_request.transaction.failure_url,
            "channel": payment_request.transaction.channel,
        }
        
        # Prepare customer details
        cust_details = {
            "cust_name": payment_request.customer.cust_name,
            "email_id": payment_request.customer.email_id,
            "mobile_no": payment_request.customer.mobile_no,
            "unique_id": payment_request.customer.unique_id or "",
            "is_logged_in": payment_request.customer.is_logged_in,
        }
        
        # Prepare billing details
        bill_details = {}
        if payment_request.billing:
            bill_details = {
                "bill_address": payment_request.billing.bill_address or "",
                "bill_city": payment_request.billing.bill_city or "",
                "bill_state": payment_request.billing.bill_state or "",
                "bill_country": payment_request.billing.bill_country or "",
                "bill_zip": payment_request.billing.bill_zip or "",
            }
        
        # Build request data with all sections (matching JavaScript SDK)
        request_data = {
            "merchantId": self.merchant_id,
            "merchantKey": self.encryption_key,
            "txnDetails": txn_details,
            "pgDetails": {},  # Empty PG details
            "cardDetails": {},  # Empty card details
            "custDetails": cust_details,
            "billDetails": bill_details,
            "shipDetails": {},  # Empty shipping details
            "itemDetails": {},  # Empty item details
            "upiDetails": {},  # Empty UPI details
            "otherDetails": {},  # Empty other details
        }
        
        # Build hash data
        hash_data = {
            "merchantId": self.merchant_id,
            "merchantKey": self.encryption_key,
            "order_no": payment_request.transaction.order_no,
            "amount": str(payment_request.transaction.amount),
            "currencyFrom": payment_request.transaction.country,
            "currencyTo": payment_request.transaction.currency,
        }
        
        # Encrypt request and generate hash
        encrypted_request = self.crypto.build_encrypted_request(request_data)
        encrypted_hash = self.crypto.build_encrypted_hash(hash_data)
        
        return PaymentResponse(
            me_id=encrypted_request["me_id"],
            merchant_request=encrypted_request["merchant_request"],
            hash=encrypted_hash["hash"],
            post_url=self.post_url,
        )
    
    def create_payment_form(self, payment_request: PaymentRequest, form_id: str = "paymentForm") -> str:
        """
        Generate HTML form for payment redirection
        
        Args:
            payment_request: PaymentRequest object
            form_id: HTML form ID
            
        Returns:
            HTML string with auto-submitting form
        """
        payment_response = self.create_payment(payment_request)
        
        html = f"""
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting to Payment Gateway</title>
    <style>
        body {{
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }}
        .loading-container {{
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 400px;
        }}
        .spinner {{
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }}
        @keyframes spin {{
            0% {{ transform: rotate(0deg); }}
            100% {{ transform: rotate(360deg); }}
        }}
        .loading-text {{
            color: #333;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
        }}
        .loading-subtext {{
            color: #666;
            font-size: 14px;
        }}
    </style>
</head>
<body>
    <div class="loading-container">
        <div class="spinner"></div>
        <div class="loading-text">Redirecting to Payment Gateway</div>
        <div class="loading-subtext">Please wait while we process your request...</div>
    </div>
    
    <form name="{form_id}" method="POST" enctype="application/x-www-form-urlencoded" action="{payment_response.post_url}" style="display: none;">
        <input name="me_id" value="{payment_response.me_id}" type="hidden">
        <input name="merchant_request" value="{payment_response.merchant_request}" type="hidden">
        <input name="hash" value="{payment_response.hash}" type="hidden">
    </form>
    <script>
        // Auto-submit form after a brief delay to show loading
        setTimeout(function() {{
            document.forms["{form_id}"].submit();
        }}, 1500);
    </script>
</body>
</html>"""
        
        return html
    
    def verify_callback(self, callback_data: Dict[str, Any]) -> Optional[PaymentCallback]:
        """
        Verify and parse payment callback from gateway
        
        Args:
            callback_data: Dictionary containing callback data from gateway
            
        Returns:
            PaymentCallback object if verification successful, None otherwise
        """
        try:
            # Extract required fields
            order_no = callback_data.get("order_no")
            amount = callback_data.get("amount")
            status = callback_data.get("status")
            hash_value = callback_data.get("hash")
            merchant_request = callback_data.get("merchant_request")
            
            if not all([order_no, amount, status, hash_value, merchant_request]):
                return None
            
            # Verify hash
            response_data = {
                "order_no": order_no,
                "amount": amount,
                "status": status,
            }
            
            if not self.crypto.verify_response_hash(response_data, hash_value):
                return None
            
            # Decrypt merchant request to get additional details
            try:
                decrypted_request = self.crypto.aes_decrypt_base64(merchant_request)
                # You can parse the decrypted request for additional validation if needed
            except Exception:
                # If decryption fails, we can still proceed with basic verification
                pass
            
            return PaymentCallback(
                order_no=order_no,
                amount=amount,
                status=status,
                hash=hash_value,
                merchant_request=merchant_request,
            )
            
        except Exception:
            return None
    
    def generate_order_number(self, prefix: str = "ORDER") -> str:
        """
        Generate a unique order number
        
        Args:
            prefix: Prefix for the order number
            
        Returns:
            Unique order number string
        """
        import time
        import random
        
        timestamp = int(time.time() * 1000)
        random_suffix = random.randint(1000, 9999)
        return f"{prefix}_{timestamp}_{random_suffix}"
