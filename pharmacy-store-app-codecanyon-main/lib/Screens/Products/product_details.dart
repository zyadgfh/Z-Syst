import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconly/iconly.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/app_config/api_config.dart';
import 'package:mobile_pos/Screens/DrugInteraction/drug_interaction_check_screen.dart';
import 'package:mobile_pos/Screens/DrugInteraction/model/drug_interaction_model.dart';
import 'package:mobile_pos/Screens/DrugInteraction/repo/drug_interaction_repo.dart';
import 'package:mobile_pos/Screens/Products/Repo/product_repo.dart';
import 'package:mobile_pos/Screens/Products/add%20product/add_product.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/key_value_widget.dart';
import 'package:mobile_pos/constant.dart';
import 'package:mobile_pos/currency.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'Providers/product_provider.dart';
import 'Widgets/widgets.dart';
import 'add product/add_stock.dart';

class ProductDetails extends StatefulWidget {
  const ProductDetails({
    super.key,
    required this.id,
    this.pagingController,
  });
  final num id;
  final PagingController? pagingController;
  @override
  State<ProductDetails> createState() => _ProductDetailsState();
}

class _ProductDetailsState extends State<ProductDetails> {
  @override
  void dispose() {
    _carouselController.dispose();
    super.dispose();
  }

  int pageIndex = 0;
  PageController? bannerController;

  final CarouselController _carouselController = CarouselController(initialItem: 1);

