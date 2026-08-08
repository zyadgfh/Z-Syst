import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/Screens/Insurance/model/insurance_model.dart';
import 'package:mobile_pos/Screens/Insurance/repo/insurance_repo.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';

class AddEditInsuranceCompanyScreen extends StatefulWidget {
  final InsuranceCompanyModel? company;

  const AddEditInsuranceCompanyScreen({super.key, this.company});

  @override
  State<AddEditInsuranceCompanyScreen> createState() =>
      _AddEditInsuranceCompanyScreenState();
}

class _AddEditInsuranceCompanyScreenState
    extends State<AddEditInsuranceCompanyScreen> {
  final InsuranceRepo _repo = InsuranceRepo();
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();
  final _addressController = TextEditingController();
  final _websiteController = TextEditingController();
  final _contactPersonController = TextEditingController();

  bool get isEditing => widget.company != null;

  @override
  void initState() {
    super.initState();
    if (isEditing) {
      final c = widget.company!;
      _nameController.text = c.name ?? '';
      _phoneController.text = c.phoneNumber ?? '';
      _emailController.text = c.email ?? '';
      _addressController.text = c.address ?? '';
      _websiteController.text = c.website ?? '';
      _contactPersonController.text = c.contactPerson ?? '';
    }
  }

  Future<void> _saveCompany() async {
    if (!_formKey.currentState!.validate()) return;

    EasyLoading.show(status: isEditing ? 'Updating...' : 'Creating...');

    final result = isEditing
        ? await _repo.updateCompany(
            id: widget.company!.id!,
            name: _nameController.text.trim(),
            phoneNumber: _phoneController.text.trim().isNotEmpty
                ? _phoneController.text.trim()
                : null,
            email: _emailController.text.trim().isNotEmpty
                ? _emailController.text.trim()
                : null,
            address: _addressController.text.trim().isNotEmpty
                ? _addressController.text.trim()
                : null,
            website: _websiteController.text.trim().isNotEmpty
                ? _websiteController.text.trim()
                : null,
            contactPerson: _contactPersonController.text.trim().isNotEmpty
                ? _contactPersonController.text.trim()
                : null,
          )
        : await _repo.createCompany(
            name: _nameController.text.trim(),
            phoneNumber: _phoneController.text.trim().isNotEmpty
                ? _phoneController.text.trim()
                : null,
            email: _emailController.text.trim().isNotEmpty
                ? _emailController.text.trim()
                : null,
            address: _addressController.text.trim().isNotEmpty
                ? _addressController.text.trim()
                : null,
            website: _websiteController.text.trim().isNotEmpty
                ? _websiteController.text.trim()
                : null,
            contactPerson: _contactPersonController.text.trim().isNotEmpty
                ? _contactPersonController.text.trim()
                : null,
          );

    EasyLoading.dismiss();

    if (result != null && mounted) {
      EasyLoading.showSuccess(
        result.message ?? 'Company ${isEditing ? 'updated' : 'created'}',
      );
      Navigator.pop(context, true);
    } else {
      EasyLoading.showError('Failed to ${isEditing ? 'update' : 'create'}');
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: kWhite),
        title: Text(
          isEditing ? 'Edit Company' : 'Add Company',
          style: theme.textTheme.titleLarge?.copyWith(
            color: kWhite,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        actions: [
          TextButton(
            onPressed: _saveCompany,
            child: const Text('Save', style: TextStyle(color: kWhite)),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildTextField(
                theme,
                'Company Name',
                'Enter company name',
                _nameController,
                Icons.business_outlined,
              ),
              const SizedBox(height: 16),
              _buildTextField(
                theme,
                'Contact Person',
                'Enter contact person',
                _contactPersonController,
                Icons.person_outline,
              ),
              const SizedBox(height: 16),
              _buildTextField(
                theme,
                'Phone Number',
                'Enter phone number',
                _phoneController,
                Icons.phone_outlined,
              ),
              const SizedBox(height: 16),
              _buildTextField(
                theme,
                'Email',
                'Enter email',
                _emailController,
                Icons.email_outlined,
                keyboardType: TextInputType.emailAddress,
              ),
              const SizedBox(height: 16),
              _buildTextField(
                theme,
                'Website',
                'Enter website URL',
                _websiteController,
                Icons.web_outlined,
              ),
              const SizedBox(height: 16),
              _buildTextField(
                theme,
                'Address',
                'Enter address',
                _addressController,
                Icons.location_on_outlined,
                maxLines: 3,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTextField(
    ThemeData theme,
    String label,
    String hint,
    TextEditingController controller,
    IconData icon, {
    TextInputType? keyboardType,
    int maxLines = 1,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            color: kNutral700,
            fontWeight: FontWeight.w600,
          ),
        ),
        const SizedBox(height: 4),
        TextFormField(
          controller: controller,
          keyboardType: keyboardType,
          maxLines: maxLines,
          decoration: InputDecoration(
            hintText: hint,
            hintStyle: const TextStyle(color: kNutral600, fontSize: 13),
            fillColor: Colors.white,
            filled: true,
            prefixIcon: Icon(icon, color: kNutral700, size: 18),
            enabledBorder: OutlineInputBorder(
              borderSide: const BorderSide(color: kOutlineColor),
              borderRadius: BorderRadius.circular(12),
            ),
            focusedBorder: OutlineInputBorder(
              borderSide: const BorderSide(color: kMainColor),
              borderRadius: BorderRadius.circular(12),
            ),
          ),
          validator: (value) {
            if (label == 'Company Name' &&
                (value == null || value.trim().isEmpty)) {
              return 'Company name is required';
            }
            return null;
          },
        ),
      ],
    );
  }
}
