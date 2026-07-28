import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/DashBoard/repo/dashboard_repo.dart';

import '../model/dashboard_overview_model.dart';

DashboardRepo repo = DashboardRepo();
final dashboardInfoProvider = FutureProvider.family.autoDispose<DashboardOverviewModel, String>((ref, type) => repo.dashboardData(type));
