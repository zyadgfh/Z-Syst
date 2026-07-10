import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../Model/due_collection_invoice_model.dart';
import '../Model/due_collection_model.dart';
import '../Model/due_invoice_list_model.dart';
import '../Repo/due_repo.dart';

DueRepo repo = DueRepo();
final dueInvoiceListProvider = FutureProvider.autoDispose.family<DueCollectionInvoice, int>((ref, id) => repo.fetchDueInvoiceList(id: id));
final dueCollectionListProvider = FutureProvider<List<DueCollection>>((ref) => repo.fetchDueCollectionList());

final allDueInvListProvider = FutureProvider.autoDispose.family<DueInvoiceListModel, num>((ref, id) => repo.fetchAllDueInvoiceList(id: id));