  TextEditingController quantityController = TextEditingController();
  bool isClicked = false;
  bool showSalePriceDetails = false;
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return Consumer(
      builder: (BuildContext context, WidgetRef ref, Widget? child) {
        final details = ref.watch(productDetailsProvider(widget.id.toString()));
        return details.when(
          data: (snapShot) {
            return AcnooScafoldWidget(
              appBar: AppBar(
                backgroundColor: Colors.transparent,
                surfaceTintColor: Colors.transparent,
                iconTheme: IconThemeData(color: kWhite),
                title: Text(
                  l.S.of(context).productDetails,
                  //'Product Details',
                  style: theme.textTheme.titleLarge?.copyWith(color: kWhite, fontSize: 20, fontWeight: FontWeight.w600),
                ),
                actions: [
                  ///______Edit_product____________________________________
                  Padding(
                    padding: const EdgeInsets.only(right: 20),
                    child: GestureDetector(
                        onTap: () {
                          Navigator.push(
                            context,
                            DialogRoute(
                              context: context,
                              builder: (context) => AddProduct(
                                productModel: snapShot,
                                isFromHome: false,
                                pagingController: widget.pagingController,
                              ),
                            ),
                          );
                        },
                        child: Icon(IconlyBold.edit)),
                  ),

                  ///_______Delete_Product_________________________________________
                  Padding(
                    padding: const EdgeInsets.only(right: 10),
                    child: GestureDetector(
                      onTap: () async {
                        final result = await showDeleteAlert(context: context, itemsName: lang.product);

                        if (result) {
                          await ProductRepo().deleteProduct(id: snapShot.id.toString(), context: context, ref: ref);
                          widget.pagingController?.refresh();
                        }
                      },
                      child: Icon(IconlyBold.delete),
                    ),
                  )
                ],
                centerTitle: true,
                // iconTheme: const IconThemeData(color: Colors.white),
                elevation: 0.0,
              ),
              bottomNavigationBar: Padding(
                padding: const EdgeInsets.all(10.0),
                child: ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(backgroundColor: kMainColor, foregroundColor: kWhite),
                  onPressed: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => AddStock(
                          productModel: snapShot,
                        ),
                      ),
                    );
                  },
                  label: Text(lang.addStock),
                  icon: Icon(Icons.add),
                ),
              ),
              body: Padding(
                padding: EdgeInsets.only(top: 5),
                child: SingleChildScrollView(
                  child: Padding(
                    padding: const EdgeInsets.only(left: 16, right: 16, top: 13),
                    child: Column(
                      children: [
                        // Top section
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    snapShot.productName ?? 'n/a',
                                    maxLines: 3,
                                    style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600),
                                  ),
                                  RichText(
                                    text: TextSpan(
                                      text: '${lang.currentStock}: ${snapShot.stockSum ?? '0'}',
                                      style: theme.textTheme.bodyMedium?.copyWith(
                                        color: kNutral700,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            SizedBox(width: 8),
                            snapShot.images?.isNotEmpty ?? false
                                ? SizedBox(
                                    height: 99,
                                    width: 118,
                                    child: PageView.builder(
                                      controller: bannerController,
                                      itemCount: snapShot.images?.length ?? 0,
                                      onPageChanged: (value) => setState(() {
                                        pageIndex = value;
                                      }),
                                      itemBuilder: (_, index) {
                                        return Stack(
                                          alignment: Alignment.bottomCenter,
                                          children: [
                                            Container(
                                              height: 99,
                                              width: 118,
                                              decoration: BoxDecoration(
                                                borderRadius: BorderRadius.circular(8),
                                                image: DecorationImage(
                                                  fit: BoxFit.cover,
                                                  image: NetworkImage(
                                                    '${APIConfig.domain}${snapShot.images?[index].toString() ?? ''}',
                                                  ),
                                                ),
                                              ),
                                            ),
                                            // Progress bar container
                                            Positioned(
                                              left: 4,
                                              right: 4,
                                              bottom: 4,
                                              child: Stack(
                                                children: [
                                                  // Background progress (faded)
                                                  Container(
                                                    width: 118,
                                                    height: 6.0,
                                                    decoration: BoxDecoration(
                                                      borderRadius: BorderRadius.circular(8),
                                                      color: Theme.of(context).colorScheme.primaryContainer.withValues(alpha: 0.1),
                                                    ),
                                                  ),
                                                  // Foreground progress (active)
                                                  AnimatedContainer(
                                                    duration: const Duration(milliseconds: 500),
                                                    curve: Curves.easeInOut,
                                                    width: (118 * (pageIndex + 1)) / (snapShot.images?.length ?? 1), // Calculate percentage width
                                                    height: 6.0,
                                                    decoration: BoxDecoration(
                                                      borderRadius: BorderRadius.circular(8),
                                                      color: Theme.of(context).colorScheme.primaryContainer.withValues(alpha: 0.4),
                                                    ),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ],
                                        );
                                      },
                                    ),
                                  )
                                : Container(
                                    height: 118,
                                    width: 98,
                                    decoration: BoxDecoration(
                                      borderRadius: BorderRadius.circular(4),
                                      image: DecorationImage(
                                        image: AssetImage(noProductImageUrl),
                                      ),
                                    ),
                                  ),
                          ],
                        ),
                        SizedBox(height: 8),
                        Container(
                          padding: EdgeInsets.symmetric(horizontal: 16),
                          decoration: BoxDecoration(
                            color: Color(0xffF7F7F7),
                            border: Border(
                              top: BorderSide(
                                color: Color(0xff9090AD).withValues(alpha: 0.3),
                              ),
                            ),
                          ),
                          child: Column(
                            children: [
                              SizedBox(height: 16),
                              ...{
                                lang.barCode: snapShot.productCode ?? 'n/a',
                                lang.category: snapShot.category?.categoryName ?? 'n/a',
                                lang.medicineType: snapShot.medicineType?.name ?? 'n/a',
                                lang.strength: snapShot.meta?.strength ?? 'n/a',
                                lang.genericName: snapShot.meta?.genericName ?? 'n/a',
                                lang.supplier: snapShot.manufacterer?.name ?? 'n/a',
                                lang.leafOrBoxSize: snapShot.boxSize?.name ?? 'n/a',
                                lang.unit: snapShot.unit?.unitName ?? 'n/a',
                                lang.quantity: snapShot.stockSum ?? '0',
                                lang.lowStock: snapShot.alertQty ?? '0',
                                lang.priceWithoutTax: '$currency${snapShot.productPurchasePriceWithoutTax ?? '0'}',
                                lang.priceWithTax: '$currency${snapShot.productPurchasePriceWithTax ?? '0'}',
                                lang.profitPercent: snapShot.profitPercent ?? '0',
                                lang.salePrice: '$currency${snapShot.productSalePrice ?? '0'}',
                                // Conditionally show Sale Price and extra details based on showSalePriceDetails
                                if (showSalePriceDetails) ...{
                                  lang.wholeSalePrice: '$currency${snapShot.productWholeSalePrice ?? '0'}',
                                  lang.taxType: snapShot.taxType ?? 'n/a',
                                  lang.shelf: snapShot.meta?.shelf ?? '0',
                                  lang.purchasePrice: '$currency${snapShot.productPurchasePriceWithoutTax ?? '0.0'}',
                                  lang.salePrice: '$currency${snapShot.productSalePrice?.toString() ?? '0.0'}',
                                },
                              }.entries.map(
                                (entry) {
                                  return KeyValueRow(
                                    title: entry.key,
                                    titleFlex: 6,
                                    description: entry.value.toString(),
                                    descriptionFlex: 8,
                                  );
                                },
                              ),
                              if (showSalePriceDetails)
                                Column(
                                  spacing: 10,
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      "${lang.description}:",
                                      style: TextStyle(fontWeight: FontWeight.w600),
                                    ),
                                    Text(snapShot.meta?.medicineDetails?.toString() ?? 'n/a')
                                  ],
                                ),
                              TextButton(
                                onPressed: () {
                                  setState(() {
                                    showSalePriceDetails = !showSalePriceDetails;
                                  });
                                },
                                child: Text(showSalePriceDetails ? lang.viewLess : lang.viewMore),
                              ),
                            ],
                          ),
                        ),
                        SizedBox(height: 12),

                        // Table
                        SizedBox(
                          width: double.maxFinite,
                          child: Theme(
                            data: ThemeData(
                              dividerColor: Color(0xffE6E6E6),
                            ),
                            child: DataTable(
                              headingRowColor: WidgetStateProperty.all(Color(0xffF2F2F2)),
                              columnSpacing: 20,
                              dividerThickness: 0,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: Color(0xffE6E6E6), width: 1),
                              ),
                              columns: [
                                DataColumn(
                                  label: Text(
                                    '${lang.batchNo}.',
                                    style: TextStyle(fontWeight: FontWeight.bold),
                                  ),
                                ),
                                DataColumn(
                                  label: Text(
                                    lang.stock,
                                    style: TextStyle(fontWeight: FontWeight.bold),
                                  ),
                                ),
                                DataColumn(
                                  label: Text(
                                    lang.expireDate,
                                    style: TextStyle(fontWeight: FontWeight.bold),
                                  ),
                                ),
                              ],
                              rows: List.generate(
                                snapShot.stocks?.length ?? 0,
                                (index) {
                                  var stock = snapShot.stocks?[index];

                                  return DataRow(
                                    cells: [
                                      DataCell(Text(stock?.batchNo?.toString() ?? 'n/a')),
                                      DataCell(Text(stock?.productStock?.toString() ?? '0')),
                                      DataCell(
                                        Text(
                                          (stock?.expireDate != null)
                                              ? DateFormat.yMMMd().format(
                                                  DateTime.parse(stock?.expireDate?.toString() ?? ''),
                                                )
                                              : 'N/A',
                                        ),
                                      ),
                                    ],
                                  );
                                },
                              ),
                            ),
                          ),
                        ),

                        SizedBox(height: 12),

                        // Drug Interactions Section
                        _DrugInteractionsSection(productId: widget.id.toInt(), productName: snapShot.productName ?? '', genericName: snapShot.meta?.genericName ?? ''),

                        SizedBox(height: 24),
                      ],
                    ),
                  ),
                ),
              ),
            );
          },
          error: (_, __) => Container(),
          loading: () => const Center(
            child: SizedBox(
              height: 40,
              width: 40,
              child: CircularProgressIndicator(),
            ),
          ),
        );
      },
    );
  }
}

