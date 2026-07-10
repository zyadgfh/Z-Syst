import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:iconly/iconly.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:intl/intl.dart';
import 'package:mobile_pos/app_config/api_config.dart';
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
    required this.pagingController,
  });
  final num id;
  final PagingController pagingController;
  @override
  State<ProductDetails> createState() => _ProductDetailsState();
}

class _ProductDetailsState extends State<ProductDetails> {
  @override
  void dispose() {
    controller.dispose();
    super.dispose();
  }

  int pageIndex = 0;
  PageController? bannerController;

  final CarouselController controller = CarouselController(initialItem: 1);

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
                          widget.pagingController.refresh();
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
                                                      color: Theme.of(context).colorScheme.primaryContainer.withOpacity(0.1),
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
                                                      color: Theme.of(context).colorScheme.primaryContainer.withOpacity(0.4),
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
                                color: Color(0xff9090AD).withOpacity(0.3),
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
