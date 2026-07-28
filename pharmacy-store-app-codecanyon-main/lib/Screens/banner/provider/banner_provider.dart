import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../model/banner_model.dart';
import '../repo/banner_repo.dart';


BannerRepository businessRepository = BannerRepository();
final bannerProvider = FutureProvider<BannerModel>((ref) => businessRepository.getBannerReportList());