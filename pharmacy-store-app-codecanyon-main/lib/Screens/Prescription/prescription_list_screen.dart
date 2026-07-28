import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:image_picker/image_picker.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/DrugInteraction/drug_interaction_check_screen.dart';
import 'package:mobile_pos/Screens/Prescription/repo/prescription_repo.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';
import 'package:mobile_pos/generated/l10n.dart' as lang;
import 'package:mobile_pos/app_config/api_config.dart';
import 'package:mobile_pos/constant.dart';

class PrescriptionListScreen extends StatefulWidget {
  const PrescriptionListScreen({super.key});

  @override
  State<PrescriptionListScreen> createState() => _PrescriptionListScreenState();
}

class _PrescriptionListScreenState extends State<PrescriptionListScreen> {
  final ScrollController _scrollController = ScrollController();
  final PagingController<int, dynamic> _pagingController = PagingController(firstPageKey: 1);
  final PrescriptionRepo _prescriptionRepo = PrescriptionRepo();
  final TextEditingController _searchController = TextEditingController();

  Future<void> _fetchPrescriptions(int pageKey) async {
    try {
      final data = await _prescriptionRepo.getPrescriptions(
        search: _searchController.text,
        nextPage: pageKey.toString(),
      );
      if (data?.data != null) {
        final newItems = data!.data!.prescriptions ?? [];
        final isLastPage = data.data!.lastPage == data.data!.currentPage;

        if (isLastPage) {
          _pagingController.appendLastPage(newItems);
        } else {
          final nextPageKey = pageKey + 1;
          _pagingController.appendPage(newItems, nextPageKey);
        }
      }
    } catch (error) {
      _pagingController.error = error;
    }
  }

  @override
  void initState() {
    _pagingController.addPageRequestListener(_fetchPrescriptions);
    super.initState();
  }

  @override
  void dispose() {
    _pagingController.dispose();
    _scrollController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _deletePrescription(int id) async {
    try {
      await _prescriptionRepo.deletePrescription(id);
      _pagingController.refresh();
      EasyLoading.showSuccess('Prescription deleted successfully');
    } catch (e) {
      EasyLoading.showError('Failed to delete prescription');
    }
  }

  void _showDeleteConfirmation(int id) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Prescription'),
        content: const Text('Are you sure you want to delete this prescription?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              _deletePrescription(id);
            },
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
  }

