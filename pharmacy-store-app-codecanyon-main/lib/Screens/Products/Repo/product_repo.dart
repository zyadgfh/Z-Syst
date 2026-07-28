import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_easyloading/flutter_easyloading.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:http/http.dart' as http;
import 'package:mobile_pos/Provider/product_provider.dart';
import 'package:mobile_pos/Screens/Products/add%20product/model/add_stock_model.dart';
import '../../../app_config/api_config.dart';
import '../../../Repository/constant_functions.dart';
import '../../Purchase/Model/StockBasedProductModel.dart';
import '../Model/datum_product_model.dart';
import '../add product/model/add_product_model.dart';
import '../Model/product_model.dart';

class ProductRepo {
  Future<List<ProductModel>> fetchAllProducts() async {
    final uri = Uri.parse('${APIConfig.url}/products');

    final response = await http.get(uri, headers: {
      'Accept': 'application/json',
      'Authorization': await getAuthToken(),
    });

    if (response.statusCode == 200) {
      final parsedData = jsonDecode(response.body) as Map<String, dynamic>;

      final partyList = parsedData['data'] as List<dynamic>;
      return partyList.map((category) => ProductModel.fromJson(category)).toList();
      // Parse into Party objects
    } else {
      throw Exception('Failed to fetch Products');
    }
  }

  // paginated product list
  Future<ProductListModel?> getProductList({
    String? nextPage,
    String? search,
    String? expireDate,
    bool? expired,
  }) async {
    try {
      String token = await getAuthToken() ?? '';

      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }

      // Build the URL with proper encoding
      final uri = Uri.parse('${APIConfig.url}/products').replace(
        queryParameters: {
          if (expired != null) 'expired': expired.toString(),
          if (expireDate != null) 'expire_date': expireDate.substring(0, 10),
          if (search != null && search.isNotEmpty) 'search': search,
          if (nextPage != null) 'page': nextPage,
        },
      );

      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };

      final response = await http.get(uri, headers: headers);

      if (response.statusCode == 200) {
        return ProductListModel.fromJson(jsonDecode(response.body));
      }

