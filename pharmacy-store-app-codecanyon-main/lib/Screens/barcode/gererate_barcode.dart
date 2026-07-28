import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_typeahead/flutter_typeahead.dart';
import 'package:infinite_scroll_pagination/infinite_scroll_pagination.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/Screens/widget/primary_button.dart';
import 'package:mobile_pos/constant.dart';
import 'package:path_provider/path_provider.dart';
import 'package:pdf/pdf.dart';
import 'package:printing/printing.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:mobile_pos/generated/l10n.dart' as l;
import '../PDF/pdf.dart';
import '../Products/Model/datum_product_model.dart';
import '../Products/Repo/product_repo.dart';

class BarcodeGeneratorScreen extends StatefulWidget {
  const BarcodeGeneratorScreen({super.key});

  @override
  _BarcodeGeneratorScreenState createState() => _BarcodeGeneratorScreenState();
}

class _BarcodeGeneratorScreenState extends State<BarcodeGeneratorScreen> {
  List<Datum> products = [];
  List<SelectedProduct> selectedProducts = [];
  bool showCode = true;
  bool showPrice = true;
  bool showName = true;

  final ScrollController _scrollController = ScrollController();
  final PagingController<int, Datum> pageController = PagingController(firstPageKey: 1);
  final TextEditingController _searchController = TextEditingController();

  ProductRepo service = ProductRepo();

  @override
  void initState() {
    super.initState();
    pageController.addPageRequestListener((pageKey) => fetchProductListData(pageKey));
    fetchProductListData(1); // Preload the products as soon as the screen is loaded
  }

  Future<void> fetchProductListData(int pageKey) async {
    ProductListModel? list;
    try {
      list = await service.getProductList(
        search: _searchController.text,
        nextPage: pageKey.toString(),
      );
      print('----------------------${list?.data?.data?.length ?? 0}');
      if (list != null) {
        final newItems = list.data?.data ?? [];
        final isLastPage = list.data?.lastPage == list.data?.currentPage;
        if (isLastPage) {
          pageController.appendLastPage(newItems);
        } else {
          final nextPageKey = pageKey + 1;
          pageController.appendPage(newItems, nextPageKey);
        }
        setState(() {
          products.addAll(newItems); // Update products list
        });
      }
    } catch (error) {
      pageController.error = error;
    }
  }

  void _addProduct(Datum product) {
    setState(() {
      final existingProduct = selectedProducts.firstWhere(
        (p) => p.product.productCode == product.productCode,
        orElse: () => SelectedProduct(product: product, quantity: 0),
      );

      if (existingProduct.quantity > 0) {
        existingProduct.quantity++;
        _showSnackBar('${product.productName} quantity increased.');
      } else {
        selectedProducts.add(SelectedProduct(product: product, quantity: 1));
      }
    });
  }

