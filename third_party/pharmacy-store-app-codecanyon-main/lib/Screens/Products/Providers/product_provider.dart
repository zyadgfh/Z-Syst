import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Products/Model/product_model.dart';

import '../Repo/product_repo.dart';

ProductRepo productRepo = ProductRepo();

// Product Details Provider
final productDetailsProvider = FutureProvider.autoDispose.family<ProductModel, String>((ref, id) {
  return productRepo.getProductDetails(id: id);
});
