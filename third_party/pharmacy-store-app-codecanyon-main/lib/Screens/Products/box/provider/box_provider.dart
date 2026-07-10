import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Products/box/repo/box_repo.dart';
import '../model/box_model.dart';

BoxRepo boxRepo = BoxRepo();
final leafAndBoxProvider = FutureProvider.autoDispose<List<BoxSizeModel>>((ref) => boxRepo.fetchAllBox());