      return null;
    } on http.ClientException catch (e) {
      print('ClientException: ${e.message}');
      return null;
    } on SocketException catch (e) {
      print('SocketException: ${e.message}');
      return null;
    } on Exception catch (e) {
      print('General Exception: $e');
      return null;
    }
  }

  // Future<ProductListModel?> getProductList({
  //   String? nextPage,
  //   String? search,
  //   String? expireDate,
  //   bool? expired,
  // }) async {
  //   try {
  //     String token = await getAuthToken() ?? '';
  //
  //     if (token.isEmpty) {
  //       throw Exception('Auth token is empty');
  //     }
  //     final url = Uri.parse('${APIConfig.url}/products?search=${search ?? ''}${(expired ?? false) ? "&expired=$expired" : null}&expire_date=$expireDate&page=$nextPage');
  //     // final url = Uri.parse('${APIConfig.url}/products?search=${search ?? ''}');
  //
  //     final headers = {'Accept': 'application/json', 'Authorization': token};
  //     print(url);
  //     http.Response _response = await http.get(url, headers: headers);
  //
  //     if (_response.statusCode == 200) {
  //       final data = ProductListModel.fromJson(jsonDecode(_response.body));
  //
  //       return data;
  //     }
  //
  //     return null;
  //   } on http.ClientException catch (e) {
  //     print(e.message);
  //     return null;
  //   } on SocketException catch (e) {
  //     print(e.message);
  //     return null;
  //   }
  // }

  Future<StockBasedProductModel?> getBatchBasedProductList({
    String? nextPage,
    String? search,
    required bool stockFilter,
  }) async {
    try {
      String token = await getAuthToken();

      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse('${APIConfig.url}/stocks-with-product?search=${search ?? ''}${stockFilter ? '&check_stock=true' : ''}&page=$nextPage');

      final headers = {'Accept': 'application/json', 'Authorization': token};
      http.Response response = await http.get(url, headers: headers);

      if (response.statusCode == 200) {
        final data = StockBasedProductModel.fromJson(jsonDecode(response.body));

        return data;
      }

      return null;
    } on http.ClientException {
      return null;
    } on SocketException {
      return null;
    }
  }

  // Add Product
  Future<bool> addProduct({required AddProductModel data, required BuildContext context}) async {
    final url = Uri.parse('${APIConfig.url}/products');
    String token = await getAuthToken() ?? '';
    final headers = {'Accept': 'application/json', 'Authorization': token};

    try {
      var request = http.MultipartRequest('POST', url);

      request.fields.addAll({
        "productName": data.productName,
        "category_id": data.categoryId.toString(),
        "meta[strength]": data.strength.toString(),
        "meta[generic_name]": data.genericName.toString(),
        "productStock": data.productStock ?? '',
        "alert_qty": data.alertStock ?? '',
        "meta[shelf]": data.shelf.toString(),
        "purchase_without_tax": data.purchaseWithoutTax.toString(),
        "purchase_with_tax": data.purchaseWithTax.toString(),
        "profit_percent": data.profitPercent.toString(),
        "sales_price": data.salePrice.toString(),
        "wholesale_price": data.wholeSalePrice.toString(),
        "meta[medicine_details]": data.medicineDetails.toString(),
        "tax_type": data.taxType.toString(),
      });
      if (data.productCode != null) request.fields.addAll({"productCode": data.productCode!});
      if (data.typeId != null) request.fields.addAll({"type_id": data.typeId.toString()});
      if (data.manufacturerId != null) request.fields.addAll({"manufacturer_id": data.manufacturerId.toString()});
      if (data.boxSizeId != null) request.fields.addAll({"box_size_id": data.boxSizeId.toString()});
      if (data.unitId != null) request.fields.addAll({"unit_id": data.unitId.toString()});
      if (data.taxId != null) request.fields.addAll({"tax_id": data.taxId.toString()});
      if (data.expireDate != null) request.fields.addAll({"expire_date": data.expireDate ?? ''});
      if (data.batchNo != null) request.fields.addAll({"batch_no": data.batchNo!});
      if (data.images != null) {
        for (int index = 0; index < data.images!.length; index++) {
          File imageFile = data.images![index];
          request.files.add(await http.MultipartFile.fromPath('images[$index]', imageFile.path));
        }
      }

      request.headers.addAll(headers);

      var response = await request.send();
      var res = await http.Response.fromStream(response);
      print('Add product: ${res.statusCode}');
      print('Add product: ${res.body}');

      if (response.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Product Created successfully')));
        return true;
      } else {
        var data = jsonDecode(res.body);
        EasyLoading.showError(data['message']);
        return false;
      }
    } catch (e) {
      EasyLoading.showError(e.toString());
      throw Exception('Error');
    }
  }

  // Update Product
  Future<bool> updateProduct({required AddProductModel data, required BuildContext context, required String id}) async {
    final url = Uri.parse('${APIConfig.url}/products/$id');
    String token = await getAuthToken() ?? '';
    final headers = {'Accept': 'application/json', 'Authorization': token};
    try {
      var request = http.MultipartRequest('POST', url);

      request.fields.addAll({
        "productCode": data.productCode ?? '',
        "productName": data.productName,
        "category_id": data.categoryId.toString() ?? '',
        "meta[strength]": data.strength.toString(),
        "meta[generic_name]": data.genericName.toString(),
        "productStock": data.productStock ?? '0',
        "alert_qty": data.alertStock.toString(),
        "meta[shelf]": data.shelf.toString(),
        "purchase_without_tax": data.purchaseWithoutTax.toString(),
        "purchase_with_tax": data.purchaseWithTax.toString(),
        "profit_percent": data.profitPercent.toString(),
        "sales_price": data.salePrice.toString(),
        "wholesale_price": data.wholeSalePrice.toString(),
        "meta[medicine_details]": data.medicineDetails.toString(),
        "tax_type": data.taxType.toString(),
        "_method": 'put',
      });

      if (data.typeId != null) request.fields.addAll({"type_id": data.typeId.toString()});
      if (data.expireDate != null) request.fields.addAll({"expire_date": data.expireDate ?? ''});
      if (data.unitId != null && data.unitId != 0) request.fields.addAll({"unit_id": data.unitId.toString()});
      if (data.taxId != null && data.taxId != 0) request.fields.addAll({"tax_id": data.taxId.toString()});
      if (data.batchNo != null) request.fields.addAll({"batch_no": data.batchNo!});
      if (data.manufacturerId != null && data.manufacturerId != 0) {
        request.fields["manufacturer_id"] = data.manufacturerId.toString();
      }
      if (data.boxSizeId != null && data.boxSizeId != 0) {
        request.fields["box_size_id"] = data.boxSizeId.toString();
      }

      if (data.removedImageList != null && data.removedImageList!.isNotEmpty) {
        for (String imageUrl in data.removedImageList!) {
          request.fields['removed_images[]'] = imageUrl; // Use the proper assignment
        }
      }
      if (data.images != null) {
        for (int index = 0; index < data.images!.length; index++) {
          File imageFile = data.images![index];
          request.files.add(await http.MultipartFile.fromPath('images[$index]', imageFile.path));
        }
      }

      request.headers.addAll(headers);
      print(request.fields);

      var response = await request.send();

      var res = await http.Response.fromStream(response);
      print(res.body);
      print(res.request);
      if (response.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('successfully')));
        return true;
      } else {
        var data = jsonDecode(res.body);
        EasyLoading.showError(data['message']);
        return false;
      }
    } catch (e) {
      EasyLoading.showError(e.toString());
      throw Exception('Error');
    }
  }

  // Add Stock
  Future<bool> addStock({required AddStockModel data, required String id}) async {
    final url = Uri.parse('${APIConfig.url}/stock-update/$id');
    String token = await getAuthToken() ?? '';
    final headers = {
      'Accept': 'application/json',
      'Authorization': token,
    };
    final requestBody = {
      "batch_no": data.batchNo ?? '',
      "expire_date": data.expireDate ?? '',
      "tax_id": data.taxId?.toString() ?? '',
      "tax_type": data.taxType.toString() ?? '',
      "purchase_without_tax": data.purchaseWithoutTax.toString() ?? '0',
      "purchase_with_tax": data.purchaseWithTax.toString() ?? '0',
      "profit_percent": data.profitPercent.toString() ?? '0',
      "sales_price": data.salePrice.toString() ?? '0',
      "wholesale_price": data.wholeSalePrice,
      "qty": data.productStock.toString() ?? '0',
    };
    print(requestBody);
    try {
      final response = await http.post(url, headers: headers, body: requestBody);
      print('Status code: ${response.statusCode}');

      if (response.statusCode == 200) {
        return true;
      } else {
        final data = jsonDecode(response.body);
        EasyLoading.showError(data['message'] ?? 'Unknown error');
        return false;
      }
    } catch (e) {
      EasyLoading.showError('Error: ${e.toString()}');
      return false;
    }
  }

  Future<bool> addProductForBulk({
    required String productName,
    required String categoryId,
    required String productCode,
    required String productStock,
    required String productSalePrice,
    required String productPurchasePrice,
    num? manufacturerId,
    num? boxSizeId,
    num? typeId,
    num? unitId,
    String? productWholeSalePrice,
    String? productDealerPrice,
    String? expireDate,
    String? medicineDetails,
    String? batchNo,
    String? shelf,
    String? genericName,
    String? strength,
  }) async {
    final uri = Uri.parse('${APIConfig.url}/products');

    var request = http.MultipartRequest('POST', uri)
      ..headers['Accept'] = 'application/json'
      ..headers['Authorization'] = await getAuthToken();
    request.fields.addAll({
      "productName": productName,
      "category_id": categoryId,
      "productCode": productCode,
      "productStock": productStock,
      "productSalePrice": productSalePrice,
      "productPurchasePrice": productPurchasePrice,
    });
    if (typeId != null) request.fields['type_id'] = typeId.toString();
    if (strength != null && strength.isNotEmpty) request.fields['meta[strength]'] = strength;
    if (genericName != null && genericName.isNotEmpty) request.fields['meta[generic_name]'] = genericName;
    if (shelf != null && shelf.isNotEmpty) request.fields['meta[shelf]'] = shelf;
    if (batchNo != null && batchNo.isNotEmpty) request.fields['meta[batch_no]'] = batchNo;
    if (medicineDetails != null && medicineDetails.isNotEmpty) request.fields['meta[medicine_details]'] = medicineDetails;
    // if (expireDate != null && expireDate.isNotEmpty) request.fields['expire_date'] = expireDate;
    if (boxSizeId != null) request.fields['box_size_id'] = boxSizeId.toString();
    if (manufacturerId != null) request.fields['manufacturer_id'] = manufacturerId.toString();
    if (unitId != null) request.fields['unit_id'] = unitId.toString();
    if (productWholeSalePrice != null && productWholeSalePrice.isNotEmpty) request.fields['productWholeSalePrice'] = productWholeSalePrice;
    if (productDealerPrice != null && productDealerPrice.isNotEmpty) request.fields['productDealerPrice'] = productDealerPrice;

    final response = await request.send();
    final responseData = await response.stream.bytesToString();
    final parsedData = jsonDecode(responseData);
    print('Add Product For bulk = code=${response.statusCode} data =$parsedData');

    if (response.statusCode == 200) {
      return true;
    }
    return false;
  }

  // Future<bool> addForBulkUpload({
  //   required String productName,
  //   required String categoryId,
  //   required String productCode,
  //   required String productStock,
  //   required String productSalePrice,
  //   required String productPurchasePrice,
  //   String? manufacturerId,
  //   String? boxSizeId,
  //   String? typeId,
  //   String? unitId,
  //   String? productWholeSalePrice,
  //   String? productDealerPrice,
  //   String? expireDate,
  //   String? medicineDetails,
  //   String? batchNo,
  //   String? shelf,
  //   String? genericName,
  //   String? strength,
  // }) async {
  //   final uri = Uri.parse('${APIConfig.url}/products');
  //
  //   var request = http.MultipartRequest('POST', uri)
  //     ..headers['Accept'] = 'application/json'
  //     ..headers['Authorization'] = await getAuthToken();
  //   request.fields.addAll({
  //     "productName": productName,
  //     "category_id": categoryId,
  //     "productCode": productCode,
  //     "productStock": productStock,
  //     "productSalePrice": productSalePrice,
  //     "productPurchasePrice": productPurchasePrice,
  //   });
  //   if (typeId != null) request.fields['type_id'] = typeId;
  //   if (strength != null) request.fields['meta[strength]'] = strength;
  //   if (genericName != null) request.fields['meta[generic_name]'] = genericName;
  //   if (shelf != null) request.fields['meta[shelf]'] = shelf;
  //   if (batchNo != null) request.fields['meta[batch_no]'] = batchNo;
  //   if (medicineDetails != null) request.fields['meta[medicine_details]'] = medicineDetails;
  //   if (expireDate != null) request.fields['expire_date'] = expireDate;
  //   if (boxSizeId != null) request.fields['box_size_id'] = boxSizeId;
  //   if (manufacturerId != null) request.fields['manufacturer_id'] = manufacturerId.toString();
  //   if (unitId != null) request.fields['unit_id'] = unitId;
  //   if (productWholeSalePrice != null) request.fields['productWholeSalePrice'] = productWholeSalePrice;
  //   if (productDealerPrice != null) request.fields['productDealerPrice'] = productDealerPrice;
  //
  //   final response = await request.send();
  //   final responseData = await response.stream.bytesToString();
  //   final parsedData = jsonDecode(responseData);
  //   print(
  //     'product add code: ${response.statusCode}, Body $parsedData',
  //   );
  //
  //   if (response.statusCode == 200) {
  //     return true;
  //   }
  //   return false;
  // }

  // get product details
  Future<ProductModel> getProductDetails({required String id}) async {
    ProductModel orderDetailsModel = ProductModel();
    try {
      String token = await getAuthToken() ?? '';
      if (token.isEmpty) {
        throw Exception('Auth token is empty');
      }
      final url = Uri.parse('${APIConfig.url}/products/$id');

      final headers = {'Accept': 'application/json', 'Authorization': token};
      var response = await http.get(url, headers: headers);
      if (response.statusCode == 200) {
        return ProductModel.fromJson(jsonDecode(response.body)['data']);
      } else {
        print('Failed to fetch details: ${response.statusCode}');
      }
    } catch (e) {
      print('Error fetching  details: $e');
      throw Exception('Error fetching details: $e');
    }
    return orderDetailsModel;
  }

  // update product stock
  Future<bool> updateProductStock({required String id, required String qty, required BuildContext context}) async {
    try {
      String token = await getAuthToken() ?? '';
      final url = Uri.parse('${APIConfig.url}/stock-update/$id');
      final headers = {
        'Accept': 'application/json',
        'Authorization': token,
      };
      final body = {'productStock': qty};

      final response = await http.post(url, headers: headers, body: body);
      final responseData = jsonDecode(response.body);
      EasyLoading.dismiss();
      if (response.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(responseData['message'])));
        return true;
      } else {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(responseData['message'])));
      }
    } catch (error) {
      print(error);
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Network error: Please try again')));
    } finally {}

    return false;
  }

  Future<void> deleteProduct({
    required String id,
    required BuildContext context,
    required WidgetRef ref,
  }) async {
    final String apiUrl = '${APIConfig.url}/products/$id';

    try {
      final response = await http.delete(
        Uri.parse(apiUrl),
        headers: {
          'Accept': 'application/json',
          'Authorization': await getAuthToken(), // Implement your getAuthToken function
        },
      );

      EasyLoading.dismiss();

      if (response.statusCode == 200) {
        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Product deleted successfully')));

        var data1 = ref.refresh(productProvider);
        Navigator.pop(context);
      } else {
        final parsedData = jsonDecode(response.body);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Failed to delete product: ${parsedData['message']}')));
      }
    } catch (e) {
      EasyLoading.dismiss();
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
    }
  }
}
