import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/bi_mock_data.dart';

class BiPage extends ConsumerWidget {
  const BiPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Build dashboard',
        subtitle: 'Assemble widgets for a role',
        icon: Icons.dashboard_customize_outlined,
        onTap: () => notify('Dashboard builder opened'),
      ),
      ActionItem(
        title: 'Define KPI',
        subtitle: 'Set thresholds and owners',
        icon: Icons.flag_outlined,
        onTap: () => notify('KPI creation started'),
      ),
      ActionItem(
        title: 'Export report',
        subtitle: 'Send PDF/Excel to stakeholders',
        icon: Icons.picture_as_pdf_outlined,
        onTap: () => notify('Report export started'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Business intelligence',
            child: FeatureGroup(
              entries: biFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick BI actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'bi',
            endpoint: '/bi/kpis',
            statusOptions: ['Active', 'At risk', 'Archived'],
            title: 'KPIs',
          ),
        ],
      ),
    );
  }
}
