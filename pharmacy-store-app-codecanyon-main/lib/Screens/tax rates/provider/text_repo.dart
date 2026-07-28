//_____________________________________________Tax_provider_____________________
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../repo/tax_repo.dart';
import '../model/tax_model.dart';

TaxRepo taxRepo = TaxRepo();
final taxProvider = FutureProvider<List<TaxModel>>((ref) => taxRepo.fetchAllTaxes(taxType: ''));

//_____________________________________________Group_Tax_provider_____________________
final singleTaxProvider = FutureProvider.autoDispose<List<TaxModel>>((ref) => taxRepo.fetchAllTaxes(taxType: 'single'));
