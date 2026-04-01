import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/procurement_mock_data.dart';

class ProcurementPage extends ConsumerWidget {
  const ProcurementPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Create PR',
        subtitle: 'Start a purchase request',
        icon: Icons.assignment_outlined,
        onTap: () => notify('Purchase request created'),
      ),
      ActionItem(
        title: 'Publish tender',
        subtitle: 'Invite vendors to bid',
        icon: Icons.campaign_outlined,
        onTap: () => notify('Tender published'),
      ),
      ActionItem(
        title: 'Compare bids',
        subtitle: 'Evaluate tender responses',
        icon: Icons.rule_outlined,
        onTap: () => notify('Bid comparison opened'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Procurement',
            child: FeatureGroup(
              entries: procurementFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick procurement actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'procurement',
            endpoint: '/procurement/purchase-requests',
            statusOptions: ['Pending', 'Approved', 'Rejected'],
            title: 'Purchase requests',
          ),
        ],
      ),
    );
  }
}
