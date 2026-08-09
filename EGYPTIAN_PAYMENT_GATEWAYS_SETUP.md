# Egyptian Payment Gateways Setup Guide

## Overview
This document explains how to set up and use the Egyptian payment gateway system in the Z-Syst Pharmacy Management System. The system supports multi-tenant payment processing with the following Egyptian payment methods:

- **Vodafone Cash** - Mobile wallet payment
- **Bank Card** - Credit/Debit card payments (supports Meeza, Visa, Mastercard)
- **Fawry** - Egyptian payment network
- **Orange Cash** - Mobile wallet payment
- **InstaPay** - Instant payment system
- **Cash** - Manual cash payment with change calculation

## Architecture

### Multi-Tenant Support
The system is designed for multi-tenant pharmacy management:
- **Company-level configuration**: Default payment gateway settings for entire company
- **Branch-level overrides**: Specific payment gateway settings per branch
- **Transaction isolation**: All transactions are tracked with company_id and branch_id
- **Flexible fees**: Transaction fees can be set as percentage or fixed amount

### Database Structure
- `company_payment_gateways` - Stores payment gateway configurations per company/branch
- `payment_transactions` - Records all payment transactions with full audit trail
- `branches` - Branch management for multi-location pharmacies

## Installation Steps

### 1. Run Database Migrations
```bash
php artisan migrate
```

This will create the necessary tables:
- `company_payment_gateways`
- `payment_transactions`
- `branches`

### 2. Configure Environment Variables
Add the following to your `.env` file:

```env
# Vodafone Cash
VODAFONE_CASH_MERCHANT_ID=your_merchant_id
VODAFONE_CASH_API_KEY=your_api_key
VODAFONE_CASH_API_SECRET=your_api_secret
VODAFONE_CASH_ENVIRONMENT=sandbox

# Bank Card (Payment Provider)
BANK_CARD_MERCHANT_ID=your_merchant_id
BANK_CARD_API_KEY=your_api_key
BANK_CARD_API_SECRET=your_api_secret
BANK_CARD_ENVIRONMENT=sandbox
BANK_CARD_ACCEPT_MEEZA=false
BANK_CARD_ACCEPT_VISAMASTERCARD=true

# Fawry
FAWRY_MERCHANT_CODE=your_merchant_code
FAWRY_SECURITY_KEY=your_security_key
FAWRY_ENVIRONMENT=sandbox

# Orange Cash
ORANGE_CASH_MERCHANT_ID=your_merchant_id
ORANGE_CASH_API_KEY=your_api_key
ORANGE_CASH_API_SECRET=your_api_secret
ORANGE_CASH_ENVIRONMENT=sandbox

# InstaPay
INSTAPAY_MERCHANT_ID=your_merchant_id
INSTAPAY_API_KEY=your_api_key
INSTAPAY_API_SECRET=your_api_secret
INSTAPAY_ENVIRONMENT=sandbox

# Cash Payment Settings
CASH_REQUIRE_VERIFICATION=true
CASH_ALLOW_PARTIAL_PAYMENT=false
CASH_AUTO_COMPLETE=true
```

### 3. Configure Payment Gateways via Admin Panel

1. Navigate to: `/admin/payment-gateways`
2. Click "Add Gateway"
3. Select Company and Branch (optional - leave blank for all branches)
4. Choose Gateway Type
5. Fill in required configuration fields
6. Set transaction fees (optional)
7. Save the configuration

## Usage

### For Subscription Payments

1. Users can select from available payment gateways during subscription
2. Egyptian payment gateways appear alongside manual payment options
3. Each gateway shows applicable fees and total amount
4. Payment processing is handled automatically

### For POS (Point of Sale) Payments

#### API Endpoints

**Get Available Gateways**
```
GET /api/v1/payments/gateways?company_id={id}&branch_id={id}
```

**Process Payment**
```
POST /api/v1/payments/process
{
  "gateway_id": 1,
  "amount": 150.50,
  "customer_phone": "01012345678",
  "customer_email": "customer@example.com",
  "invoice_id": 123,
  "card_data": { // Only for bank card
    "card_number": "4111111111111111",
    "card_holder": "John Doe",
    "expiry_month": "12",
    "expiry_year": "2025",
    "cvv": "123"
  },
  "received_amount": 200.00, // Only for cash
  "payment_notes": "Payment notes"
}
```

**Verify Payment**
```
POST /api/v1/payments/verify
{
  "transaction_id": 123
}
```

**Process Refund**
```
POST /api/v1/payments/refund
{
  "transaction_id": 123,
  "amount": 150.50,
  "reason": "Customer requested refund"
}
```

**Calculate Change (Cash)**
```
POST /api/v1/payments/calculate-change
{
  "amount": 150.50,
  "received_amount": 200.00
}
```

**Get Payment Statistics**
```
GET /api/v1/payments/stats?company_id={id}&branch_id={id}&date_from={date}&date_to={date}
```

## Payment Gateway Classes

### BasePaymentGateway
Abstract base class that all payment gateways extend. Provides:
- Configuration management
- Transaction creation
- Fee calculation
- Logging
- Error handling

