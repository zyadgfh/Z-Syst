import 'package:flutter/material.dart';
import 'model/prediction_model.dart';
import 'repo/prediction_repo.dart';

class PredictionSettingsScreen extends StatefulWidget {
  const PredictionSettingsScreen({super.key});

  @override
  State<PredictionSettingsScreen> createState() =>
      _PredictionSettingsScreenState();
}

class _PredictionSettingsScreenState extends State<PredictionSettingsScreen> {
  final PredictionRepo _repo = PredictionRepo();
  PredictionSettings? _settings;
  bool _loading = true;
  bool _saving = false;

  // Form fields
  late bool _predictionEnabled;
  late String _forecastPeriod;
  late int _forecastDays;
  late int _historicalMonths;
  late String _predictionMethod;
  late bool _seasonalAdjustment;
  late double _safetyStockMultiplier;
  late int _leadTimeDays;
  late double _confidenceThreshold;
  late bool _autoOrderEnabled;

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    setState(() => _loading = true);
    final settings = await _repo.getSettings();
    if (mounted) {
      setState(() {
        _settings = settings ?? PredictionSettings.defaults();
        _predictionEnabled = _settings!.predictionEnabled;
        _forecastPeriod = _settings!.forecastPeriod;
        _forecastDays = _settings!.forecastDays;
        _historicalMonths = _settings!.historicalMonths;
        _predictionMethod = _settings!.predictionMethod;
        _seasonalAdjustment = _settings!.seasonalAdjustment;
        _safetyStockMultiplier = _settings!.safetyStockMultiplier;
        _leadTimeDays = _settings!.leadTimeDays;
        _confidenceThreshold = _settings!.confidenceThreshold;
        _autoOrderEnabled = _settings!.autoOrderEnabled;
        _loading = false;
      });
    }
  }

  Future<void> _saveSettings() async {
    setState(() => _saving = true);
    final updated = PredictionSettings(
      predictionEnabled: _predictionEnabled,
      forecastPeriod: _forecastPeriod,
      forecastDays: _forecastDays,
      historicalMonths: _historicalMonths,
      predictionMethod: _predictionMethod,
      seasonalAdjustment: _seasonalAdjustment,
      safetyStockMultiplier: _safetyStockMultiplier,
      leadTimeDays: _leadTimeDays,
      confidenceThreshold: _confidenceThreshold,
      autoOrderEnabled: _autoOrderEnabled,
    );

    final success = await _repo.updateSettings(updated);
    if (mounted) {
      setState(() => _saving = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(success ? 'تم حفظ الإعدادات بنجاح' : 'فشل في حفظ الإعدادات'),
          backgroundColor: success ? Colors.green : Colors.red,
        ),
      );
      if (success) _loadSettings();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('إعدادات التنبؤ بالمبيعات'),
        actions: [
          _saving
              ? const Center(child: Padding(
                  padding: EdgeInsets.all(16.0),
                  child: SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  ),
                ))
              : IconButton(
                  icon: const Icon(Icons.save),
                  onPressed: _saveSettings,
                  tooltip: 'حفظ',
                ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSection(
                    'إعدادات التنبؤ الأساسية',
                    [
                      _buildSwitchTile(
                        'تفعيل التنبؤ',
                        'تشغيل نظام التنبؤ بالمبيعات',
                        _predictionEnabled,
                        (v) => setState(() => _predictionEnabled = v),
                      ),
                      _buildDropdownTile(
                        'فترة التنبؤ',
                        'الوحدة الزمنية للتنبؤ',
                        _forecastPeriod,
                        ['daily', 'weekly', 'monthly'],
                        ['يومي', 'أسبوعي', 'شهري'],
                        (v) => setState(() => _forecastPeriod = v!),
                      ),
                      _buildSliderTile(
                        'أيام التنبؤ',
                        'عدد الأيام المستقبلية للتنبؤ: $_forecastDays يوم',
                        _forecastDays.toDouble(),
                        1, 365,
                        (v) => setState(() => _forecastDays = v.round()),
                      ),
                      _buildSliderTile(
                        'الأشهر التاريخية',
                        'عدد أشهر البيانات التاريخية للتحليل: $_historicalMonths شهر',
                        _historicalMonths.toDouble(),
                        1, 24,
                        (v) => setState(() => _historicalMonths = v.round()),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _buildSection(
                    'طريقة التنبؤ',
                    [
                      _buildDropdownTile(
                        'طريقة التنبؤ',
                        'الخوارزمية المستخدمة للتنبؤ',
                        _predictionMethod,
                        ['moving_average', 'weighted_moving_average', 'exponential_smoothing', 'combined'],
                        ['المتوسط المتحرك', 'المتوسط المرجح', 'التمليس الأسي', 'مدمج'],
                        (v) => setState(() => _predictionMethod = v!),
                      ),
                      _buildSwitchTile(
                        'التعديل الموسمي',
                        'مراعاة الأنماط الموسمية (يوم الأسبوع)',
                        _seasonalAdjustment,
                        (v) => setState(() => _seasonalAdjustment = v),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _buildSection(
                    'إعدادات المخزون والطلب',
                    [
                      _buildSliderTile(
                        'مضاعف مخزون الأمان',
                        'مستوى مخزون الأمان: ${_safetyStockMultiplier.toStringAsFixed(1)}x',
                        _safetyStockMultiplier,
                        0.5, 5.0,
                        (v) => setState(() => _safetyStockMultiplier = v),
                        divisions: 45,
                      ),
                      _buildSliderTile(
                        'أيام المهلة (Lead Time)',
                        'الوقت المتوقع لوصول الطلب: $_leadTimeDays يوم',
                        _leadTimeDays.toDouble(),
                        1, 90,
                        (v) => setState(() => _leadTimeDays = v.round()),
                      ),
                      _buildSliderTile(
                        'حد الثقة',
                        'الحد الأدنى للثقة للطلب التلقائي: ${(_confidenceThreshold * 100).toStringAsFixed(0)}%',
                        _confidenceThreshold,
                        0.1, 1.0,
                        (v) => setState(() => _confidenceThreshold = v),
                        divisions: 9,
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _buildSection(
                    'الطلب التلقائي',
                    [
                      _buildSwitchTile(
                        'تفعيل الطلب التلقائي',
                        'تمكين إنشاء اقتراحات الطلب التلقائي',
                        _autoOrderEnabled,
                        (v) => setState(() => _autoOrderEnabled = v),
                      ),
                    ],
                  ),
                  const SizedBox(height: 80),
                ],
              ),
            ),
    );
  }

  Widget _buildSection(String title, List<Widget> children) {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: const BoxDecoration(
              color: Color(0xFF1A237E),
              borderRadius: BorderRadius.only(
                topLeft: Radius.circular(12),
                topRight: Radius.circular(12),
              ),
            ),
            child: Text(
              title,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
          ...children,
        ],
      ),
    );
  }

  Widget _buildSwitchTile(
      String title, String subtitle, bool value, Function(bool) onChanged) {
    return SwitchListTile(
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.w500)),
      subtitle: Text(subtitle, style: const TextStyle(fontSize: 12)),
      value: value,
      activeColor: const Color(0xFF1A237E),
      onChanged: onChanged,
    );
  }

  Widget _buildDropdownTile(
      String title,
      String subtitle,
      String value,
      List<String> values,
      List<String> labels,
      Function(String?) onChanged) {
    return ListTile(
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.w500)),
      subtitle: Text(subtitle, style: const TextStyle(fontSize: 12)),
      trailing: DropdownButton<String>(
        value: value,
        underline: const SizedBox(),
        items: List.generate(values.length, (i) {
          return DropdownMenuItem(value: values[i], child: Text(labels[i]));
        }),
        onChanged: onChanged,
      ),
    );
  }

  Widget _buildSliderTile(
      String title,
      String subtitle,
      double value,
      double min,
      double max,
      Function(double) onChanged,
      {int? divisions}) {
    return Column(
      children: [
        ListTile(
          title: Text(title, style: const TextStyle(fontWeight: FontWeight.w500)),
          subtitle: Text(subtitle, style: const TextStyle(fontSize: 12)),
        ),
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16),
          child: Slider(
            value: value,
            min: min,
            max: max,
            divisions: divisions,
            activeColor: const Color(0xFF1A237E),
            onChanged: onChanged,
          ),
        ),
      ],
    );
  }
}

