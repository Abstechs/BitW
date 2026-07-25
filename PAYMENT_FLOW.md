# BitW Payment Flow Documentation

## Overview
The payment system is now fully wired to support Paystack payments and manual deposits with admin approval. Here's what has been implemented:

## Payment Methods

### 1. Paystack Payment (Automatic)
- **File**: `api/paystack-initialize.php` - Initializes payment with Paystack
- **Webhook**: `api/paystack-webhook.php` - Processes successful payments automatically
- **Verification**: `api/paystack-verify.php` - Verifies payment after redirect
- **Status**: Wallet is credited immediately upon successful payment

**Flow**:
1. User enters amount in dashboard
2. System initializes Paystack payment with user details
3. User redirected to Paystack payment page
4. After payment, Paystack sends webhook notification
5. Webhook verifies and credits wallet automatically

### 2. Manual Deposit (Admin Approval)
- **File**: `api/manual-deposit.php` - Handles deposit submissions
- **Approval**: `public/admin/deposits.php` - Admin approval interface
- **Status**: Wallet is credited only after admin approval
- **Proof Storage**: Deposits proofs stored in `public/assets/deposit-proofs/`

**Flow**:
1. User enters amount and uploads proof of deposit
2. System stores transaction as 'pending'
3. Admin reviews deposit in admin panel
4. Admin approves or rejects
5. Upon approval, wallet is automatically credited

## Configuration

All payment settings are stored in `config/settings.php`:

```php
'PAYSTACK_SECRET' => 'sk_test_...',        // Paystack Secret Key
'PAYSTACK_PUBLIC' => 'pk_test_...',        // Paystack Public Key
'PAYSTACK_DEFAULT_ACCOUNT' => '...',       // Default account for display
'PAYSTACK_DEFAULT_BANK' => '...',          // Default bank name
'PAYSTACK_DEFAULT_ACCOUNT_NAME' => '...',  // Account holder name
'MANUAL_DEPOSIT_ENABLED' => true,          // Enable/disable manual deposits
'CRYPTO_DEPOSIT_ENABLED' => false,         // Future: Crypto support
'DEFAULT_PLAN_IMAGE' => '...',             // Default plan image
```

**Admin can update these settings via**: `public/admin/settings.php`

## Database Schema

### New/Updated Tables

#### transactions table (status field extended)
```sql
status ENUM('pending', 'completed', 'failed', 'rejected')
```

#### plans table (new columns)
```sql
max_purchase_attempts INT DEFAULT 1  -- Limit purchases per user
image VARCHAR(255)                    -- Plan image path
```

**Run migration**: `mysql -u user -p database < schema-migrations.sql`

## User-Facing Flow

### Dashboard Wallet Section
Located at `public/pages/dashboard/wallet-actions.php`:

1. **Paystack Tab**
   - User enters amount (₦)
   - Clicks "Fund with Paystack"
   - Redirected to Paystack payment page
   - After successful payment, wallet updated

2. **Manual Deposit Tab**
   - User enters amount (₦)
   - Uploads proof of payment (JPG, PNG, GIF, PDF)
   - Submits for approval
   - Status: "Pending admin approval"

3. **Withdraw Tab** (existing)
   - User initiates withdrawal request
   - Minimum amount enforced

## Admin Interface

### Deposits Management
Located at: `public/admin/deposits.php`

Features:
- View all pending deposits
- View payment proof (clickable link)
- Approve/Reject with one click
- Automatic wallet credit upon approval
- Transaction history shows completed/rejected deposits

**Navigation**: Admin Dashboard → Deposits menu

### Settings Management
Located at: `public/admin/settings.php`

Features:
- Update Paystack API keys
- Set manual deposit account details
- Configure default plan image
- Enable/disable payment methods

## API Endpoints

### POST `/api/paystack-initialize.php`
Initializes a Paystack payment
- **Parameters**: `amount` (₦)
- **Returns**: `authorization_url`, `access_code`, `reference`
- **Response**: JSON with status and redirect URL

### POST `/api/paystack-webhook.php`
Receives Paystack webhook notifications
- **Signature**: Verified using `x-paystack-signature` header
- **Action**: Credits wallet on successful charge
- **Logs**: Webhook events logged to `logs/paystack.log`

### GET `/api/paystack-verify.php`
Verifies payment after redirect
- **Parameters**: `reference` (query string)
- **Action**: Verifies with Paystack, redirects to dashboard

### POST `/api/manual-deposit.php`
Submits manual deposit request
- **Parameters**: `amount` (₦), `proof` (file)
- **Returns**: JSON with reference number
- **Action**: Creates pending transaction with proof

## Payment Flow Integration

### Core Functions (core/plans.php)
- `countUserPlanPurchases()` - Track user purchases
- `canPurchasePlan()` - Check purchase limits
- `purchasePlan()` - Process plan purchase with wallet debit

### Core Functions (core/wallet.php)
- `getWallet()` - Get current balance
- `creditWallet()` - Add funds
- `debitWallet()` - Deduct funds
- `logTransaction()` - Log transaction
- `getTransactions()` - Get transaction history

## Testing Checklist

- [ ] Set Paystack keys in `config/settings.php`
- [ ] Enable manual deposit in settings
- [ ] Run schema migrations
- [ ] Test Paystack payment (will test charge on test account)
- [ ] Test manual deposit submission
- [ ] Test admin approval workflow
- [ ] Verify wallet credit
- [ ] Check transaction logs

## Security Notes

1. **Paystack Webhook Verification**: All webhooks verified using HMAC-SHA512
2. **Admin Authentication**: All admin pages require `is_admin = 1`
3. **User Authentication**: All user payment endpoints require active session
4. **File Upload Security**: Only allowed file types accepted for deposit proofs
5. **Transaction Atomicity**: Use database transactions for wallet operations

## File Locations

```
bitw/
├── api/
│   ├── paystack-initialize.php    # Paystack init
│   ├── paystack-webhook.php       # Paystack webhook handler
│   ├── paystack-verify.php        # Payment verification
│   └── manual-deposit.php         # Manual deposit handler
├── config/
│   └── settings.php               # Payment config
├── logs/
│   └── paystack.log              # Webhook logs
├── public/
│   ├── admin/
│   │   ├── deposits.php          # Admin approval interface
│   │   ├── settings.php          # Admin settings
│   │   └── includes/
│   │       └── admin_header.php  # Admin nav (includes deposits link)
│   ├── assets/
│   │   └── deposit-proofs/       # User proof uploads
│   ├── pages/
│   │   └── dashboard/
│   │       └── wallet-actions.php # User deposit UI
│   └── dashboard.php             # User dashboard
└── schema-migrations.sql          # Database updates
```

## Next Steps

1. Configure Paystack keys in admin settings
2. Enable/disable manual deposits as needed
3. Test payment flows thoroughly
4. Monitor webhook logs for issues
5. Train admins on approval workflow

## Support & Troubleshooting

### Paystack Payment Not Working
- Check Paystack keys in settings
- Verify webhook URL is accessible
- Check `logs/paystack.log` for errors

### Manual Deposit Issues
- Ensure `public/assets/deposit-proofs/` is writable
- Check file upload permissions
- Verify proof file format

### Wallet Not Crediting
- Check transaction status in database
- Verify webhook was received
- Check for database errors in logs