### VodafoneCashGateway
- Processes mobile wallet payments via Vodafone Cash API
- Requires customer phone number
- Supports payment verification and refunds

### BankCardGateway
- Processes credit/debit card payments
- Supports Meeza, Visa, Mastercard
- Requires card details (number, holder, expiry, CVV)
- Integrates with payment providers like Paymob, Fawry Pay

### FawryGateway
- Processes payments via Fawry network
- Supports payment at Fawry outlets
- Uses signature-based authentication

### OrangeCashGateway
- Processes mobile wallet payments via Orange Cash API
- Requires customer phone number
- Similar structure to Vodafone Cash

### InstaPayGateway
- Processes instant payments via InstaPay
- Supports bank account transfers
- Requires customer phone number

### CashGateway
- Handles manual cash payments
- Calculates change automatically
- Supports partial payments (configurable)
- Requires manager verification (configurable)

## Configuration Management

### Gateway Configuration Fields

Each gateway type has specific required fields:

**Vodafone Cash:**
- merchant_id
- api_key
- api_secret
- environment (sandbox/production)

**Bank Card:**
- merchant_id
- api_key
- api_secret
- environment (sandbox/production)
- accept_meeza (boolean)
- accept_visamastercard (boolean)

**Fawry:**
- merchant_code
- security_key
- environment (sandbox/production)

**Orange Cash:**
- merchant_id
- api_key
- api_secret
- environment (sandbox/production)

**InstaPay:**
- merchant_id
- api_key
- api_secret
- environment (sandbox/production)

**Cash:**
- require_verification (boolean)
- allow_partial_payment (boolean)
- auto_complete (boolean)

### Fee Structure

Transaction fees can be configured as:
- **Percentage**: e.g., 2.5% of transaction amount
- **Fixed**: e.g., 5 EGP per transaction

Fees are calculated automatically during payment processing.

## Transaction Management

### Transaction Status Flow
1. **Pending** - Transaction initiated, awaiting completion
2. **Completed** - Payment successfully processed
3. **Failed** - Payment failed or rejected
4. **Refunded** - Transaction refunded

### Transaction Tracking
Each transaction includes:
- Internal reference (auto-generated)
- External reference (from payment gateway)
- Amount and currency
- Customer information
- Payment gateway response data
- Processing metadata
- Timestamps for status changes

## Security Considerations

1. **API Credentials**: Store in environment variables, never in code
2. **Transaction Isolation**: Multi-tenant data separation enforced
3. **Audit Trail**: All transactions logged with user context
4. **Error Handling**: Graceful failure handling with user feedback
5. **Validation**: Input validation for all payment data

## Testing

### Sandbox Environment
All gateways support sandbox/test mode for development:
- Set `environment` to `sandbox` in configuration
- Use test credentials provided by payment providers
- Test payment flows without real money

### Manual Testing
1. Configure a gateway in sandbox mode
2. Process a test payment
3. Verify transaction status
4. Test refund process
5. Check transaction statistics

## Troubleshooting

### Common Issues

**Payment Gateway Not Appearing**
- Check if gateway is active in admin panel
- Verify company_id and branch_id match current context
- Ensure gateway configuration is valid

**Payment Processing Failed**
- Check payment gateway logs
- Verify API credentials are correct
- Ensure sandbox/production mode matches configuration
- Check network connectivity to payment gateway APIs

**Transaction Status Stuck on Pending**
- Use verify payment endpoint to check status
- Check webhook callbacks are working
- Verify payment gateway service status

## Integration with Existing Features

### Subscription Flow
- Integrated with existing subscription system
- Supports both Egyptian gateways and legacy manual payments
- Automatic subscription activation on successful payment

### POS Integration
- API endpoints for POS payment processing
- Real-time payment verification
- Change calculation for cash payments
- Transaction statistics for reporting

### Invoice System
- Links payments to invoices
- Supports partial payments
- Automatic invoice status updates

## API Response Examples

### Successful Payment Response
```json
{
  "success": true,
  "transaction_id": 123,
  "reference_id": "VOD-123456789",
  "internal_reference": "TXN-ABC123-1234567890",
  "status": "completed",
  "status_label": "Completed",
  "amount": 150.50,
  "message": "Payment processed successfully"
}
```

### Error Response
```json
{
  "success": false,
  "message": "Payment gateway is not active"
}
```

## Support and Maintenance

### Regular Tasks
- Monitor transaction success rates
- Review payment gateway service status
- Update API credentials as needed
- Audit transaction logs for anomalies
- Backup transaction data regularly

### Updates
- Keep payment gateway SDKs updated
- Monitor for API changes from payment providers
- Test new sandbox features before production deployment

## Contact Support
For issues related to:
- Payment gateway configuration: Contact system administrator
- API integration issues: Contact development team
- Payment provider problems: Contact respective payment provider support

---

*Note: This payment gateway system is designed for Egyptian pharmacies and supports the most common Egyptian payment methods. Additional payment gateways can be added by extending the BasePaymentGateway class.*