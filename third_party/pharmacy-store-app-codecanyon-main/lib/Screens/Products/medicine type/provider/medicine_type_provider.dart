import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Products/medicine%20type/repo/medicine_type_repo.dart';
import '../model/medicine_type_model.dart';

final medicineTypeProvider = FutureProvider.autoDispose<List<MedicineTypeModel>>((ref) => MedicineTypeRepo().fetchAllMedicineType());
