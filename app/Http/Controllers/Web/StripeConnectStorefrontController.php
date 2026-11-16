<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\StripeConnectConfigurationException;
use App\Http\Controllers\Controller;
use App\Models\StripeConnectedAccount;
use App\Services\StripeConnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

/**
 * Public per-connected-account Storefront & Direct Charge Checkout Controller.
 *
 * URL Identifier Notice (IMPORTANT FOR PRODUCTION):
 *   For simplicity in this sample integration, the storefront URL uses the
 *   connected account's Stripe Account ID directly: `/connect/storefront/{accountId}`
 *   (e.g. `/connect/storefront/acct_123...`).
 *
 *   TODO (Production): Do NOT expose raw Stripe Account IDs (`acct_...`) in
 *   public URLs in production. Instead, route by an internal public identifier
 *   such as `businesses.store_slug` (e.g. `/store/{slug}`) or a public UUID,
 *   and resolve the corresponding `stripe_account_id` server-side from the DB.
 */
class StripeConnectStorefrontController extends Controller
{
    public function __construct(
        private readonly StripeConnectService $stripeConnect,
    ) {}

    /**
     * Display the connected account's public storefront by listing active
     * products directly from Stripe using the `Stripe-Account` header.
     *
     * NOTE: Using `$accountId` (`acct_...`) directly in the URL is for demo
     * purposes only — replace with a non-Stripe public slug/UUID in production.
     */
    public function show(string $accountId): View
    {
        $record = StripeConnectedAccount::findByStripeAccountId($accountId);
        $products = [];
        $stripeError = null;

        try {
            // Retrieve products scoped to this connected account via `Stripe-Account` header.
            $products = $this->stripeConnect->listProducts($accountId, 20);
        } catch (StripeConnectConfigurationException|ApiErrorException $e) {
            $stripeError = $e->getMessage();
        }

        return view('stripe-connect.storefront', [
            'accountId' => $accountId,
            'merchantName' => $record?->display_name ?? 'Connected Storefront',
            'products' => $products,
            'stripeError' => $stripeError,
            'applicationFeeCents' => (int) config('stripe_connect.application_fee_amount', 123),
        ]);
    }

    /**
     * Start a Stripe Hosted Checkout Session using a **Direct Charge** on the
     * connected account (`['stripe_account' => $accountId]`) with a platform
     * `application_fee_amount` to monetize the transaction.
     */
    public function checkout(Request $request, string $accountId): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'string', 'max:120'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);

        try {
            // Stripe replaces `{CHECKOUT_SESSION_ID}` with the real cs_... ID on redirect.
            $successUrl = route('stripe-connect.checkout.success', [
                'accountId' => $accountId,
            ]).'?session_id={CHECKOUT_SESSION_ID}';

            $cancelUrl = route('stripe-connect.storefront.show', [
                'accountId' => $accountId,
            ]);

            $session = $this->stripeConnect->createDirectChargeCheckoutSession(
                $accountId,
                $validated['product_id'],
                $quantity,
                $successUrl,
                $cancelUrl,
            );

            if ($request->expectsJson()) {
                return response()->json(['url' => (string) $session->url]);
            }

            return redirect()->away((string) $session->url, 303);
        } catch (StripeConnectConfigurationException $e) {
            return $this->errorResponse($request, $e->getMessage(), 503);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($request, $e->getMessage(), 422);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Direct Charge checkout creation failed', [
                'account_id' => $accountId,
                'product_id' => $validated['product_id'],
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($request, 'Unable to start checkout: '.$e->getMessage(), 502);
        }
    }

    /**
     * Order confirmation page reached after a customer completes Direct Charge
     * Hosted Checkout (`?session_id=cs_...`).
     */
    public function success(Request $request, string $accountId): View
    {
        $record = StripeConnectedAccount::findByStripeAccountId($accountId);
        $sessionId = trim((string) $request->query('session_id', ''));
        $session = null;
        $stripeError = null;

        if ($sessionId !== '') {
            try {
                // Retrieve the Checkout Session from the connected account (`Stripe-Account` header).
                $session = $this->stripeConnect->retrieveDirectChargeCheckoutSession($accountId, $sessionId);
            } catch (StripeConnectConfigurationException|ApiErrorException $e) {
                $stripeError = $e->getMessage();
            }
        }

        return view('stripe-connect.checkout-success', [
            'accountId' => $accountId,
            'merchantName' => $record?->display_name ?? 'Connected Storefront',
            'sessionId' => $sessionId,
            'session' => $session,
            'stripeError' => $stripeError,
        ]);
    }

    private function errorResponse(Request $request, string $message, int $status): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], $status);
        }

        return redirect()->back()->with('error', $message);
    }
}
