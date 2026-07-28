// ignore_for_file: file_names

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Products/category/model/category_model.dart';

import '../unit/model/unit_model.dart';
import '../category/repo/category_repo.dart';
import '../unit/repo/unit_repo.dart';

CategoryRepo categoryRepo = CategoryRepo();
final categoryProvider = FutureProvider.autoDispose<List<ProductCategoryModel>>((ref) => categoryRepo.fetchAllCategory());

UnitsRepo unitsRepo = UnitsRepo();
final unitsProvider = FutureProvider.autoDispose<List<UnitModel>>((ref) => unitsRepo.fetchAllUnits());
