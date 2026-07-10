import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Products/manufacturer/repo/manufacturer_repo.dart';
import 'package:mobile_pos/Screens/Products/medicine%20type/repo/medicine_type_repo.dart';
import '../model/manufacturer_model.dart';

final manufacturerProvider = FutureProvider.autoDispose<List<ManufacturerModel>>((ref) => ManufacturerRepo().fetchAllManufacturers());
