import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile_pos/Screens/widget/acnoo_scafold.dart';
import 'package:mobile_pos/generated/l10n.dart' as l;
import 'package:mobile_pos/Provider/feature_status_provider.dart';
class FeatureStatusScreen extends ConsumerWidget {
  const FeatureStatusScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final featuresAsync = ref.watch(featureStatusProvider);

    return AcnooScafoldWidget(
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        title: Text(
          l.S.of(context).features,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(color: Colors.white),
        ),
        iconTheme: const IconThemeData(color: Colors.white),
        centerTitle: true,
      ),
      body: Padding(
        padding: const EdgeInsets.all(20.0),
        child: featuresAsync.when(
          data: (features) {
            if (features.isEmpty) {
              return Center(
                child: Text(
                  l.S.of(context).features,
                  style: Theme.of(context).textTheme.bodyLarge,
                ),
              );
            }
            return ListView.separated(
              itemCount: features.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, index) {
                final feature = features[index];
                return ListTile(
                  contentPadding: EdgeInsets.zero,
                  title: Text(
                    feature.label ?? '',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                  ),
                  subtitle: Text(feature.details ?? ''),
                  trailing: Chip(
                    label: Text(
                      feature.status == 'completed' ? 'Completed' : feature.status ?? '',
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Colors.white),
                    ),
                    backgroundColor: feature.status == 'completed' ? Colors.green : Colors.orange,
                  ),
                );
              },
            );
          },
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (error, stack) => Center(child: Text(error.toString())),
        ),
      ),
    );
  }
}
