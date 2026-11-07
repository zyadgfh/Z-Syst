<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function __construct(protected AccountingService $accounting)
    {
        $this->middleware('permission:accounting-read')->only(['index', 'vat', 'trialBalance']);
        $this->middleware('permission:accounting-create')->only('storeEntry');
    }

    public function index(Request $request)
    {
        return view('admin.accounting.index', [
            'entries' => JournalEntry::forBusiness((int) $request->user()->business_id)->latest('id')->paginate(15),
            'accounts' => $this->accounting->trialBalance((int) $request->user()->business_id),
        ]);
    }

    public function vat(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        return response()->json(['success' => true, 'data' => $this->accounting->vatReport((int) $request->user()->business_id, $from, $to)]);
    }

    public function trialBalance(Request $request)
    {
        return response()->json(['success' => true, 'data' => $this->accounting->trialBalance((int) $request->user()->business_id)]);
    }

    public function storeEntry(Request $request)
    {
        $data = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'nullable|string|max:300',
            'amount' => 'required|numeric|min:0.01',
            'debit_account' => 'required|string|max:8',
            'credit_account' => 'required|string|max:8|different:debit_account',
        ]);
        $businessId = (int) $request->user()->business_id;
        $amount = (float) $data['amount'];

        $this->accounting->post($businessId, $data['entry_date'], 'manual', null, $amount, [
            ['account_code' => $data['debit_account'], 'debit' => $amount, 'credit' => 0],
            ['account_code' => $data['credit_account'], 'debit' => 0, 'credit' => $amount],
        ], $data['description'] ?? null);

        return back()->with('success', __('سُجل القيد'));
    }
}