  void _showLinkToSaleDialog(dynamic prescription) {
    final saleIdController = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Link to Sale'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Enter Sale Invoice ID to link:'),
            const SizedBox(height: 12),
            TextField(
              controller: saleIdController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                hintText: 'Sale ID',
                border: OutlineInputBorder(),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () async {
              if (saleIdController.text.isNotEmpty) {
                try {
                  await _prescriptionRepo.linkToSale(
                    prescriptionId: prescription.id,
                    saleId: int.parse(saleIdController.text),
                  );
                  _pagingController.refresh();
                  EasyLoading.showSuccess('Linked to sale successfully');
                  Navigator.pop(ctx);
                } catch (e) {
                  EasyLoading.showError('Failed to link: $e');
                }
              }
            },
            style: TextButton.styleFrom(foregroundColor: kMainColor),
            child: const Text('Link'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final language = lang.S.of(context);

    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        surfaceTintColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        title: Text(
          'Prescriptions',
          style: theme.textTheme.titleLarge?.copyWith(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.medical_services_outlined, color: Colors.white),
            tooltip: 'Check Drug Interactions',
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => const DrugInteractionCheckScreen(),
                ),
              );
            },
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.only(left: 16, right: 16, top: 16),
            child: SizedBox(
              height: 48,
              child: TextFormField(
                controller: _searchController,
                onChanged: (value) {
                  if (value.isEmpty) {
                    _pagingController.refresh();
                  }
                },
                onFieldSubmitted: (value) async {
                  if (_searchController.text.isNotEmpty) {
                    _pagingController.refresh();
                  }
                },
                decoration: InputDecoration(
                  contentPadding: const EdgeInsets.all(10),
                  prefixIcon: const Icon(
                    FeatherIcons.search,
                    color: kNutral700,
                  ),
                  hintText: language.searchH,
                  enabledBorder: OutlineInputBorder(
                    borderSide: const BorderSide(color: kOutlineColor),
                    borderRadius: BorderRadius.circular(30),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderSide: const BorderSide(color: kMainColor),
                    borderRadius: BorderRadius.circular(30),
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 8),
          Divider(color: kOutlineBorder, thickness: 1.0),
          Expanded(
            child: RefreshIndicator.adaptive(
              onRefresh: () async => _pagingController.refresh(),
              child: PagedListView(
                padding: EdgeInsets.zero,
                pagingController: _pagingController,
                scrollController: _scrollController,
                physics: const AlwaysScrollableScrollPhysics(),
                builderDelegate: PagedChildBuilderDelegate<dynamic>(
                  newPageProgressIndicatorBuilder: (context) => const Center(
                    child: CircularProgressIndicator(color: kMainColor),
                  ),
                  noItemsFoundIndicatorBuilder: (context) => Padding(
                    padding: const EdgeInsets.all(20.0),
                    child: Center(
                      child: EmptyListWidget(title: 'No prescriptions found'),
                    ),
                  ),
                  itemBuilder: (context, item, index) => Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 6.0),
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: kOutlineColor.withValues(alpha: 0.3)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Prescription Image Thumbnail
                              ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: Container(
                                  width: 80,
                                  height: 80,
                                  color: kMainColor.withValues(alpha: 0.1),
                                  child: item.image != null
                                      ? Image.network(
                                          '${APIConfig.domain}${item.image}',
                                          fit: BoxFit.cover,
                                          errorBuilder: (context, error, stackTrace) => const Icon(
                                            Icons.image,
                                            color: kGreyTextColor,
                                            size: 40,
                                          ),
                                        )
                                      : const Icon(Icons.image, color: kGreyTextColor, size: 40),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    // Party Name
                                    Row(
                                      children: [
                                        const Icon(Icons.person_outline, size: 16, color: kGreyTextColor),
                                        const SizedBox(width: 4),
                                        Expanded(
                                          child: Text(
                                            item.party?.name ?? 'Walk-in Customer',
                                            style: theme.textTheme.titleSmall?.copyWith(
                                              fontWeight: FontWeight.w600,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 4),
                                    // Phone
                                    if (item.party?.phone != null)
                                      Row(
                                        children: [
                                          const Icon(Icons.phone_outlined, size: 14, color: kGreyTextColor),
                                          const SizedBox(width: 4),
                                          Text(
                                            item.party!.phone,
                                            style: theme.textTheme.bodySmall?.copyWith(color: kGreyTextColor),
                                          ),
                                        ],
                                      ),
                                    const SizedBox(height: 4),
                                    // Invoice if linked
                                    if (item.sale?.invoiceNumber != null)
                                      Row(
                                        children: [
                                          const Icon(Icons.receipt_outlined, size: 14, color: kGreyTextColor),
                                          const SizedBox(width: 4),
                                          Text(
                                            'Invoice: ${item.sale!.invoiceNumber}',
                                            style: theme.textTheme.bodySmall?.copyWith(color: kMainColor),
                                          ),
                                        ],
                                      ),
                                  ],
                                ),
                              ),
                              // Status Badge
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: item.status == 'used'
                                      ? kMainColor.withValues(alpha: 0.1)
                                      : Colors.orange.withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  item.status?.toUpperCase() ?? 'PENDING',
                                  style: theme.textTheme.bodySmall?.copyWith(
                                    color: item.status == 'used' ? kMainColor : Colors.orange,
                                    fontWeight: FontWeight.w600,
                                    fontSize: 10,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          // Notes if available
                          if (item.notes != null && item.notes.isNotEmpty)
                            Padding(
                              padding: const EdgeInsets.only(bottom: 8),
                              child: Text(
                                item.notes,
                                style: theme.textTheme.bodySmall?.copyWith(
                                  color: kGreyTextColor,
                                  fontStyle: FontStyle.italic,
                                ),
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          // Date and Actions
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  const Icon(Icons.calendar_today, size: 14, color: kGreyTextColor),
                                  const SizedBox(width: 4),
                                  Text(
                                    item.createdAt != null
                                        ? DateFormat('dd/MM/yyyy').format(DateTime.parse(item.createdAt))
                                        : '',
                                    style: theme.textTheme.bodySmall?.copyWith(color: kGreyTextColor),
                                  ),
                                ],
                              ),
                              Row(
                                children: [
                                  if (item.status == 'pending')
                                    IconButton(
                                      icon: const Icon(Icons.link, size: 18, color: kMainColor),
                                      onPressed: () {
                                        _showLinkToSaleDialog(item);
                                      },
                                      tooltip: 'Link to Sale',
                                      padding: EdgeInsets.zero,
                                      constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                                    ),
                                  IconButton(
                                    icon: const Icon(Icons.delete_outline, size: 18, color: Colors.red),
                                    onPressed: () => _showDeleteConfirmation(item.id),
                                    tooltip: 'Delete',
                                    padding: EdgeInsets.zero,
                                    constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: Colors.transparent,
        elevation: 0,
        onPressed: () async {
          final result = await Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => const AddPrescriptionScreen(),
            ),
          );
          if (result == true) {
            _pagingController.refresh();
          }
        },
        label: Container(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xff14b8a6), Color(0xFF00987F)],
              begin: Alignment.centerLeft,
              end: Alignment.centerRight,
            ),
            borderRadius: BorderRadius.circular(30),
            boxShadow: [
              BoxShadow(
                color: const Color(0xff14AE5C).withValues(alpha: 0.3),
                blurRadius: 20,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.add, color: Colors.white),
              const SizedBox(width: 8),
              Text(
                'Add Prescription',
                style: theme.textTheme.bodyMedium?.copyWith(color: Colors.white),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class AddPrescriptionScreen extends StatefulWidget {
  const AddPrescriptionScreen({super.key});

  @override
  State<AddPrescriptionScreen> createState() => _AddPrescriptionScreenState();
}

class _AddPrescriptionScreenState extends State<AddPrescriptionScreen> {
  final _formKey = GlobalKey<FormState>();
  final _notesController = TextEditingController();
  final PrescriptionRepo _prescriptionRepo = PrescriptionRepo();
  final ImagePicker _picker = ImagePicker();
  File? _selectedImage;
  String? _selectedPartyId;

  Future<void> _pickImage() async {
    final XFile? image = await _picker.pickImage(source: ImageSource.gallery);
    if (image != null) {
      setState(() {
        _selectedImage = File(image.path);
      });
    }
  }

  Future<void> _submit() async {
    if (_selectedImage == null) {
      EasyLoading.showError('Please select an image');
      return;
    }

    EasyLoading.show();
    try {
      await _prescriptionRepo.createPrescription(
        image: _selectedImage!,
        partyId: _selectedPartyId,
        notes: _notesController.text,
      );
      EasyLoading.showSuccess('Prescription created successfully');
      Navigator.pop(context, true);
    } catch (e) {
      EasyLoading.showError('Failed to create prescription');
    }
  }

  @override
  void dispose() {
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        surfaceTintColor: Colors.white,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        title: Text(
          'Add Prescription',
          style: theme.textTheme.titleLarge?.copyWith(
            color: Colors.white,
            fontSize: 20,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: true,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Image Picker
              GestureDetector(
                onTap: _pickImage,
                child: Container(
                  width: double.infinity,
                  height: 200,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: kOutlineColor),
                  ),
                  child: _selectedImage != null
                      ? ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: Image.file(
                            _selectedImage!,
                            fit: BoxFit.cover,
                            width: double.infinity,
                            height: 200,
                          ),
                        )
                      : Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.add_photo_alternate_outlined, size: 50, color: kGreyTextColor),
                            const SizedBox(height: 8),
                            Text(
                              'Tap to upload prescription image',
                              style: theme.textTheme.bodyMedium?.copyWith(color: kGreyTextColor),
                            ),
                          ],
                        ),
                ),
              ),
              const SizedBox(height: 16),

              // Notes
              TextFormField(
                controller: _notesController,
                maxLines: 4,
                decoration: InputDecoration(
                  labelText: 'Notes (optional)',
                  hintText: 'Enter any notes...',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: 32),

              // Submit Button
              SizedBox(
                width: double.infinity,
                height: 48,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: kMainColor,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                  ),
                  onPressed: _submit,
                  child: const Text(
                    'Save Prescription',
                    style: TextStyle(color: Colors.white, fontSize: 16),
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
