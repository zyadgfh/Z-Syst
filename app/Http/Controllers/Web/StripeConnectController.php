<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\StripeConnectConfigurationException;
use App\Http\Controllers\Controller;
use App\Models\StripeConnectedAccount;
use App\Services\StripeConnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

/**
 * Sample Stripe Connect Dashboard Controller.
 *
 * Demonstrates the complete merchant-side Connect lifecycle:
 *   1. Creating a Stripe V2 Connected Account and mapping it to the local User in DB.
 *   2. Onboarding the connected account with V2 Account Links and reading live
 *      onboarding/capability status directly from Stripe's V2 Accounts API
 *      (never cached in the database).
 *   3. Creating Products on the connected account via the `Stripe-Account` header.
 *   4. Charging a platform-level SaaS Subscription to the connected account using
 *      its V2 Account ID as `customer_account` (never `.customer`), plus launching
 *      the Stripe Customer Billing Portal.
 */
class StripeConnectController extends Controller
{
    public function __construct(
        private readonly StripeConnectService $stripeConnect,
    ) {}

    /**
     * Render the Stripe Connect Dashboard.
     *
     * Accepts `?accountId=acct_...` (which Stripe's Account Link `return_url`
     * appends after onboarding) so the user lands right back on their account
     * with freshly queried live onboarding status.
     */
    public function index(Request $request): View
    {
        $accounts = $this->accessibleAccounts($request);
        $requestedAccountId = trim((string) $request->query('accountId', ''));

        /** @var StripeConnectedAccount|null $selectedAccount */
        $selectedAccount = $requestedAccountId !== ''
            ? $accounts->firstWhere('stripe_account_id', $requestedAccountId)
            : $accounts->first();

        $liveStatus = null;
        $products = [];
        $stripeError = null;

        if ($selectedAccount !== null) {
            try {
                // ALWAYS fetch account onboarding status directly from Stripe's
                // V2 Accounts API — never store or serve stale onboarding status from DB.
                $liveStatus = $this->stripeConnect->getAccountOnboardingStatus($selectedAccount->stripe_account_id);

                // Fetch active products on the connected account (`Stripe-Account` header).
                $products = $this->stripeConnect->listProducts($selectedAccount->stripe_account_id, 20);
            } catch (StripeConnectConfigurationException|ApiErrorException $e) {
                $stripeError = $e->getMessage();
            }
        }

        $checklist = $this->stripeConnect->configurationChecklist();
        $user = Auth::user();

        return view('stripe-connect.dashboard', [
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'liveStatus' => $liveStatus,
            'products' => $products,
            'stripeError' => $stripeError,
            'checklist' => $checklist,
            'allConfigured' => collect($checklist)->every(fn (array $item): bool => $item['configured']),
            'defaultDisplayName' => $user?->business?->company_name ?? $user?->name ?? '',
            'defaultContactEmail' => $user?->email ?? '',
            'defaultCurrency' => config('stripe_connect.default_currency', 'usd'),
            'applicationFeeCents' => (int) config('stripe_connect.application_fee_amount', 123),
            'isAuthenticated' => $user !== null,
            'onboardingRefreshed' => $request->boolean('refresh'),
            'onboardingReturned' => $request->filled('accountId') && ! $request->boolean('refresh'),
            'subscriptionNotice' => $request->query('subscription'),
        ]);
    }

