import 'package:flutter/material.dart';
import 'package:mobile_pos/Screens/Fefo/repo/fefo_repo.dart';
import 'package:mobile_pos/Screens/Fefo/model/fefo_model.dart';
import 'package:mobile_pos/Provider/language_provider.dart';
import 'package:provider/provider.dart';

class FefoSettingsScreen extends StatefulWidget {
  const FefoSettingsScreen({super.key});

  @override
  State<FefoSettingsScreen> createState() => _FefoSettingsScreenState();
}

class _FefoSettingsScreenState extends State<FefoSettingsScreen> {
  final FefoRepo _repo = FefoRepo();
  final _formKey = GlobalKey<FormState>();
  bool _loading = true;
  bool _saving = false;
  FefoSettings? _settings;

  @override
  void initState() {
    super.initState();
    _loadSettings();
  }

  Future<void> _loadSettings() async {
    setState(() => _loading = true);
    final settings = await _repo.getSettings();
    setState(() {
      _settings = settings ?? FefoSettings();
      _loading = false;
    });
  }

  Future<void> _saveSettings() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _saving = true);
    final success = await _repo.updateSettings(_settings!);
    setState(() => _saving = false);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(success ? 'Settings saved successfully' : 'Failed to save settings'),
          backgroundColor: success ? Colors.green : Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final lang = Provider.of<LanguageProvider>(context).currentLang;
    return Scaffold(
      appBar: AppBar(
        title: Text(lang == 'ar' ? 'إعدادات FEFO' : 'FEFO Settings'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // FEFO Enable Switch
                    Card(
                      child: SwitchListTile(
                        title: Text(lang == 'ar' ? 'تفعيل نظام FEFO' : 'Enable FEFO System'),
                        subtitle: Text(lang == 'ar'
                            ? 'ترتيب البضائع حسب تاريخ انتهاء الصلاحية (الأقرب أولاً)'
                            : 'Sort inventory by expiry date (Nearest First)'),
                        value: _settings?.fefoEnabled ?? true,
                        onChanged: (val) {
                          setState(() => _settings!.fefoEnabled = val);
                        },
                      ),
                    ),

                    const SizedBox(height: 16),

                    // Deduction Mode
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              lang == 'ar' ? 'وضع الخصم' : 'Deduction Mode',
                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 8),
                            RadioListTile<String>(
                              title: Text(lang == 'ar' ? 'تلقائي' : 'Automatic'),
                              subtitle: Text(lang == 'ar'
                                  ? 'يتم خصم الكميات تلقائياً من أقرب دفعة منتهية الصلاحية'
                                  : 'Automatically deduct from nearest expiry batch'),
                              value: 'automatic',
                              groupValue: _settings?.deductionMode,
                              onChanged: (val) {
                                setState(() => _settings!.deductionMode = val!);
                              },
                            ),
                            RadioListTile<String>(
                              title: Text(lang == 'ar' ? 'اقتراح يدوي' : 'Manual Suggestion'),
                              subtitle: Text(lang == 'ar'
                                  ? 'عرض اقتراح FEFO وترك الاختيار للمستخدم'
                                  : 'Show FEFO suggestions and let user choose'),
                              value: 'manual_suggestion',
                              groupValue: _settings?.deductionMode,
                              onChanged: (val) {
                                setState(() => _settings!.deductionMode = val!);
                              },
                            ),
                          ],
                        ),
                      ),
                    ),

                    const SizedBox(height: 16),

                    // Grace Days
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              lang == 'ar' ? 'فترة السماح (أيام)' : 'Grace Period (Days)',
                              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 8),
                            TextFormField(
                              initialValue: _settings?.expiryGraceDays.toString() ?? '30',
                              keyboardType: TextInputType.number,
                              decoration: InputDecoration(
                                labelText: lang == 'ar' ? 'عدد الأيام قبل انتهاء الصلاحية' : 'Days before expiry to flag',
                                border: const OutlineInputBorder(),
                                suffixText: lang == 'ar' ? 'يوم' : 'days',
                              ),
                              onChanged: (val) {
                                _settings!.expiryGraceDays = int.tryParse(val) ?? 30;
                              },
                              validator: (val) {
                                if (val == null || val.isEmpty) return 'Required';
                                final n = int.tryParse(val);
                                if (n == null || n < 1 || n > 365) return 'Between 1-365';
                                return null;
                              },
                            ),
                          ],
                        ),
                      ),
                    ),

                    const SizedBox(height: 16),

                    // Additional Settings
                    Card(
                      child: Column(
                        children: [
                          SwitchListTile(
                            title: Text(lang == 'ar' ? 'حذف المخزون منتهي الصلاحية تلقائياً' : 'Auto-remove expired stock'),
                            subtitle: Text(lang == 'ar'
                                ? 'ضبط الكمية إلى صفر عند انتهاء الصلاحية'
                                : 'Set stock to zero when expired'),
                            value: _settings?.autoDeductExpiredStock ?? false,
                            onChanged: (val) {
                              setState(() => _settings!.autoDeductExpiredStock = val);
                            },
                          ),
                          const Divider(height: 1),
                          SwitchListTile(
                            title: Text(lang == 'ar' ? 'إشعارات خصم FEFO' : 'FEFO deduction notifications'),
                            subtitle: Text(lang == 'ar'
                                ? 'إرسال إشعار عند خصم الكمية حسب FEFO'
                                : 'Send notification on FEFO deduction'),
                            value: _settings?.notifyOnFefoDeduction ?? true,
                            onChanged: (val) {
                              setState(() => _settings!.notifyOnFefoDeduction = val);
                            },
                          ),
                        ],
                      ),
                    ),

                    const SizedBox(height: 24),

                    // Save Button
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton(
                        onPressed: _saving ? null : _saveSettings,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Theme.of(context).primaryColor,
                          foregroundColor: Colors.white,
                        ),
                        child: _saving
                            ? const SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                              )
                            : Text(
                                lang == 'ar' ? 'حفظ الإعدادات' : 'Save Settings',
                                style: const TextStyle(fontSize: 16),
                              ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}

