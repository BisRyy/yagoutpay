"""
YagoutPay Python SDK

A modern Python SDK for integrating with the YagoutPay payment gateway.
"""

__version__ = "1.0.0"
__author__ = "YagoutPay Team"
__email__ = "support@yagoutpay.com"

from .client import YagoutPay
from .models import (
    PaymentRequest,
    PaymentResponse,
    CustomerDetails,
    TransactionDetails,
    BillingDetails,
)

__all__ = [
    "YagoutPay",
    "PaymentRequest",
    "PaymentResponse",
    "CustomerDetails",
    "TransactionDetails",
    "BillingDetails",
]
