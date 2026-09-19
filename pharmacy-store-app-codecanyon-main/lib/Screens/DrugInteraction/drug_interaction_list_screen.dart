import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_feather_icons/flutter_feather_icons.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/Screens/DrugInteraction/model/drug_interaction_model.dart';
import 'package:mobile_pos/Screens/DrugInteraction/repo/drug_interaction_repo.dart';
import 'package:mobile_pos/Screens/widget/empty_widgets.dart';
import 'package:mobile_pos/constant.dart';
import '../../generated/l10n.dart' as lang;

class DrugInteractionListScreen extends StatefulWidget {
  const DrugInteractionListScreen({super.key});

  @override
  State<DrugInteractionListScreen> createState() => _DrugInteractionListScreenState();
}

class _DrugInteractionListScreenState extends State<DrugInteractionListScreen> {
  final PagingController<int, DrugInteractionData> _pagingController = PagingController(firstPageKey: 1);
  final DrugInteractionRepo _repo = DrugInteractionRepo();
  final TextEditingController _searchController = TextEditingController();
  String? _selectedSeverity;

  @override
  void initState() {
    _pagingController.addPageRequestListener(_fetchInteractions);
    super.initState();
  }

  @override
  void dispose() {
    _pagingController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _fetchInteractions(int pageKey) async {
    try {
      final data = await _repo.getDrugInteractions(
        search: _searchController.text,
        nextPage: pageKey.toString(),
        severity: _selectedSeverity,
      );
      if (data?.data != null) {
        final newItems = data!.data!.interactions ?? [];
        final isLastPage = data.data!.lastPage == data.data!.currentPage;

        if (isLastPage) {
          _pagingController.appendLastPage(newItems);
        } else {
          _pagingController.appendPage(newItems, pageKey + 1);
        }
      }
    } catch (error) {
      _pagingController.error = error;
    }
  }

  Future<void> _deleteInteraction(int id) async {
    try {
      await _repo.deleteDrugInteraction(id);
      _pagingController.refresh();
      EasyLoading.showSuccess('Interaction deleted');
    } catch (e) {
      EasyLoading.showError('Failed to delete');
    }
  }

  void _showDeleteConfirmation(int id) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Delete Interaction'),
        content: const Text('Are you sure you want to delete this drug interaction?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              _deleteInteraction(id);
            },
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
  }

