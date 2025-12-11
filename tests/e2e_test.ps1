# Wallet Service End-to-End Test Script
# This script automates the full flow: Reset DB -> Seed -> Test API -> Verify

$ErrorActionPreference = "Stop"
$BaseUrl = "http://localhost:8000/api"

Write-Host ">>> STARTING END-TO-END TEST..." -ForegroundColor Cyan

# 1. Reset Database and Seed
Write-Host "`n[1/6] Resetting Database & Seeding..." -ForegroundColor Yellow
$seedOutput = php artisan migrate:fresh --seed 2>&1 | Out-String

# 2. Capture API Keys
Write-Host "`n[2/6] Capturing API Keys..." -ForegroundColor Yellow
$senderKey = $null
$receiverKey = $null

if ($seedOutput -match "(?s)User 1 \(Sender\).*?API Key: (sk_live_\w+)") {
    $senderKey = $matches[1].Trim()
    Write-Host "Captured Sender Key: $senderKey" -ForegroundColor Green
} else {
    Write-Error "Failed to capture Sender API Key from seeder output."
}

if ($seedOutput -match "(?s)User 2 \(Receiver\).*?API Key: (sk_live_\w+)") {
    $receiverKey = $matches[1].Trim()
    Write-Host "Captured Receiver Key: $receiverKey" -ForegroundColor Green
} else {
    Write-Error "Failed to capture Receiver API Key from seeder output."
}

# Helper for API Calls
function Invoke-ApiRequest {
    param (
        [string]$Method,
        [string]$Uri,
        [string]$Token,
        [hashtable]$Body = $null
    )
    
    $headers = @{
        "Accept" = "application/json"
        "x-api-key" = $Token
    }

    try {
        if ($Body) {
            $jsonBody = $Body | ConvertTo-Json
            $response = Invoke-RestMethod -Method $Method -Uri "$BaseUrl$Uri" -Headers $headers -Body $jsonBody -ContentType "application/json"
        } else {
            $response = Invoke-RestMethod -Method $Method -Uri "$BaseUrl$Uri" -Headers $headers
        }
        return $response
    } catch {
        Write-Error "API Request Failed: $Uri `nError: $($_.Exception.Message) `nResponse: $($_.ErrorDetails.Message)"
    }
}

# 3. Get Receiver Wallet Number
Write-Host "`n[3/6] Getting Receiver Wallet Number..." -ForegroundColor Yellow
$receiverBalance = Invoke-ApiRequest -Method Get -Uri "/wallet/balance" -Token $receiverKey
$receiverWallet = $receiverBalance.data.wallet_number
Write-Host "Receiver Wallet: $receiverWallet" -ForegroundColor Green

# 4. Fund Sender Wallet (Simulated)
Write-Host "`n[4/6] Funding Sender Wallet..." -ForegroundColor Yellow
# 4a. Initialize Deposit
$depositAmount = 5000
$depositResponse = Invoke-ApiRequest -Method Post -Uri "/wallet/deposit" -Token $senderKey -Body @{ amount = $depositAmount }
$reference = $depositResponse.data.reference
Write-Host "Deposit Initialized. Reference: $reference" -ForegroundColor Green

# 4b. Simulate Paystack Webhook
Write-Host "Simulating Paystack Webhook..." -ForegroundColor Yellow

# Get Secret for Signature
$webhookSecret = php artisan tinker --execute="echo config('services.paystack.webhook_secret');"
$webhookSecret = $webhookSecret.Trim()

if (-not $webhookSecret -or $webhookSecret -eq "") {
    Write-Warning "Could not fetch PAYSTACK_WEBHOOK_SECRET using tinker. Using default 'secret'."
    $webhookSecret = "secret"
}

$webhookBody = @{
    event = "charge.success"
    data = @{
        reference = $reference
        amount = $depositAmount * 100 # Paystack expects Kobo in webhook
        currency = "NGN"
        status = "success"
    }
}
$webhookJson = $webhookBody | ConvertTo-Json -Depth 5
# PowerShell's ComputeHash
$hmac = [System.Security.Cryptography.HMACSHA512]::new([System.Text.Encoding]::UTF8.GetBytes($webhookSecret))
$hashBytes = $hmac.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($webhookJson))
$signature = ($hashBytes | ForEach-Object { $_.ToString("x2") }) -join ""

$webhookHeaders = @{
    "x-paystack-signature" = $signature
    "Content-Type" = "application/json"
}

try {
    Invoke-RestMethod -Method Post -Uri "$BaseUrl/wallet/paystack/webhook" -Headers $webhookHeaders -Body $webhookJson
    Write-Host "Webhook Simulated Successfully." -ForegroundColor Green
} catch {
    Write-Error "Webhook Simulation Failed: $($_.Exception.Message) `nResponse: $($_.ErrorDetails.Message)"
}

# Verify Funding
$senderBalance = Invoke-ApiRequest -Method Get -Uri "/wallet/balance" -Token $senderKey
Write-Host "Sender Balance after Funding: $($senderBalance.data.balance)" -ForegroundColor Green

# 5. Transfer Money
Write-Host "`n[5/6] Transferring Money..." -ForegroundColor Yellow
$transferAmount = 1000
$transferResponse = Invoke-ApiRequest -Method Post -Uri "/wallet/transfer" -Token $senderKey -Body @{
    wallet_number = $receiverWallet
    amount = $transferAmount
}

if ($transferResponse.success) {
    Write-Host "Transfer Successful: $($transferResponse.message)" -ForegroundColor Green
} else {
    Write-Error "Transfer Failed"
}

# 6. Verify Final State
Write-Host "`n[6/6] Verifying Final Balances..." -ForegroundColor Yellow
$senderFinal = Invoke-ApiRequest -Method Get -Uri "/wallet/balance" -Token $senderKey
$receiverFinal = Invoke-ApiRequest -Method Get -Uri "/wallet/balance" -Token $receiverKey

Write-Host "Sender Final Balance: $($senderFinal.data.balance) (Expected: $(5000 - 1000))"
Write-Host "Receiver Final Balance: $($receiverFinal.data.balance) (Expected: $transferAmount)"

if ([double]$senderFinal.data.balance -eq 4000 -and [double]$receiverFinal.data.balance -eq 1000) {
    Write-Host "`n>>> TEST PASSED SUCCESSFULLY <<<" -ForegroundColor Cyan
} else {
    Write-Host "`n>>> TEST FAILED: Balances do not match expected values <<<" -ForegroundColor Red
}
