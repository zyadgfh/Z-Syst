<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Manufacturer;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    /**
     * عرض قائمة الأدوية
     */
    public function index()
    {
        $medicines = Product::query()
            ->select('id', 'generic_name', 'brand_name', 'product_name', 'strength', 'dosage_form', 
                    'purchase_price', 'sales_price', 'is_active', 'track_inventory')
            ->orderBy('generic_name')
            ->paginate(20);

        return view('pharmacy-medicines', compact('medicines'));
    }

    /**
     * عرض نموذج إدخال دواء جديد
     */
    public function create()
    {
        $categories = Category::orderBy('categoryName')->get();
        $manufacturers = Manufacturer::orderBy('name')->get();

        return view('pharmacy-medicine-form', compact('categories', 'manufacturers'));
    }

    /**
     * حفظ دواء جديد
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            // Basic Info
            'generic_name' => 'required|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'product_name' => 'nullable|string|max:255',

            // Identification
            'product_code' => 'nullable|string|max:100|unique:products',
            'barcode' => 'nullable|string|max:255|unique:products',
            'internal_code' => 'nullable|string|max:100',

            // Pharmacy Specific
            'category_id' => 'nullable|exists:categories,id',
            'manufacturer_id' => 'nullable|exists:manufacturers,id',
            'dosage_form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'unit_of_measure' => 'nullable|string|max:50',
            'package_size' => 'nullable|string|max:100',

            // Prescription & Control
            'prescription_required' => 'nullable|boolean',
            'controlled_substance_schedule' => 'nullable|in:1,2,3,4,5',
            'requires_special_handling' => 'nullable|boolean',

            // Storage
            'storage_conditions' => 'nullable|json',
            'shelf_life_months' => 'nullable|integer|min:0',

            // Stock Management
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'alert_qty' => 'nullable|numeric|min:0',

            // Pricing
            'purchase_price' => 'nullable|numeric|min:0',
            'sales_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',

            // Description
            'description' => 'nullable|string',
            'usage_instructions' => 'nullable|string',
            'side_effects' => 'nullable|string',

            // Status
            'is_active' => 'nullable|boolean',
            'track_inventory' => 'nullable|boolean',
        ]);

        $medicine = Product::create($data);

        return redirect()->route('pharmacy.medicines.index')
            ->with('success', 'تم إضافة الدواء بنجاح');
    }

    /**
     * عرض تفاصيل دواء
     */
    public function show($id)
    {
        $medicine = Product::with(['category', 'manufacturer', 'inventory'])->findOrFail($id);
        return view('pharmacy-medicine-show', compact('medicine'));
    }

    /**
     * عرض نموذج تعديل دواء
     */
    public function edit($id)
    {
        $medicine = Product::findOrFail($id);
        $categories = Category::orderBy('categoryName')->get();
        $manufacturers = Manufacturer::orderBy('name')->get();

        return view('pharmacy-medicine-form', compact('medicine', 'categories', 'manufacturers'));
    }

    /**
     * تحديث دواء
     */
    public function update(Request $request, $id)
    {
        $medicine = Product::findOrFail($id);

        $data = $request->validate([
            // Basic Info
            'generic_name' => 'required|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'product_name' => 'nullable|string|max:255',

            // Identification
            'product_code' => 'nullable|string|max:100|unique:products,product_code,' . $medicine->id,
            'barcode' => 'nullable|string|max:255|unique:products,barcode,' . $medicine->id,
            'internal_code' => 'nullable|string|max:100',

            // Pharmacy Specific
            'category_id' => 'nullable|exists:categories,id',
            'manufacturer_id' => 'nullable|exists:manufacturers,id',
            'dosage_form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'unit_of_measure' => 'nullable|string|max:50',
            'package_size' => 'nullable|string|max:100',

            // Prescription & Control
            'prescription_required' => 'nullable|boolean',
            'controlled_substance_schedule' => 'nullable|in:1,2,3,4,5',
            'requires_special_handling' => 'nullable|boolean',

            // Storage
            'storage_conditions' => 'nullable|json',
            'shelf_life_months' => 'nullable|integer|min:0',

            // Stock Management
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'alert_qty' => 'nullable|numeric|min:0',

            // Pricing
            'purchase_price' => 'nullable|numeric|min:0',
            'sales_price' => 'nullable|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',

            // Description
            'description' => 'nullable|string',
            'usage_instructions' => 'nullable|string',
            'side_effects' => 'nullable|string',

            // Status
            'is_active' => 'nullable|boolean',
            'track_inventory' => 'nullable|boolean',
        ]);

        $medicine->update($data);

        return redirect()->route('pharmacy.medicines.index')
            ->with('success', 'تم تحديث الدواء بنجاح');
    }

    /**
     * حذف دواء
     */
    public function destroy($id)
    {
        $medicine = Product::findOrFail($id);
        $medicine->delete();

        return redirect()->route('pharmacy.medicines.index')
            ->with('success', 'تم حذف الدواء بنجاح');
    }

    /**
     * البحث في الأدوية (API)
     */
    public function search(Request $request)
    {
        $search = $request->input('q');
        $medicines = Product::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('generic_name', 'like', "%{$search}%")
                      ->orWhere('brand_name', 'like', "%{$search}%")
                      ->orWhere('product_code', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->where('track_inventory', true)
            ->limit(20)
            ->get(['id', 'generic_name', 'brand_name', 'sales_price as price', 'barcode']);

        return response()->json($medicines);
    }

    /**
     * عرض تقارير الأدوية
     */
    public function reports()
    {
        // الإحصائيات العامة
        $totalMedicines = Product::count();
        $activeMedicines = Product::where('is_active', true)->count();
        $prescriptionMedicines = Product::where('prescription_required', true)->count();
        $controlledMedicines = Product::whereNotNull('controlled_substance_schedule')->count();

        // الأدوية منخفضة المخزون (نفس مستوى إعادة الطلب أو أقل)
        $lowStockMedicines = Product::whereHas('inventory', function ($q) {
            $q->selectRaw('SUM(quantity) as total_qty')
              ->havingRaw('total_qty <= products.reorder_level');
        })->orWhere(function ($q) {
            $q->where('track_inventory', true)
              ->where('reorder_level', '>', 0);
        })->with('inventory')->limit(20)->get();

        $lowStockCount = $lowStockMedicines->count();

        // الأدوية قريبة الانتهاء (خلال 90 يوم)
        $expiringMedicines = Product::whereHas('inventory', function ($q) {
            $q->whereBetween('expiry_date', [now(), now()->addDays(90)])
              ->where('quantity', '>', 0);
        })->with(['inventory' => function ($q) {
            $q->select('product_id', 'expiry_date', 'quantity')
              ->whereBetween('expiry_date', [now(), now()->addDays(90)])
              ->where('quantity', '>', 0);
        }])->limit(20)->get();

        $expiringSoonCount = $expiringMedicines->count();

        // الأدوية حسب الفئات
        $medicinesByCategory = Category::withCount('products')
            ->withSum('products', 'min_stock')
            ->orderBy('products_count', 'desc')
            ->limit(10)
            ->get();

        // الأدوية حسب الشركات المصنعة
        $medicinesByManufacturer = Manufacturer::withCount('products')
            ->orderBy('products_count', 'desc')
            ->limit(10)
            ->get();

        // مواد محكمة التحكم
        $controlledMedicinesList = Product::whereNotNull('controlled_substance_schedule')
            ->limit(20)
            ->get();

        return view('pharmacy-medicine-reports', compact(
            'totalMedicines',
            'activeMedicines',
            'lowStockCount',
            'expiringSoonCount',
            'prescriptionMedicines',
            'controlledMedicines',
            'lowStockMedicines',
            'expiringMedicines',
            'medicinesByCategory',
            'medicinesByManufacturer',
            'controlledMedicinesList'
        ));
    }
}
