import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/scm_mock_data.dart';

class ScmPage extends ConsumerWidget {
  const ScmPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Create PO',
        subtitle: 'Issue purchase order to supplier',
        icon: Icons.receipt_long_outlined,
        onTap: () => notify('Purchase order drafted'),
      ),
      ActionItem(
        title: 'Receive ASN',
        subtitle: 'Log inbound shipment',
        icon: Icons.local_shipping_outlined,
        onTap: () => notify('Inbound receiving flow opened'),
      ),
      ActionItem(
        title: 'Update forecast',
        subtitle: 'Adjust demand plan for next month',
        icon: Icons.query_stats_outlined,
        onTap: () => notify('Forecast update started'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Supply chain',
            child: FeatureGroup(
              entries: scmFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick SCM actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'scm',
            endpoint: '/scm/purchase-orders',
            statusOptions: ['Open', 'Pending', 'Closed'],
            title: 'Purchase orders',
          ),
        ],
      ),
    );
  }
}
