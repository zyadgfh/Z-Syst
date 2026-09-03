<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OnboardingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Show the onboarding wizard.
     */
    public function index()
    {
        $user = Auth::user();
        $business = $user->business;
        $steps = $this->getSteps($user);

        return view('admin.onboarding.index', compact('user', 'business', 'steps'));
    }

    /**
     * Dispatch to the correct step.
     */
    public function step(int $step)
    {
        return match($step) {
            1 => $this->step1(),
            2 => $this->step2(),
            3 => $this->step3(),
            4 => $this->step4(),
            default => abort(404),
        };
    }

    /**
     * Step 1: Business setup.
     */
    public function step1()
    {
        $user = Auth::user();
        $business = $user->business;
        $steps = $this->getSteps($user);

        return view('admin.onboarding.step1', compact('user', 'business', 'steps'));
    }

    /**
     * Save step 1 data.
     */
    public function saveStep1(Request $request)
    {
        $request->validate([
            'companyName' => 'required|string|max:255',
            'phoneNumber' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $business = $user->business;

        if ($business) {
            $business->update($request->only(['companyName', 'phoneNumber', 'address']));
        }

        return redirect()->route('admin.onboarding.step', 2)
            ->with('success', __('Business info saved!'));
    }

    /**
     * Step 2: Categories.
     */
    public function step2()
    {
        $user = Auth::user();
        $categories = Category::where('business_id', $user->business_id)->get();
        $steps = $this->getSteps($user);

        return view('admin.onboarding.step2', compact('user', 'categories', 'steps'));
    }

    /**
     * Save step 2 data (quick category).
     */
    public function saveStep2(Request $request)
    {
        $request->validate([
            'categoryName' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        Category::create([
            'business_id' => $user->business_id,
            'categoryName' => $request->categoryName,
            'status' => 'active',
        ]);

        if ($request->input('action') === 'continue') {
            return redirect()->route('admin.onboarding.step', 3)
                ->with('success', __('Category added!'));
        }

        return redirect()->route('admin.onboarding.step', 2)
            ->with('success', __('Category added! Add another or continue.'));
    }

    /**
     * Step 3: First product.
     */
    public function step3()
    {
        $user = Auth::user();
        $categories = Category::where('business_id', $user->business_id)->get();
        $units = Unit::where('business_id', $user->business_id)->get();
        $products = Product::where('business_id', $user->business_id)->get();
        $steps = $this->getSteps($user);

        return view('admin.onboarding.step3', compact('user', 'categories', 'units', 'products', 'steps'));
    }

    /**
     * Save step 3 data (quick product).
     */
    public function saveStep3(Request $request)
    {
        $request->validate([
            'productName' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sales_price' => 'required|numeric|min:0',
            'purchase_without_tax' => 'nullable|numeric|min:0',
        ]);

        $user = Auth::user();

        Product::create([
            'business_id' => $user->business_id,
            'productName' => $request->productName,
            'category_id' => $request->category_id,
            'sales_price' => $request->sales_price,
            'purchase_without_tax' => $request->purchase_without_tax ?? 0,
            'type' => 'standard',
        ]);

        if ($request->input('action') === 'continue') {
            return redirect()->route('admin.onboarding.step', 4)
                ->with('success', __('Product added!'));
        }

        return redirect()->route('admin.onboarding.step', 3)
            ->with('success', __('Product added! Add another or continue.'));
    }

    /**
     * Step 4: Review & Done.
     */
    public function step4()
    {
        $user = Auth::user();
        $business = $user->business;
        $categories = Category::where('business_id', $user->business_id)->count();
        $products = Product::where('business_id', $user->business_id)->count();
        $steps = $this->getSteps($user);

        return view('admin.onboarding.step4', compact('user', 'business', 'categories', 'products', 'steps'));
    }

    /**
     * Complete onboarding — mark as done and redirect to dashboard.
     */
    public function complete()
    {
        $user = Auth::user();

        // Store completion flag
        DB::table('settings')->updateOrInsert(
            ['key' => 'onboarding_completed_' . $user->business_id],
            ['value' => '1', 'type' => 'boolean']
        );

        return redirect()->route('admin.dashboard.index')
            ->with('success', __('🎉 Onboarding complete! Welcome to your dashboard.'));
    }

    /**
     * Skip onboarding entirely.
     */
    public function skip()
    {
        $user = Auth::user();

        DB::table('settings')->updateOrInsert(
            ['key' => 'onboarding_completed_' . $user->business_id],
            ['value' => '1', 'type' => 'boolean']
        );

        return redirect()->route('admin.dashboard.index')
            ->with('info', __('Onboarding skipped. You can always come back later.'));
    }

    /**
     * Build step status array.
     */
    protected function getSteps($user): array
    {
        $businessId = $user->business_id;

        return [
            1 => [
                'title' => __('Business Info'),
                'done' => $user->business && $user->business->companyName,
            ],
            2 => [
                'title' => __('Categories'),
                'done' => Category::where('business_id', $businessId)->count() > 0,
            ],
            3 => [
                'title' => __('Products'),
                'done' => Product::where('business_id', $businessId)->count() > 0,
            ],
            4 => [
                'title' => __('Review'),
                'done' => false,
            ],
        ];
    }
}