    /**
     * Step 1: Create a V2 Connected Account and persist the user -> account ID
     * mapping in `stripe_connected_accounts`.
     */
    public function createAccount(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:190'],
        ]);

        try {
            // Create the account via Stripe's V2 Core Accounts API (never passing top-level `type`).
            $stripeAccount = $this->stripeConnect->createConnectedAccount(
                $validated['display_name'],
                $validated['contact_email'],
            );

            $user = Auth::user();

            // Persist the mapping from the local Z-Syst user (UUID) to the Stripe V2 Account ID.
            $record = StripeConnectedAccount::create([
                'user_id' => $user?->getKey(),
                'business_id' => $user?->business_id,
                'stripe_account_id' => $stripeAccount->id,
                'display_name' => $validated['display_name'],
                'contact_email' => $validated['contact_email'],
            ]);

            // If running in unauthenticated demo mode, remember this account in the
            // visitor's own session so they can manage it without exposing other accounts.
            if ($user === null) {
                $demoIds = (array) $request->session()->get('stripe_connect_demo_account_ids', []);
                $demoIds[] = $record->stripe_account_id;
                $request->session()->put('stripe_connect_demo_account_ids', array_values(array_unique($demoIds)));
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'account_id' => $record->stripe_account_id,
                    'display_name' => $record->display_name,
                    'redirect_url' => route('stripe-connect.dashboard', ['accountId' => $record->stripe_account_id]),
                ], 201);
            }

            return redirect()
                ->route('stripe-connect.dashboard', ['accountId' => $record->stripe_account_id])
                ->with('status', "Connected account created ({$record->stripe_account_id}). Click \"Onboard to collect payments\" to finish setup.");
        } catch (StripeConnectConfigurationException $e) {
            return $this->errorResponse($request, $e->getMessage(), 503);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Connect account creation failed', ['error' => $e->getMessage()]);

            return $this->errorResponse($request, 'Stripe API error while creating connected account: '.$e->getMessage(), 502);
        }
    }

    /**
     * Step 2: Create a V2 Account Link (`account_onboarding`) and send the user
     * to Stripe's hosted onboarding flow.
     */
    public function onboard(Request $request, string $accountId): RedirectResponse|JsonResponse
    {
        $record = $this->findAuthorizedAccountOrFail($request, $accountId);

        try {
            // `refresh_url`: Stripe redirects here if the single-use link expires.
            $refreshUrl = route('stripe-connect.dashboard', [
                'accountId' => $record->stripe_account_id,
                'refresh' => 1,
            ]);

            // `return_url`: Stripe redirects here when onboarding completes or pauses.
            // Includes `?accountId=...` so the dashboard reloads fresh live status.
            $returnUrl = route('stripe-connect.dashboard', [
                'accountId' => $record->stripe_account_id,
            ]);

            $accountLink = $this->stripeConnect->createOnboardingAccountLink(
                $record->stripe_account_id,
                $refreshUrl,
                $returnUrl,
            );

            return $this->stripeRedirectResponse($request, (string) $accountLink->url);
        } catch (StripeConnectConfigurationException $e) {
            return $this->errorResponse($request, $e->getMessage(), 503);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Connect Account Link creation failed', [
                'account_id' => $record->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($request, 'Could not create onboarding link: '.$e->getMessage(), 502);
        }
    }

    /**
     * Step 2b: JSON endpoint that retrieves the live onboarding & capability
     * status directly from Stripe's V2 Accounts API (never from the DB).
     */
    public function accountStatus(Request $request, string $accountId): JsonResponse
    {
        $record = $this->findAuthorizedAccountOrFail($request, $accountId);

        try {
            $status = $this->stripeConnect->getAccountOnboardingStatus($record->stripe_account_id);

            return response()->json([
                'account_id' => $status['account_id'],
                'display_name' => $status['display_name'],
                'card_payments_status' => $status['card_payments_status'],
                'ready_to_process_payments' => $status['ready_to_process_payments'],
                'requirements_status' => $status['requirements_status'],
                'onboarding_complete' => $status['onboarding_complete'],
                'requirements_entries' => $status['requirements_entries'],
            ]);
        } catch (StripeConnectConfigurationException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        } catch (ApiErrorException $e) {
            return response()->json(['error' => $e->getMessage()], 502);
        }
    }

    /**
     * Step 3: Create a Stripe Product on the connected account using the
     * `Stripe-Account` header (`['stripe_account' => $accountId]`).
     */
    public function createProduct(Request $request, string $accountId): RedirectResponse|JsonResponse
    {
        $record = $this->findAuthorizedAccountOrFail($request, $accountId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
            // Accept either human-friendly decimal `price` (e.g. "19.99") or integer `price_in_cents`.
            'price' => ['required_without:price_in_cents', 'nullable', 'numeric', 'min:0.50', 'max:999999.99'],
            'price_in_cents' => ['required_without:price', 'nullable', 'integer', 'min:50', 'max:99999999'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
        ]);

        $priceInCents = isset($validated['price_in_cents'])
            ? (int) $validated['price_in_cents']
            : (int) round(((float) $validated['price']) * 100);

        try {
            $product = $this->stripeConnect->createProduct(
                $record->stripe_account_id,
                $validated['name'],
                $validated['description'] ?? null,
                $priceInCents,
                strtolower($validated['currency']),
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'default_price' => $product->default_price,
                ], 201);
            }

            return redirect()
                ->route('stripe-connect.dashboard', ['accountId' => $record->stripe_account_id])
                ->with('status', "Product \"{$product->name}\" created on connected account {$record->stripe_account_id}.");
        } catch (StripeConnectConfigurationException $e) {
            return $this->errorResponse($request, $e->getMessage(), 503);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Connect product creation failed', [
                'account_id' => $record->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($request, 'Stripe API error while creating product: '.$e->getMessage(), 502);
        }
    }

    /**
     * Step 4: Launch a platform-level Hosted Checkout session to charge a SaaS
     * subscription to the connected account using V2 `customer_account`.
     */
    public function subscribe(Request $request, string $accountId): RedirectResponse|JsonResponse
    {
        $record = $this->findAuthorizedAccountOrFail($request, $accountId);

        try {
            // Note: `{CHECKOUT_SESSION_ID}` is expanded by Stripe on redirect.
            $successUrl = route('stripe-connect.subscription.success', [
                'accountId' => $record->stripe_account_id,
            ]).'?session_id={CHECKOUT_SESSION_ID}';

            $cancelUrl = route('stripe-connect.dashboard', [
                'accountId' => $record->stripe_account_id,
                'subscription' => 'canceled',
            ]);

            $session = $this->stripeConnect->createPlatformSubscriptionCheckoutSession(
                $record->stripe_account_id,
                $successUrl,
                $cancelUrl,
            );

            return $this->stripeRedirectResponse($request, (string) $session->url);
        } catch (StripeConnectConfigurationException $e) {
            return $this->errorResponse($request, $e->getMessage(), 503);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Connect subscription checkout creation failed', [
                'account_id' => $record->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($request, 'Could not start subscription checkout: '.$e->getMessage(), 502);
        }
    }

    /**
     * Step 4b: Confirmation page shown after the connected account completes
     * platform subscription checkout.
     */
    public function subscriptionSuccess(Request $request, string $accountId): View
    {
        $record = $this->findAuthorizedAccountOrFail($request, $accountId);

        return view('stripe-connect.subscription-success', [
            'account' => $record,
            'sessionId' => (string) $request->query('session_id', ''),
        ]);
    }

    /**
     * Step 5: Create a Stripe Billing Portal session using `customer_account`
     * so the connected account can manage their platform subscription.
     */
    public function billingPortal(Request $request, string $accountId): RedirectResponse|JsonResponse
    {
        $record = $this->findAuthorizedAccountOrFail($request, $accountId);

        try {
            $returnUrl = route('stripe-connect.dashboard', [
                'accountId' => $record->stripe_account_id,
            ]);

            $portalSession = $this->stripeConnect->createBillingPortalSession(
                $record->stripe_account_id,
                $returnUrl,
            );

            return $this->stripeRedirectResponse($request, (string) $portalSession->url);
        } catch (StripeConnectConfigurationException $e) {
            return $this->errorResponse($request, $e->getMessage(), 503);
        } catch (ApiErrorException $e) {
            Log::error('Stripe Billing Portal session creation failed', [
                'account_id' => $record->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse($request, 'Could not open Billing Portal: '.$e->getMessage(), 502);
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * List connected accounts accessible to the current principal:
     *   - superadmin: all connected accounts
     *   - authenticated user: accounts mapped to their `user_id`
     *   - unauthenticated demo visitor: only accounts created in their own session
     *
     * @return Collection<int, StripeConnectedAccount>
     */
    private function accessibleAccounts(Request $request): Collection
    {
        $user = Auth::user();

        if ($user !== null) {
            if (($user->role ?? null) === 'superadmin') {
                return StripeConnectedAccount::query()->latest()->get();
            }

            return StripeConnectedAccount::query()
                ->where('user_id', $user->getKey())
                ->latest()
                ->get();
        }

        $demoIds = (array) $request->session()->get('stripe_connect_demo_account_ids', []);
        if ($demoIds === []) {
            return collect();
        }

        return StripeConnectedAccount::query()
            ->whereNull('user_id')
            ->whereIn('stripe_account_id', $demoIds)
            ->latest()
            ->get();
    }

    /**
     * Resolve a connected account only if the current caller owns it (prevents IDOR).
     */
    private function findAuthorizedAccountOrFail(Request $request, string $accountId): StripeConnectedAccount
    {
        $account = $this->accessibleAccounts($request)
            ->firstWhere('stripe_account_id', $accountId);

        abort_if($account === null, 404, 'Connected account not found or not accessible.');

        return $account;
    }

    /**
     * Return JSON `{ "url": ... }` for fetch/AJAX callers (which avoids Chromium
     * blocking cross-origin redirects under CSP `form-action 'self'`), or a
     * standard 303 redirect for direct form submissions.
     */
    private function stripeRedirectResponse(Request $request, string $url): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['url' => $url]);
        }

        return redirect()->away($url, 303);
    }

    private function errorResponse(Request $request, string $message, int $status): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], $status);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }
}