  void _showSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _preview() async {
    final pdf = pw.Document();
    List<pw.Widget> barcodeWidgets = [];

    for (var selectedProduct in selectedProducts) {
      for (int i = 0; i < selectedProduct.quantity; i++) {
        barcodeWidgets.add(pw.Column(
          crossAxisAlignment: pw.CrossAxisAlignment.center,
          mainAxisAlignment: pw.MainAxisAlignment.center,
          children: [
            if (showName) pw.Text('${selectedProduct.product.productName}', style: const pw.TextStyle(fontSize: 10)),
            pw.SizedBox(height: 2),
            pw.BarcodeWidget(
                drawText: showCode ? true : false,
                data: selectedProduct.product.productCode!,
                barcode: pw.Barcode.code128(),
                width: 80,
                height: 30,
                textPadding: 4,
                textStyle: const pw.TextStyle(fontSize: 8)),
            if (showPrice) pw.Text('Price: ${selectedProduct.product.salesPrice}', style: const pw.TextStyle(fontSize: 10)),
          ],
        ));
      }
    }

    pdf.addPage(
      pw.MultiPage(
        build: (pw.Context context) {
          return [
            pw.GridView(
              childAspectRatio: 0.68,
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              crossAxisCount: 4,
              children: barcodeWidgets.map((widget) => pw.Container(child: widget)).toList(),
            ),
          ];
        },
      ),
    );
    try {
      final byteData = await pdf.save();
      final dir = await getApplicationDocumentsDirectory();
      final file = File('${dir.path}/barcode.pdf');
      await file.writeAsBytes(byteData.buffer.asUint8List(byteData.offsetInBytes, byteData.lengthInBytes));
      EasyLoading.showSuccess('Generate Complete');
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => PDFViewerPage(path: file.path),
        ),
      );
      await Printing.layoutPdf(onLayout: (PdfPageFormat format) async => await pdf.save());
    } catch (e) {
      print("Error generating PDF: $e");
    }
  }

  void _toggleCheckbox(bool value, Function(bool) updateFunction) {
    setState(() {
      updateFunction(value);
    });
  }

  final Map<int, TextEditingController> _controller = {};

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final lang = l.S.of(context);
    return AcnooScafoldWidget(
      appBar: AppBar(
        centerTitle: true,
        title: Text(
          lang.barcodeGenerator,
          style: theme.textTheme.titleLarge?.copyWith(
            color: Colors.white,
          ),
        ),
        iconTheme: IconThemeData(color: Colors.white),
        backgroundColor: Colors.transparent,
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: SingleChildScrollView(
          child: Column(
            children: [
              //--------------- Search Bar ---------------
              TypeAheadField<Datum>(
                builder: (context, controller, focusNode) {
                  return TextField(
                    controller: controller,
                    focusNode: focusNode,
                    decoration: InputDecoration(
                      fillColor: Colors.white,
                      border: const OutlineInputBorder(
                        borderSide: BorderSide(
                          color: kMainColor,
                        ),
                      ),
                      hintText: lang.searchProduct,
                      suffixIcon: Container(
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(8),
                          color: kMainColor,
                        ),
                        child: const Icon(
                          Icons.search,
                          color: Colors.white,
                        ),
                      ),
                      contentPadding: const EdgeInsets.symmetric(horizontal: 8.0, vertical: 4),
                    ),
                  );
                },
                suggestionsCallback: (pattern) {
                  // Ensure that products are fetched before searching
                  return products
                      .where(
                        (product) => product.productName!.toLowerCase().contains(pattern.toLowerCase()) || product.productCode!.toLowerCase().contains(pattern.toLowerCase()),
                      )
                      .toList();
                },
                itemBuilder: (context, Datum suggestion) {
                  return ListTile(
                    title: Text(suggestion.productName!),
                    subtitle: Text('${lang.code}: ${suggestion.productCode?.toString() ?? '0'}'),
                    trailing: Text('${lang.price}: ${suggestion.salesPrice?.toString() ?? '0'}'),
                  );
                },
                onSelected: (Datum value) {
                  _addProduct(value);
                },
              ),
              const SizedBox(height: 14),

              //--------------- Checkboxes ---------------
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Checkbox(
                    visualDensity: VisualDensity(horizontal: -4, vertical: -4),
                    activeColor: kMainColor,
                    side: BorderSide(color: kMainColor),
                    value: showCode,
                    onChanged: (bool? value) => _toggleCheckbox(value!, (val) => showCode = val),
                  ),
                  Flexible(child: Text(lang.showCode)),
                  const SizedBox(width: 8),
                  Checkbox(
                    visualDensity: VisualDensity(horizontal: -4, vertical: -4),
                    activeColor: kMainColor,
                    side: BorderSide(color: kMainColor),
                    value: showPrice,
                    onChanged: (bool? value) => _toggleCheckbox(value!, (val) => showPrice = val),
                  ),
                  Flexible(child: Text(lang.showPrice)),
                  const SizedBox(width: 8),
                  Checkbox(
                    visualDensity: VisualDensity(horizontal: -4, vertical: -4),
                    activeColor: kMainColor,
                    side: BorderSide(color: kMainColor),
                    value: showName,
                    onChanged: (bool? value) => _toggleCheckbox(value!, (val) => showName = val),
                  ),
                  Flexible(child: Text(lang.showName)),
                ],
              ),
              const SizedBox(height: 20),

              //--------------- Data Table ---------------
              selectedProducts.isNotEmpty
                  ? SingleChildScrollView(
                      scrollDirection: Axis.horizontal,
                      child: DataTable(
                        dividerThickness: 0,
                        headingRowColor: WidgetStateProperty.all(
                          theme.colorScheme.primary.withValues(alpha: 0.2),
                        ),
                        showBottomBorder: true,
                        horizontalMargin: 8,
                        columns: [
                          DataColumn(label: Text(lang.name)),
                          DataColumn(label: Text(lang.stock)),
                          DataColumn(label: Text(lang.quantity)),
                          DataColumn(label: Text(lang.actions)),
                        ],
                        border: TableBorder(bottom: BorderSide(color: kBorderColor)),
                        rows: selectedProducts.map((selectedProduct) {
                          final controller = _controller[selectedProduct.quantity];
                          // final controller = TextEditingController(text: selectedProduct.quantity.toString());
                          return DataRow(
                            cells: [
                              DataCell(
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      selectedProduct.product.productName ?? '',
                                      style: gTextStyle.copyWith(color: kTitleColor, fontSize: 14),
                                    ),
                                    Text(
                                      selectedProduct.product.productCode ?? '',
                                      style: gTextStyle.copyWith(color: kGreyTextColor, fontSize: 12),
                                    ),
                                  ],
                                ),
                              ),
                              DataCell(Text(selectedProduct.product.stocksSumProductStock?.toString() ?? '0')),
                              DataCell(
                                SizedBox(
                                  height: 38,
                                  child: TextFormField(
                                    controller: controller,
                                    keyboardType: TextInputType.number,
                                    textAlign: TextAlign.center,
                                    onChanged: (value) {
                                      setState(() {
                                        selectedProduct.quantity = int.tryParse(value) ?? 0;
                                      });
                                    },
                                  ),
                                ),
                              ),
                              DataCell(IconButton(
                                icon: const Icon(Icons.delete),
                                onPressed: () {
                                  setState(() {
                                    selectedProducts.remove(selectedProduct);
                                  });
                                },
                              )),
                            ],
                          );
                        }).toList(),
                      ),
                    )
                  : Center(
                      child: Column(
                        children: [
                          const Icon(
                            Icons.document_scanner,
                            size: 70,
                            color: kMainColor,
                          ),
                          Text(lang.noItemSelected, style: TextStyle(fontSize: 18)),
                        ],
                      ),
                    ),
            ],
          ),
        ),
      ),
      bottomNavigationBar: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
        child: NewPrimaryButton(
          buttonText: lang.previewPdf,
          onPressed: selectedProducts.isNotEmpty
              ? _preview
              : () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text(lang.noProductSelected)),
                  );
                },
        ),
      ),
    );
  }
}

class SelectedProduct {
  final Datum product;
  int quantity;

  SelectedProduct({required this.product, required this.quantity});
}