// ────────────────────────────────────────────────────────────────
// Drug Interactions Section for Product Details
// ────────────────────────────────────────────────────────────────

class _DrugInteractionsSection extends StatefulWidget {
  final int productId;
  final String productName;
  final String genericName;

  const _DrugInteractionsSection({
    required this.productId,
    required this.productName,
    required this.genericName,
  });

  @override
  State<_DrugInteractionsSection> createState() => _DrugInteractionsSectionState();
}

class _DrugInteractionsSectionState extends State<_DrugInteractionsSection> {
  final DrugInteractionRepo _repo = DrugInteractionRepo();
  DrugInteractionCheckResponse? _result;
  bool _isLoading = false;
  bool _loaded = false;

  @override
  void initState() {
    super.initState();
    _loadInteractions();
  }

  Future<void> _loadInteractions() async {
    setState(() => _isLoading = true);
    try {
      final result = await _repo.checkInteractions(productIds: [widget.productId]);
      if (mounted) {
        setState(() {
          _result = result;
          _isLoading = false;
          _loaded = true;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _isLoading = false);
    }
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
    final interactions = _result?.data?.interactions ?? [];
    final total = _result?.data?.totalInteractions ?? 0;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Section Header
        Row(
          children: [
            const Icon(Icons.medical_services_outlined, size: 20, color: kMainColor),
            const SizedBox(width: 8),
            Text(
              'Drug Interactions',
              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
            ),
            const Spacer(),
            if (_isLoading)
              const SizedBox(
                width: 16, height: 16,
                child: CircularProgressIndicator(strokeWidth: 2, color: kMainColor),
              ),
            if (_loaded && total > 0)
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: Colors.red.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text('$total', style: const TextStyle(color: Colors.red, fontSize: 12, fontWeight: FontWeight.bold)),
              ),
            if (total > 0)
              IconButton(
                icon: const Icon(Icons.open_in_new, size: 16, color: kMainColor),
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => DrugInteractionCheckScreen(
                        preselectedProductIds: [widget.productId],
                      ),
                    ),
                  );
                },
                tooltip: 'Check more interactions',
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
              ),
          ],
        ),
        const SizedBox(height: 4),
        Text(
          '${widget.productName}${widget.genericName.isNotEmpty ? ' (${widget.genericName})' : ''}',
          style: theme.textTheme.bodySmall?.copyWith(color: kGreyTextColor),
        ),
        const SizedBox(height: 8),

        if (_isLoading)
          const SizedBox(height: 40, child: Center(child: CircularProgressIndicator(strokeWidth: 2, color: kMainColor)))
        else if (!_loaded)
          const SizedBox.shrink()
        else if (total == 0)
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.green.withValues(alpha: 0.05),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: Colors.green.withValues(alpha: 0.2)),
            ),
            child: Row(
              children: [
                const Icon(Icons.check_circle_outline, color: Colors.green, size: 20),
                const SizedBox(width: 8),
                Text(
                  'No known interactions for this product.',
                  style: theme.textTheme.bodySmall?.copyWith(color: Colors.green.shade700),
                ),
              ],
            ),
          )
        else
          ...interactions.map((interaction) {
            final color = _severityColor(interaction.severity ?? '');
            return Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.06),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: color.withValues(alpha: 0.2)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Drug pair
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          '${interaction.drugA?.productName ?? interaction.drugA?.matchedName ?? '?'} ↔ ${interaction.drugB?.productName ?? interaction.drugB?.matchedName ?? '?'}',
                          style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                        decoration: BoxDecoration(
                          color: color.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          interaction.severityLabel ?? interaction.severity ?? '',
                          style: TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    interaction.description ?? '',
                    style: theme.textTheme.bodySmall?.copyWith(color: kNutrals900, height: 1.4),
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                  ),
                  if (interaction.recommendation != null && interaction.recommendation!.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      '💡 ${interaction.recommendation}',
                      style: theme.textTheme.bodySmall?.copyWith(color: Colors.blue.shade700, fontSize: 11),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ],
              ),
            );
          }),
      ],
    );
  }
}
