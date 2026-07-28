import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/Settings/Repo/feature_status_repo.dart';
import 'package:mobile_pos/model/feature_status_model.dart';

final featureStatusProvider = FutureProvider.autoDispose<List<FeatureStatusModel>>(
  (ref) => FeatureStatusRepo().fetchFeatures(),
);
