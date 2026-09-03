<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CurrencyExport;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ZSystCurrencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:currencies-create')->only('create', 'store');
        $this->middleware('permission:currencies-read')->only('index');
        $this->middleware('permission:currencies-update')->only('edit', 'update', 'default');
        $this->middleware('permission:currencies-delete')->only('destroy');
    }

    public function index()
    {
        $this->authorize('viewAny', Currency::class);
        $currencies = Currency::orderBy('is_default', 'desc')->orderBy('status', 'desc')->paginate(10);

        return view('admin.currencies.index', compact('currencies'));
    }

    public function zsystFilter(Request $request)
    {
        $currencies = Currency::orderBy('is_default', 'desc')->orderBy('status', 'desc')->when(request('search'), function ($q) {
            $q->where(function ($q) {
                $q->where('name', 'like', '%'.request('search').'%')
                    ->orWhere('country_name', 'like', '%'.request('search').'%')
                    ->orWhere('code', 'like', '%'.request('search').'%')
                    ->orWhere('symbol', 'like', '%'.request('search').'%');
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.currencies.datas', compact('currencies'))->render(),
            ]);
        }

        return redirect(url()->previous());
    }

    public function create()
    {
        $this->authorize('create', Currency::class);
        $countries = base_path('lang/countrylist.json');
        $countries = json_decode(file_get_contents($countries), true);

        return view('admin.currencies.create', compact('countries'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Currency::class);
        $request->validate([
            'name' => 'required|string|max:30|unique:currencies',
            'country_name' => 'nullable|string|max:255',
            'code' => 'required|string|max:10|unique:currencies',
            'rate' => 'nullable|numeric|min:0',
            'symbol' => 'nullable|string|max:5',
            'position' => 'nullable|string|max:20',
            'status' => 'required|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        Currency::create($request->all());

        return response()->json([
            'message' => __('Currency Created successfully'),
            'redirect' => route('admin.currencies.index'),
        ]);
    }

    public function edit(Currency $currency)
    {
        $countries = base_path('lang/countrylist.json');
        $countries = json_decode(file_get_contents($countries), true);

        return view('admin.currencies.edit', compact('currency', 'countries'));
    }

    public function update(Request $request, Currency $currency)
    {
        $this->authorize('update', $currency);
        $request->validate([
            'name' => 'required|string|max:30|unique:currencies,name,'.$currency->id,
            'country_name' => 'nullable|string|max:255',
            'code' => 'required|string|max:10|unique:currencies,code,'.$currency->id,
            'rate' => 'nullable|numeric|min:0',
            'symbol' => 'nullable|string|max:5',
            'position' => 'nullable|string|max:20',
            'status' => 'required|boolean',
            'is_default' => 'nullable|boolean',
        ]);

        $currency->update($request->all());

        return response()->json([
            'message' => __('Currency updated successfully'),
            'redirect' => route('admin.currencies.index'),
        ]);
    }

    public function default($id)
    {
        $currency = Currency::find($id);

        if ($currency) {
            Currency::where('id', '!=', $id)->update(['is_default' => 0]);
            $currency->update(['is_default' => 1]);
        }

        return redirect()->route('admin.currencies.index')->with('message', __('Default currency activated successfully'));
    }

    public function destroy(Currency $currency)
    {
        $this->authorize('delete', $currency);
        if ($currency->is_default) {
            return response()->json([
                'message' => __('You cannot delete it because it is default currency'),
                'redirect' => route('admin.currencies.index'),
            ], 400);
        }

        $currency->delete();

        return response()->json([
            'message' => __('Currency deleted successfully'),
            'redirect' => route('admin.currencies.index'),
        ], 200);
    }

    public function deleteAll(Request $request)
    {
        $default_currency_id = Currency::where('is_default', 1)->value('id');

        if (count($request->ids) === 1 && in_array($default_currency_id, $request->ids)) {
            return response()->json([
                'message' => __('You cannot delete the default currency.'),
                'redirect' => route('admin.currencies.index'),
            ], 400);
        }

        $idsToDelete = array_filter($request->ids, function ($id) use ($default_currency_id) {
            return $id != $default_currency_id;
        });

        Currency::whereIn('id', $idsToDelete)->delete();

        return response()->json([
            'message' => __('Selected currencies deleted successfully.'),
            'redirect' => route('admin.currencies.index'),
        ]);
    }

    public function exportExcel()
    {
        return Excel::download(new CurrencyExport, 'currencies.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new CurrencyExport, 'currencies.csv');
    }
}
