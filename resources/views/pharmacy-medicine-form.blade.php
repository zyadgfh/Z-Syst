<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($medicine) ? 'تعديل دواء' : 'إدخال دواء جديد' }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; background: #f8fafc; color: #0f172a; }
        .panel { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); max-width: 900px; margin: 0 auto; }
        h1 { margin-top: 0; color: #2563eb; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; }
        .form-group { display: flex; flex-direction: column; }
        label { font-weight: bold; margin-bottom: 6px; font-size: 14px; }
        input, select, textarea { padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; }
        textarea { resize: vertical; min-height: 80px; }
        .checkbox-group { flex-direction: row; align-items: center; gap: 8px; }
        .checkbox-group input { width: auto; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; border: none; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-secondary { background: #64748b; color: white; margin-right: 10px; }
        .actions { margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>{{ isset($medicine) ? 'تعديل الدواء' : 'إدخال دواء جديد' }}</h1>
        
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ isset($medicine) ? route('pharmacy.medicines.update', $medicine->id) : route('pharmacy.medicines.store') }}">
            @csrf
            @if(isset($medicine))
                @method('PUT')
            @endif

            <div class="form-grid">
                <!-- المعلومات الأساسية -->
                <div class="form-group">
                    <label for="generic_name">الاسم العلمي *</label>
                    <input type="text" id="generic_name" name="generic_name" value="{{ old('generic_name', $medicine->generic_name ?? '') }}" required>
                </div>

                <div class="form-group">
                    <label for="brand_name">الاسم التجاري</label>
                    <input type="text" id="brand_name" name="brand_name" value="{{ old('brand_name', $medicine->brand_name ?? '') }}">
                </div>

                <div class="form-group">
                    <label for="product_name">اسم المنتج</label>
                    <input type="text" id="product_name" name="product_name" value="{{ old('product_name', $medicine->product_name ?? '') }}">
                </div>

                <!-- التعريف والتصنيف -->
                <div class="form-group">
                    <label for="category_id">الفئة</label>
                    <select id="category_id" name="category_id">
                        <option value="">اختر الفئة</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (old('category_id', $medicine->category_id ?? '') == $category->id) ? 'selected' : '' }}>
                                {{ $category->categoryName }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="manufacturer_id">الشركة المصنعة</label>
                    <select id="manufacturer_id" name="manufacturer_id">
                        <option value="">اختر الشركة</option>
                        @foreach($manufacturers as $manufacturer)
                            <option value="{{ $manufacturer->id }}" {{ (old('manufacturer_id', $medicine->manufacturer_id ?? '') == $manufacturer->id) ? 'selected' : '' }}>
                                {{ $manufacturer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- المعلومات الصيدلانية -->
                <div class="form-group">
                    <label for="dosage_form">الشكل الصيدلي</label>
                    <input type="text" id="dosage_form" name="dosage_form" value="{{ old('dosage_form', $medicine->dosage_form ?? '') }}" placeholder="قركم، حبة، شراب...">
                </div>

                <div class="form-group">
                    <label for="strength">القوة/التركيز</label>
                    <input type="text" id="strength" name="strength" value="{{ old('strength', $medicine->strength ?? '') }}" placeholder="500mg, 10mg/ml...">
                </div>

                <div class="form-group">
                    <label for="unit_of_measure">وحدة القياس</label>
                    <input type="text" id="unit_of_measure" name="unit_of_measure" value="{{ old('unit_of_measure', $medicine->unit_of_measure ?? '') }}">
                </div>

                <!-- المعرفات -->
                <div class="form-group">
                    <label for="product_code">كود المنتج (SKU)</label>
                    <input type="text" id="product_code" name="product_code" value="{{ old('product_code', $medicine->product_code ?? '') }}">
                </div>

                <div class="form-group">
                    <label for="barcode">الباركود</label>
                    <input type="text" id="barcode" name="barcode" value="{{ old('barcode', $medicine->barcode ?? '') }}">
                </div>

                <div class="form-group">
                    <label for="internal_code">الكود الداخلي</label>
                    <input type="text" id="internal_code" name="internal_code" value="{{ old('internal_code', $medicine->internal_code ?? '') }}">
                </div>

                <!-- الأسعار -->
                <div class="form-group">
                    <label for="purchase_price">سعر الشراء</label>
                    <input type="number" step="0.01" id="purchase_price" name="purchase_price" value="{{ old('purchase_price', $medicine->purchase_price ?? '') }}">
                </div>

                <div class="form-group">
                    <label for="sales_price">سعر البيع</label>
                    <input type="number" step="0.01" id="sales_price" name="sales_price" value="{{ old('sales_price', $medicine->sales_price ?? '') }}">
                </div>

                <div class="form-group">
                    <label for="wholesale_price">سعر الجملة</label>
                    <input type="number" step="0.01" id="wholesale_price" name="wholesale_price" value="{{ old('wholesale_price', $medicine->wholesale_price ?? '') }}">
                </div>

                <!-- إدارة المخزون -->
                <div class="form-group">
                    <label for="min_stock">أدنى مخزون</label>
                    <input type="number" step="0.01" id="min_stock" name="min_stock" value="{{ old('min_stock', $medicine->min_stock ?? '') }}">
                </div>

                <div class="form-group">
                    <label for="max_stock">أقصى مخزون</label>
                    <input type="number" step="0.01" id="max_stock" name="max_stock" value="{{ old('max_stock', $medicine->max_stock ?? '') }}">
                </div>

                <div class="form-group">
                    <label for="reorder_level">مستوى إعادة الطلب</label>
                    <input type="number" step="0.01" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', $medicine->reorder_level ?? '') }}">
                </div>

                <!-- معلومات تخزين -->
                <div class="form-group">
                    <label for="shelf_life_months">عمر الصلاحية (بالشهور)</label>
                    <input type="number" id="shelf_life_months" name="shelf_life_months" value="{{ old('shelf_life_months', $medicine->shelf_life_months ?? '') }}">
                </div>

                <!-- التحكم والوصفات -->
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="prescription_required" name="prescription_required" value="1" {{ (old('prescription_required', $medicine->prescription_required ?? false)) ? 'checked' : '' }}>
                    <label for="prescription_required">يتطلب وصفة طبية</label>
                </div>

                <div class="form-group">
                    <label for="controlled_substance_schedule">جدول المواد محكمة التحكم</label>
                    <select id="controlled_substance_schedule" name="controlled_substance_schedule">
                        <option value="">لا ينطبق</option>
                        <option value="1" {{ (old('controlled_substance_schedule', $medicine->controlled_substance_schedule ?? '') == 1) ? 'selected' : '' }}>برنامج 1</option>
                        <option value="2" {{ (old('controlled_substance_schedule', $medicine->controlled_substance_schedule ?? '') == 2) ? 'selected' : '' }}>برنامج 2</option>
                        <option value="3" {{ (old('controlled_substance_schedule', $medicine->controlled_substance_schedule ?? '') == 3) ? 'selected' : '' }}>برنامج 3</option>
                        <option value="4" {{ (old('controlled_substance_schedule', $medicine->controlled_substance_schedule ?? '') == 4) ? 'selected' : '' }}>برنامج 4</option>
                        <option value="5" {{ (old('controlled_substance_schedule', $medicine->controlled_substance_schedule ?? '') == 5) ? 'selected' : '' }}>برنامج 5</option>
                    </select>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="requires_special_handling" name="requires_special_handling" value="1" {{ (old('requires_special_handling', $medicine->requires_special_handling ?? false)) ? 'checked' : '' }}>
                    <label for="requires_special_handling">يتطلب تعامل خاص</label>
                </div>

                <!-- الوصف والتعليمات -->
                <div class="form-group" style="grid-column: span 3;">
                    <label for="description">الوصف</label>
                    <textarea id="description" name="description">{{ old('description', $medicine->description ?? '') }}</textarea>
                </div>

                <div class="form-group" style="grid-column: span 3;">
                    <label for="usage_instructions">إرشادات الاستخدام</label>
                    <textarea id="usage_instructions" name="usage_instructions">{{ old('usage_instructions', $medicine->usage_instructions ?? '') }}</textarea>
                </div>

                <div class="form-group" style="grid-column: span 3;">
                    <label for="side_effects">الآثار الجانبية</label>
                    <textarea id="side_effects" name="side_effects">{{ old('side_effects', $medicine->side_effects ?? '') }}</textarea>
                </div>

                <!-- الحالة -->
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ (old('is_active', $medicine->is_active ?? true)) ? 'checked' : '' }}>
                    <label for="is_active">نشط</label>
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" id="track_inventory" name="track_inventory" value="1" {{ (old('track_inventory', $medicine->track_inventory ?? true)) ? 'checked' : '' }}>
                    <label for="track_inventory">تتبع المخزون</label>
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">{{ isset($medicine) ? 'حفظ التعديلات' : 'إضافة الدواء' }}</button>
                <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-secondary">إلغاء</a>
            </div>
        </form>
    </div>
</body>
</html>