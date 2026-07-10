<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AffiliateAddon\App\Models\AffiliateTransaction;
use Illuminate\Support\Facades\DB;

class AcnooWithdrawRequestController extends Controller
{
    public function index()
    {
        $withdraws = AffiliateTransaction::with('user:id,name')->latest()->paginate(20);
        return view('admin.affiliate-modules.withdraws.index', compact('withdraws'));
    }

    public function acnooFilter(Request $request)
    {
        $search = $request->input('search');

        $withdraws = AffiliateTransaction::with('user:id,name')->when($search, function ($q) use ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('amount', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    });
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 20);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.affiliate-modules.withdraws.datas', compact('withdraws'))->render()
            ]);
        }

        return redirect(url()->previous());
    }

    public function reject(Request $request, string $id)
    {
        $request->validate([
            'note' => 'required|string|max:255',
        ]);

        $reject = AffiliateTransaction::findOrFail($id);

        if ($reject) {
            $reject->update([
                'status' => 'unpaid',
                'note' => $request->note,
            ]);

            return response()->json([
                'message' => 'Status Unpaid',
                'redirect' => route('admin.affiliate-withdrawals.index'),
            ]);
        } else {
            return response()->json(['message' => 'request not found'], 404);
        }
    }

    public function paid(Request $request, string $id)
    {
        $request->validate([
            'note' => 'nullable|string|max:255',
        ]);

        $paid = AffiliateTransaction::findOrFail($id);

        if ($paid) {
            $paid->update([
                'status' => 'paid',
                'note' => $request->note,
            ]);

            return response()->json([
                'message' => 'Status Paid',
                'redirect' => route('admin.affiliate-withdrawals.index'),
            ]);
        } else {
            return response()->json(['message' => 'request not found'], 404);
        }
    }
}
