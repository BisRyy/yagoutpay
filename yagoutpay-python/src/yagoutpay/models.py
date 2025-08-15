"""
Pydantic models for YagoutPay SDK
"""

from typing import Optional
from pydantic import BaseModel, Field, validator
import re


class CustomerDetails(BaseModel):
    """Customer details for payment"""
    
    cust_name: str = Field(..., description="Customer full name")
    email_id: str = Field(..., description="Customer email address")
    mobile_no: str = Field(..., description="Customer mobile number")
    unique_id: Optional[str] = Field(None, description="Unique customer identifier")
    is_logged_in: str = Field("Y", description="Login status")
    
    @validator('email_id')
    def validate_email(cls, v):
        if not re.match(r"[^@]+@[^@]+\.[^@]+", v):
            raise ValueError('Invalid email format')
        return v
    
    @validator('mobile_no')
    def validate_mobile(cls, v):
        # Enforce Ethiopian local format only: 09XXXXXXXX (10 digits)
        if not re.match(r'^09\d{8}$', v):
            raise ValueError('Invalid mobile number format. Use 09XXXXXXXX')
        return v


class BillingDetails(BaseModel):
    """Billing address details"""
    
    bill_address: Optional[str] = Field(None, description="Billing address")
    bill_city: Optional[str] = Field(None, description="Billing city")
    bill_state: Optional[str] = Field(None, description="Billing state")
    bill_country: Optional[str] = Field(None, description="Billing country")
    bill_zip: Optional[str] = Field(None, description="Billing zip code")


class TransactionDetails(BaseModel):
    """Transaction details for payment"""
    
    order_no: str = Field(..., description="Unique order number")
    amount: float = Field(..., gt=0, description="Payment amount")
    country: str = Field("ETH", description="Country code")
    currency: str = Field("ETB", description="Currency code")
    txn_type: str = Field("SALE", description="Transaction type")
    success_url: str = Field(..., description="Success callback URL")
    failure_url: str = Field(..., description="Failure callback URL")
    channel: str = Field("WEB", description="Payment channel")
    
    @validator('amount')
    def validate_amount(cls, v):
        if v <= 0:
            raise ValueError('Amount must be greater than 0')
        return v
    
    @validator('order_no')
    def validate_order_no(cls, v):
        if not v.strip():
            raise ValueError('Order number cannot be empty')
        return v.strip()


class PaymentRequest(BaseModel):
    """Complete payment request"""
    
    transaction: TransactionDetails
    customer: CustomerDetails
    billing: Optional[BillingDetails] = None
    
    class Config:
        json_schema_extra = {
            "example": {
                "transaction": {
                    "order_no": "ORDER_123456",
                    "amount": 1000.00,
                    "success_url": "https://example.com/success",
                    "failure_url": "https://example.com/failure"
                },
                "customer": {
                    "cust_name": "John Doe",
                    "email_id": "john@example.com",
                    "mobile_no": "+251911123456"
                },
                "billing": {
                    "bill_address": "123 Main St",
                    "bill_city": "Addis Ababa",
                    "bill_state": "Addis Ababa",
                    "bill_country": "Ethiopia"
                }
            }
        }


class PaymentResponse(BaseModel):
    """Payment gateway response"""
    
    me_id: str = Field(..., description="Merchant ID")
    merchant_request: str = Field(..., description="Encrypted merchant request")
    hash: str = Field(..., description="Request hash")
    post_url: str = Field(..., description="Payment gateway URL")
    
    class Config:
        json_schema_extra = {
            "example": {
                "me_id": "202508080001",
                "merchant_request": "encrypted_request_data",
                "hash": "request_hash_value",
                "post_url": "https://uatcheckout.yagoutpay.com/ms-transaction-core-1-0/paymentRedirection/checksumGatewayPage"
            }
        }


class PaymentCallback(BaseModel):
    """Payment callback from gateway"""
    
    order_no: str = Field(..., description="Order number")
    amount: str = Field(..., description="Payment amount")
    status: str = Field(..., description="Payment status")
    hash: str = Field(..., description="Response hash")
    merchant_request: str = Field(..., description="Encrypted response")
    
    class Config:
        json_schema_extra = {
            "example": {
                "order_no": "ORDER_123456",
                "amount": "1000.00",
                "status": "SUCCESS",
                "hash": "response_hash",
                "merchant_request": "encrypted_response_data"
            }
        }
