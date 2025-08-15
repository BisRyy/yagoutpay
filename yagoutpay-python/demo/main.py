"""
FastAPI Demo for YagoutPay Python SDK
"""

import os
from typing import Dict, Any
from fastapi import FastAPI, Request, Form, HTTPException
from fastapi.responses import HTMLResponse, RedirectResponse
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates
from pydantic import BaseModel
from dotenv import load_dotenv

# Import YagoutPay SDK
import sys
sys.path.append(os.path.join(os.path.dirname(__file__), '..', 'src'))
from yagoutpay import YagoutPay, PaymentRequest, TransactionDetails, CustomerDetails, BillingDetails

# Load environment variables
load_dotenv()

# Initialize FastAPI app
app = FastAPI(
    title="RideYagout - Python SDK Demo",
    description="FastAPI demo for YagoutPay Python SDK with ride booking functionality",
    version="1.0.0"
)

# Mount static files
app.mount("/static", StaticFiles(directory="demo/static"), name="static")

# Templates
templates = Jinja2Templates(directory="demo/templates")

# Initialize YagoutPay client from environment (no hardcoded fallbacks)
MERCHANT_ID = os.getenv("MERCHANT_ID")
ENCRYPTION_KEY = os.getenv("ENCRYPTION_KEY")
ENVIRONMENT = os.getenv("ENVIRONMENT", "test")

if not MERCHANT_ID or not ENCRYPTION_KEY:
    raise RuntimeError(
        "MERCHANT_ID and ENCRYPTION_KEY must be provided via environment variables"
    )

yagoutpay = YagoutPay(
    merchant_id=MERCHANT_ID,
    encryption_key=ENCRYPTION_KEY,
    environment=ENVIRONMENT
)

# Base URL for callbacks
BASE_URL = os.getenv("BASE_URL", "http://localhost:8080")


class RideBookingRequest(BaseModel):
    """Ride booking request model"""
    customer_name: str
    email_id: str
    mobile_no: str
    pickup_address: str
    dropoff_address: str
    distance: float
    ride_type: str = "comfort"
    amount: float


@app.get("/", response_class=HTMLResponse)
async def home(request: Request):
    """Home page with ride booking form"""
    return templates.TemplateResponse("index.html", {"request": request})


@app.post("/pay")
async def book_ride(
    customer_name: str = Form(...),
    email_id: str = Form(...),
    mobile_no: str = Form(...),
    pickup_address: str = Form(...),
    dropoff_address: str = Form(...),
    distance: float = Form(...),
    ride_type: str = Form("comfort"),
    amount: float = Form(...)
):
    """Process ride booking and redirect to payment"""
    
    # Generate order number
    order_no = yagoutpay.generate_order_number("RIDE")
    
    # Create payment request
    payment_request = PaymentRequest(
        transaction=TransactionDetails(
            order_no=order_no,
            amount=amount,
            success_url=f"{BASE_URL}/success?order_no={order_no}&ride_type={ride_type}&amount={amount}",
            failure_url=f"{BASE_URL}/failure?order_no={order_no}&reason=payment_failed"
        ),
        customer=CustomerDetails(
            cust_name=customer_name,
            email_id=email_id,
            mobile_no=mobile_no
        ),
        billing=BillingDetails(
            bill_address=pickup_address,
            bill_city="Addis Ababa",
            bill_state="Addis Ababa",
            bill_country="Ethiopia"
        )
    )
    
    # Generate payment form
    payment_form = yagoutpay.create_payment_form(payment_request)
    
    return HTMLResponse(content=payment_form)


@app.api_route("/success", methods=["GET", "POST"], response_class=HTMLResponse)
async def success(request: Request):
    if request.method == "POST":
        form = await request.form()
        order_no = form.get("order_no") or form.get("ORDER_NO") or ""
        amount = form.get("amount") or form.get("AMOUNT") or ""
        ride_type = form.get("ride_type") or form.get("RIDE_TYPE") or "Comfort"
        params = []
        if order_no:
            params.append(f"order_no={order_no}")
        if ride_type:
            params.append(f"ride_type={ride_type}")
        if amount:
            params.append(f"amount={amount}")
        query = ("?" + "&".join(params)) if params else ""
        return RedirectResponse(url=f"/success{query}", status_code=302)

    order_no = request.query_params.get("order_no", "N/A")
    ride_type = request.query_params.get("ride_type", "Comfort")
    amount = request.query_params.get("amount", "0")

    return templates.TemplateResponse("success.html", {
        "request": request,
        "order_no": order_no,
        "ride_type": ride_type,
        "amount": amount
    })


@app.api_route("/failure", methods=["GET", "POST"], response_class=HTMLResponse)
async def failure(request: Request):
    if request.method == "POST":
        form = await request.form()
        order_no = form.get("order_no") or form.get("ORDER_NO") or ""
        reason = form.get("reason") or form.get("STATUS") or form.get("status") or "payment_failed"
        params = []
        if order_no:
            params.append(f"order_no={order_no}")
        if reason:
            params.append(f"reason={reason}")
        query = ("?" + "&".join(params)) if params else ""
        return RedirectResponse(url=f"/failure{query}", status_code=302)

    order_no = request.query_params.get("order_no", "N/A")
    reason = request.query_params.get("reason", "Payment failed")

    return templates.TemplateResponse("failure.html", {
        "request": request,
        "order_no": order_no,
        "reason": reason
    })


@app.post("/callback")
async def payment_callback(request: Request):
    """Handle payment callback from YagoutPay"""
    form_data = await request.form()
    callback_data = dict(form_data)
    
    # Verify callback
    payment_callback = yagoutpay.verify_callback(callback_data)
    
    if payment_callback:
        # Payment verified successfully
        if payment_callback.status.upper() == "SUCCESS":
            return RedirectResponse(
                url=f"/success?order_no={payment_callback.order_no}&amount={payment_callback.amount}",
                status_code=302
            )
        else:
            return RedirectResponse(
                url=f"/failure?order_no={payment_callback.order_no}&reason={payment_callback.status}",
                status_code=302
            )
    else:
        # Invalid callback
        return RedirectResponse(
            url="/failure?reason=invalid_callback",
            status_code=302
        )


@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "service": "RideYagout Python SDK Demo",
        "version": "1.0.0"
    }


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
