import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../model/purchase_details_model.dart';
import '../repo/purchase_list_repo.dart';

final purchaseDetailsProvider = FutureProvider.family<PurchaseDetailsModel?, num>((ref, id) async {
  return await PurchaseListRepo().getPurchaseDetails(id: id);
});
