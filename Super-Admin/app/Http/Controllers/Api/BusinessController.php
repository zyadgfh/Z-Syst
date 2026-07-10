<?php

namespace App\Http\Controllers\Api;

use App\Models\Option;
use App\Models\Plan;
use App\Models\User;
use App\Models\Business;
use App\Models\Currency;
use App\Helpers\HasUploader;
use App\Models\UserCurrency;
use Illuminate\Http\Request;
use App\Models\PlanSubscribe;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use Illuminate\Support\Facades\Hash;

class BusinessController extends Controller
{
    use HasUploader;

    public function index()
    {
        $business_id = auth()->user()->business_id;

        // Ensure currency exists
        $business_currency = UserCurrency::select('id', 'name', 'code', 'symbol', 'position')
            ->where('business_id', $business_id)
            ->first();

        if (!$business_currency) {
            $currency = Currency::where('is_default', 1)->first();
            UserCurrency::create([
                'name' => $currency->name,
                'code' => $currency->code,
                'rate' => $currency->rate,
                'business_id' => $business_id,
                'symbol' => $currency->symbol,
                'currency_id' => $currency->id,
                'position' => $currency->position,
                'country_name' => $currency->country_name,
            ]);
        }

        // Fetch user and business info
        $user = User::select('id', 'name', 'role', 'visibility', 'lang', 'email', 'branch_id', 'active_branch_id')->findOrFail(auth()->id());
        $business = Business::with('category:id,name', 'enrolled_plan:id,plan_id,business_id,price,duration,allow_multibranch', 'enrolled_plan.plan:id,subscriptionName')->findOrFail($business_id);

        //admin setting option
        $generalValue = Option::where('key', 'general')->first()->value ?? [];
        $develop_by_level = $generalValue['admin_footer_text'] ?? '';
        $develop_by = $generalValue['admin_footer_link_text'] ?? '';
        $develop_by_link = $generalValue['admin_footer_link'] ?? '';

        // Get business settings option
        $option = Option::where('key', 'business-settings')
            ->whereJsonContains('value->business_id', $business_id)
            ->first();

        $invoice_logo = $option->value['invoice_logo'] ?? null;
        $a4_invoice_logo = $option->value['a4_invoice_logo'] ?? null;
        $thermal_invoice_logo = $option->value['thermal_invoice_logo'] ?? null;
        $invoice_scanner_logo = $option->value['invoice_scanner_logo'] ?? null;
        $sale_rounding_option = $option->value['sale_rounding_option'] ?? 'none';
        $note_label = $option->value['note_label'] ?? null;
        $note = $option->value['note'] ?? null;
        $gratitude_message = $option->value['gratitude_message'] ?? null;
        $warranty_void_label = $option->value['warranty_void_label'] ?? null;
        $warranty_void = $option->value['warranty_void'] ?? null;

        $data = array_merge(
            $business->toArray(),
            ['user' => $user->toArray() + ['active_branch' =>  $user->active_branch]],
            ['business_currency' => $business_currency],
            ['invoice_logo' => $invoice_logo],
            ['a4_invoice_logo' => $a4_invoice_logo],
            ['thermal_invoice_logo' => $thermal_invoice_logo],
            ['invoice_scanner_logo' => $invoice_scanner_logo],
            ['sale_rounding_option' => $sale_rounding_option],
            ['invoice_size' => !empty(invoice_setting()) && moduleCheck('ThermalPrinterAddon') ? invoice_setting() : null],
            ['invoice_language' => !empty(invoice_language()) ? invoice_language() : ''],
            ['note' => $note],
            ['note_label' => $note_label],
            ['gratitude_message' => $gratitude_message],
            ['warranty_void_label' => $warranty_void_label],
            ['warranty_void' => $warranty_void],
            ['show_note' => (int) ($option->value['show_note'] ?? 0)],
            ['show_gratitude_msg' => (int) ($option->value['show_gratitude_msg'] ?? 0)],
            ['show_invoice_scanner_logo' => (int) ($option->value['show_invoice_scanner_logo'] ?? 0)],
            ['show_a4_invoice_logo' => (int) ($option->value['show_a4_invoice_logo'] ?? 0)],
            ['show_thermal_invoice_logo' => (int) ($option->value['show_thermal_invoice_logo'] ?? 0)],
            ['show_warranty' => (int) ($option->value['show_warranty'] ?? 0)],
            ['develop_by_level' => $develop_by_level],
            ['develop_by' => $develop_by],
            ['develop_by_link' => $develop_by_link],
            ['branch_count' => branch_count()],
            ['addons' => [
                'AffiliateAddon' => moduleCheck('AffiliateAddon'),
                'MultiBranchAddon' => moduleCheck('MultiBranchAddon'),
                'WarehouseAddon' => moduleCheck('WarehouseAddon'),
                'ThermalPrinterAddon' => moduleCheck('ThermalPrinterAddon'),
                'HrmAddon' => moduleCheck('HrmAddon'),
                'CustomDomainAddon' => moduleCheck('CustomDomainAddon'),
                'SerialCodeAddon' => moduleCheck('SerialCodeAddon')
            ]],
        );

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'address' => 'nullable|max:250',
            'companyName' => 'required|max:250',
            'pictureUrl' => 'nullable|image|max:5120',
            'shopOpeningBalance' => 'nullable|numeric',
            'phoneNumber'  => 'nullable',
            'business_category_id' => 'required|exists:business_categories,id',
        ]);

        DB::beginTransaction();
        try {

            $user = auth()->user();
            $free_plan = Plan::where('subscriptionPrice', '<=', 0)->orWhere('offerPrice', '<=', 0)->first();

            $business = Business::create($request->except('pictureUrl') + [
                'phoneNumber' => $request->phoneNumber,
                'subscriptionDate' => $free_plan ? now() : NULL,
                'will_expire' => $free_plan ? now()->addDays($free_plan->duration) : NULL,
                'pictureUrl' => $request->pictureUrl ? $this->upload($request, 'pictureUrl') : NULL
            ]);

            PaymentType::create([
                'name' => "Cash",
                'business_id' => $business->id
            ]);

            $user->update([
                'business_id' => $business->id,
                'phone' => $request->phoneNumber,
                'name' => $business->companyName,
            ]);

            $currency = Currency::where('is_default', 1)->first();
            UserCurrency::create([
                'business_id' => $business->id,
                'currency_id' => $currency->id,
                'name' => $currency->name,
                'country_name' => $currency->country_name,
                'code' => $currency->code,
                'rate' => $currency->rate,
                'symbol' => $currency->symbol,
                'position' => $currency->position,
            ]);

            if ($free_plan) {
                $subscribe = PlanSubscribe::create([
                    'plan_id' => $free_plan->id,
                    'business_id' => $business->id,
                    'duration' => $free_plan->duration,
                    'allow_multibranch' => $free_plan->allow_multibranch,
                ]);

                $business->update([
                    'plan_subscribe_id' => $subscribe->id,
                ]);
            }

            Cache::forget('plan-data-' . $business->id);

            DB::commit();
            return response()->json([
                'message' => __('Business setup completed.'),
            ]);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(__('Something went wrong, Please contact with admin.'), 403);
        }
    }

    public function update(Request $request, Business $business)
    {
        $request->validate([
            'address' => 'nullable|max:250',
            'companyName' => 'nullable|required_if:sale_rounding_option,!=,null|max:250',
            'business_category_id' => 'nullable|required_if:sale_rounding_option,!=,null|exists:business_categories,id',
            'pictureUrl' => 'nullable|image|max:5120',
            'show_company_name' => 'nullable|boolean',
            'show_phone_number' => 'nullable|boolean',
            'show_address' => 'nullable|boolean',
            'show_email' => 'nullable|boolean',
            'show_vat' => 'nullable|boolean',
            'invoice_logo' => 'nullable|image|max:5120',
            'a4_invoice_logo' => 'nullable|image|max:5120',
            'thermal_invoice_logo' => 'nullable|image|max:5120',
            'invoice_scanner_logo' => 'nullable|image|max:5120',
            'sale_rounding_option' => 'nullable|in:none,round_up,nearest_whole_number,nearest_0.05,nearest_0.1,nearest_0.5',
            'phoneNumber'  => 'nullable',
            'invoice_size' => 'nullable|string|max:100',
            'invoice_language' => 'nullable|string|max:100',
            'gratitude_message' => 'nullable|string|max:100',
            'warranty_void_label' => 'nullable|string',
            'warranty_void' => 'nullable|string',
            'show_note' => 'nullable|boolean',
            'show_gratitude_msg' => 'nullable|boolean',
            'show_invoice_scanner_logo' => 'nullable|boolean',
            'show_a4_invoice_logo' => 'nullable|boolean',
            'show_thermal_invoice_logo' => 'nullable|boolean',
            'show_warranty' => 'nullable|boolean',
        ]);

        $business->update([
            'meta' => [
                'show_company_name' => (int)($request->show_company_name ?? 0),
                'show_phone_number' => (int)($request->show_phone_number ?? 0),
                'show_address' => (int)($request->show_address ?? 0),
                'show_email' => (int)($request->show_email ?? 0),
                'show_vat' => (int)($request->show_vat ?? 0),
            ]
        ]);

        // Update when sale_rounding_option is not provided
        if (!$request->sale_rounding_option) {
            auth()->user()->update([
                'name' => $request->companyName,
                'phone' => $request->phoneNumber,
            ]);

            $business->update($request->except('pictureUrl','meta') + [
                'pictureUrl' => $request->pictureUrl ? $this->upload($request, 'pictureUrl', $business->pictureUrl) : $business->pictureUrl,
            ]);
        }

        // Update or insert business settings
        $setting = Option::where('key', 'business-settings')
            ->whereJsonContains('value->business_id', $business->id)
            ->first();

        $invoiceLogo = $request->invoice_logo ? $this->upload($request, 'invoice_logo', $setting->value['invoice_logo'] ?? null) : ($setting->value['invoice_logo'] ?? null);
        $a4_invoice_logo = $request->a4_invoice_logo ? $this->upload($request, 'a4_invoice_logo', $setting->value['a4_invoice_logo'] ?? null) : ($setting->value['a4_invoice_logo'] ?? null);
        $thermal_invoice_logo = $request->thermal_invoice_logo ? $this->upload($request, 'thermal_invoice_logo', $setting->value['thermal_invoice_logo'] ?? null) : ($setting->value['thermal_invoice_logo'] ?? null);
        $invoice_scanner_logo = $request->invoice_scanner_logo ? $this->upload($request, 'invoice_scanner_logo', $setting->value['invoice_scanner_logo'] ?? null) : ($setting->value['invoice_scanner_logo'] ?? null);

        $settingData = [
            'business_id' => $business->id,
            'invoice_logo' => $invoiceLogo,
            'a4_invoice_logo' => $a4_invoice_logo,
            'thermal_invoice_logo' => $thermal_invoice_logo,
            'invoice_scanner_logo' => $invoice_scanner_logo,
            'sale_rounding_option' => $request->sale_rounding_option ?? 'none',
            'note_label' => $request->note_label,
            'note' => $request->note,
            'gratitude_message' => $request->gratitude_message,
            'warranty_void_label' => $request->warranty_void_label,
            'warranty_void' => $request->warranty_void,
            'vat_name' => $request->vat_name,
            'vat_no' => $request->vat_no,
            'show_note' => $request->show_note ?? 0,
            'show_gratitude_msg' => $request->show_gratitude_msg ?? 0,
            'show_invoice_scanner_logo' => $request->show_invoice_scanner_logo ?? 0,
            'show_a4_invoice_logo' => $request->show_a4_invoice_logo ?? 0,
            'show_thermal_invoice_logo' => $request->show_thermal_invoice_logo ?? 0,
            'show_warranty' => $request->show_warranty ?? 0,
        ];

        if ($setting) {
            $setting->update([
                'value' => array_merge($setting->value, $settingData),
            ]);
        } else {
            Option::create([
                'key' => 'business-settings',
                'value' => $settingData,
            ]);
        }

        // Update Invoice Settings
        if ($request->filled('invoice_size')) {
            $invoiceKey = 'invoice_setting_' . $business->id;

            Option::updateOrCreate(
                ['key' => $invoiceKey],
                ['value' => $request->invoice_size]
            );

            Cache::forget($invoiceKey);
        }

        if ($request->filled('invoice_language')) {
            $invoice_language_key = 'invoice_language_' . $business->id;

            Option::updateOrCreate(
                ['key' => $invoice_language_key],
                ['value' => $request->invoice_language]
            );

            Cache::forget($invoice_language_key);
        }

        Cache::forget("business_setting_{$business->id}");
        Cache::forget("business_sale_rounding_{$business->id}");

        return response()->json([
            'message' => __('Data saved successfully.'),
            'business' => $business,
            'invoice_logo' => $invoiceLogo,
            'a4_invoice_logo' => $a4_invoice_logo,
            'thermal_invoice_logo' => $thermal_invoice_logo,
            'invoice_scanner_logo' => $invoice_scanner_logo,
            'sale_rounding_option' => $request->sale_rounding_option,
            'invoice_size' => $request->invoice_size,
            'invoice_language' => $request->invoice_language,
            'note_label' => $request->note_label,
            'note' => $request->note,
            'gratitude_message' => $request->gratitude_message,
            'warranty_void_label' => $request->warranty_void_label,
            'warranty_void' => $request->warranty_void,
            'vat_name' => $request->vat_name,
            'vat_no' => $request->vat_no,
            'show_note' => (int) $request->show_note ?? 0,
            'show_gratitude_msg' => (int) $request->show_gratitude_msg ?? 0,
            'show_invoice_scanner_logo' => (int) $request->show_invoice_scanner_logo ?? 0,
            'show_a4_invoice_logo' => (int) $request->show_a4_invoice_logo ?? 0,
            'show_thermal_invoice_logo' => (int) $request->show_thermal_invoice_logo ?? 0,
            'show_warranty' => (int) $request->show_warranty ?? 0,
        ]);
    }

    public function updateExpireDate(Request $request)
    {
        $days = $request->query('days', 0);
        $operation = $request->query('operation');
        $business = Business::where('id', auth()->user()->business_id)->first();
        if (!$business) {
            return response()->json([
                'message' => 'Business not found.',
            ], 404);
        }
        if ($operation === 'add') {
            $business->will_expire = now()->addDays($days);
        } elseif ($operation === 'sub') {
            $business->will_expire = now()->subDays($days);
        } else {
            return response()->json([
                'message' => 'Invalid operation. Use "add" or "sub".',
            ], 400);
        }
        $business->save();
        return response()->json([
            'message' => 'Expiry date updated successfully.',
            'will_expire' => $business->will_expire,
        ]);
    }

    public function deleteBusiness(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => __('The provided password is incorrect.'),
            ], 406);
        }

        Business::where('id', $user->business_id)->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
