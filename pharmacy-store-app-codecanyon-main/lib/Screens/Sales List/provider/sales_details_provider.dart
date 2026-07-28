import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../Sales/Model/sales_details_model.dart';
import '../repo/sales_list_repo.dart';

SalesListRepo apiService = SalesListRepo();

final salesDetailsProvider = FutureProvider.family<SalesDetailsModel?, num>((ref, id) async {
  final salesDetails = await apiService.getSalesDetails(id: id);
  return salesDetails;
});