  Color _severityColor(String severity) {
    switch (severity) {
      case 'contraindicated': return Colors.red;
      case 'severe': return const Color(0xFFEA580C);
      case 'moderate': return const Color(0xFFEAB308);
      case 'minor': return const Color(0xFF22C55E);
      default: return kGreyTextColor;
    }
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
          'Drug Interactions',
          style: theme.textTheme.titleLarge?.copyWith(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w600),
        ),
        centerTitle: true,
      ),
      body: Column(
        children: [
          // Search & Filter
          Padding(
            padding: const EdgeInsets.only(left: 16, right: 16, top: 16),
            child: Row(
              children: [
                Expanded(
                  flex: 3,
                  child: SizedBox(
                    height: 48,
                    child: TextFormField(
                      controller: _searchController,
                      onChanged: (value) {
                        if (value.isEmpty) {
                          _pagingController.refresh();
                        } else {
                          _pagingController.refresh();
                        }
                      },
                      decoration: InputDecoration(
                        contentPadding: const EdgeInsets.all(10),
                        prefixIcon: const Icon(FeatherIcons.search, color: kNutral700),
                        hintText: '${language.searchH}...',
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
                const SizedBox(width: 8),
                // Severity filter
                SizedBox(
                  height: 48,
                  child: DropdownButtonFormField<String>(
                    value: _selectedSeverity,
                    decoration: InputDecoration(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 12),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(30)),
                      enabledBorder: OutlineInputBorder(
                        borderSide: const BorderSide(color: kOutlineColor),
                        borderRadius: BorderRadius.circular(30),
                      ),
                    ),
                    hint: const Text('Severity', style: TextStyle(fontSize: 12)),
                    items: const [
                      DropdownMenuItem(value: null, child: Text('All', style: TextStyle(fontSize: 12))),
                      DropdownMenuItem(value: 'contraindicated', child: Text('Contraindicated', style: TextStyle(fontSize: 12, color: Colors.red))),
                      DropdownMenuItem(value: 'severe', child: Text('Severe', style: TextStyle(fontSize: 12, color: Color(0xFFEA580C)))),
                      DropdownMenuItem(value: 'moderate', child: Text('Moderate', style: TextStyle(fontSize: 12, color: Color(0xFFEAB308)))),
                      DropdownMenuItem(value: 'minor', child: Text('Minor', style: TextStyle(fontSize: 12, color: Color(0xFF22C55E)))),
                    ],
                    onChanged: (value) {
                      setState(() {
                        _selectedSeverity = value;
                        _pagingController.refresh();
                      });
                    },
                  ),
                ),
              ],
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
                physics: const AlwaysScrollableScrollPhysics(),
                builderDelegate: PagedChildBuilderDelegate<DrugInteractionData>(
                  newPageProgressIndicatorBuilder: (context) => const Center(child: CircularProgressIndicator(color: kMainColor)),
                  noItemsFoundIndicatorBuilder: (context) => Padding(
                    padding: const EdgeInsets.all(20.0),
                    child: Center(child: EmptyListWidget(title: 'No interactions found')),
                  ),
                  itemBuilder: (context, item, index) => Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 6.0),
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: _severityColor(item.severity ?? '').withValues(alpha: 0.3)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Drug pair row
                          Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        const Icon(Icons.medication, size: 14, color: kGreyTextColor),
                                        const SizedBox(width: 4),
                                        Expanded(
                                          child: Text(
                                            item.drugAName ?? 'N/A',
                                            style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
                                          ),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 2),
                                    Row(
                                      children: [
                                        const Icon(Icons.medication, size: 14, color: kGreyTextColor),
                                        const SizedBox(width: 4),
                                        Expanded(
                                          child: Text(
                                            item.drugBName ?? 'N/A',
                                            style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: _severityColor(item.severity ?? '').withValues(alpha: 0.15),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Text(
                                  item.severity?.toUpperCase() ?? 'N/A',
                                  style: TextStyle(
                                    color: _severityColor(item.severity ?? ''),
                                    fontSize: 10,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          if (item.description != null && item.description!.isNotEmpty) ...[
                            const SizedBox(height: 6),
                            Text(
                              item.description!.length > 100 ? '${item.description!.substring(0, 100)}...' : item.description!,
                              style: theme.textTheme.bodySmall?.copyWith(color: kGreyTextColor),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                          const SizedBox(height: 6),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              if (item.createdAt != null)
                                Row(
                                  children: [
                                    const Icon(Icons.calendar_today, size: 12, color: kGreyTextColor),
                                    const SizedBox(width: 4),
                                    Text(
                                      DateFormat('dd/MM/yyyy').format(DateTime.parse(item.createdAt!)),
                                      style: theme.textTheme.bodySmall?.copyWith(color: kGreyTextColor, fontSize: 11),
                                    ),
                                  ],
                                ),
                              Row(
                                children: [
                                  IconButton(
                                    icon: const Icon(Icons.edit_outlined, size: 18, color: kMainColor),
                                    onPressed: () => _showEditDialog(context, item),
                                    padding: EdgeInsets.zero,
                                    constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                                    tooltip: 'Edit',
                                  ),
                                  IconButton(
                                    icon: const Icon(Icons.delete_outline, size: 18, color: Colors.red),
                                    onPressed: () => _showDeleteConfirmation(item.id!),
                                    padding: EdgeInsets.zero,
                                    constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                                    tooltip: 'Delete',
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
        onPressed: () => _showAddEditDialog(context),
        label: Container(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          decoration: BoxDecoration(
            gradient: const LinearGradient(colors: [Color(0xff14b8a6), Color(0xFF00987F)], begin: Alignment.centerLeft, end: Alignment.centerRight),
            borderRadius: BorderRadius.circular(30),
            boxShadow: [BoxShadow(color: const Color(0xff14AE5C).withValues(alpha: 0.3), blurRadius: 20, offset: const Offset(0, 8))],
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.add, color: Colors.white),
              const SizedBox(width: 8),
              Text('Add Interaction', style: theme.textTheme.bodyMedium?.copyWith(color: Colors.white)),
            ],
          ),
        ),
      ),
    );
  }

  void _showAddEditDialog(BuildContext context, [DrugInteractionData? existing]) {
    final isEdit = existing != null;
    final nameAController = TextEditingController(text: existing?.drugAName ?? '');
    final nameBController = TextEditingController(text: existing?.drugBName ?? '');
    final descController = TextEditingController(text: existing?.description ?? '');
    final mechController = TextEditingController(text: existing?.mechanism ?? '');
    final recoController = TextEditingController(text: existing?.recommendation ?? '');
    final sourceController = TextEditingController(text: existing?.source ?? '');
    String severity = existing?.severity ?? 'moderate';

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(isEdit ? 'Edit Interaction' : 'Add Interaction'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: nameAController,
                decoration: const InputDecoration(labelText: 'Drug A Name *', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: nameBController,
                decoration: const InputDecoration(labelText: 'Drug B Name *', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                value: severity,
                decoration: const InputDecoration(labelText: 'Severity *', border: OutlineInputBorder()),
                items: const [
                  DropdownMenuItem(value: 'contraindicated', child: Text('Contraindicated - Do Not Use Together')),
                  DropdownMenuItem(value: 'severe', child: Text('Severe - Use with Caution')),
                  DropdownMenuItem(value: 'moderate', child: Text('Moderate - Monitor Therapy')),
                  DropdownMenuItem(value: 'minor', child: Text('Minor - Limited Effect')),
                ],
                onChanged: (v) => severity = v ?? 'moderate',
              ),
              const SizedBox(height: 8),
              TextField(
                controller: descController,
                maxLines: 3,
                decoration: const InputDecoration(labelText: 'Description *', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: mechController,
                maxLines: 2,
                decoration: const InputDecoration(labelText: 'Mechanism (optional)', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: recoController,
                maxLines: 2,
                decoration: const InputDecoration(labelText: 'Recommendation (optional)', border: OutlineInputBorder()),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: sourceController,
                decoration: const InputDecoration(labelText: 'Source (optional)', border: OutlineInputBorder()),
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: kMainColor, foregroundColor: Colors.white),
            onPressed: () async {
              if (nameAController.text.isEmpty || nameBController.text.isEmpty || descController.text.isEmpty) {
                EasyLoading.showInfo('Drug A, Drug B, and Description are required');
                return;
              }
              Navigator.pop(ctx);
              EasyLoading.show();
              try {
                if (isEdit) {
                  await _repo.updateDrugInteraction(
                    id: existing.id!,
                    drugAName: nameAController.text,
                    drugBName: nameBController.text,
                    severity: severity,
                    description: descController.text,
                    mechanism: mechController.text,
                    recommendation: recoController.text,
                    source: sourceController.text,
                  );
                  EasyLoading.showSuccess('Updated successfully');
                } else {
                  await _repo.createDrugInteraction(
                    drugAName: nameAController.text,
                    drugBName: nameBController.text,
                    severity: severity,
                    description: descController.text,
                    mechanism: mechController.text,
                    recommendation: recoController.text,
                    source: sourceController.text,
                  );
                  EasyLoading.showSuccess('Created successfully');
                }
                _pagingController.refresh();
              } catch (e) {
                EasyLoading.showError('Failed: $e');
              }
            },
            child: Text(isEdit ? 'Update' : 'Save'),
          ),
        ],
      ),
    );
  }

  void _showEditDialog(BuildContext context, DrugInteractionData item) {
    _showAddEditDialog(context, item);
  }
}

