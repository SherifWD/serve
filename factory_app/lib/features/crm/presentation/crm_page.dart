import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/crm_mock_data.dart';

class CrmPage extends ConsumerWidget {
  const CrmPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Log lead',
        subtitle: 'Create a new lead with contact',
        icon: Icons.lightbulb_outline,
        onTap: () => notify('Lead captured'),
      ),
      ActionItem(
        title: 'Advance opportunity',
        subtitle: 'Move deal to next stage',
        icon: Icons.trending_up_outlined,
        onTap: () => notify('Opportunity stage updated'),
      ),
      ActionItem(
        title: 'Open service case',
        subtitle: 'Create support ticket for customer',
        icon: Icons.support_agent_outlined,
        onTap: () => notify('Service case created'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Customer relationship',
            child: FeatureGroup(
              entries: crmFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick CRM actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'crm',
            endpoint: '/crm/leads',
            statusOptions: ['New', 'Qualified', 'Won', 'Lost'],
            title: 'Leads',
          ),
        ],
      ),
    );
  }
}
