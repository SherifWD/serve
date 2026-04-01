import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/hse_mock_data.dart';

class HsePage extends ConsumerWidget {
  const HsePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Report incident',
        subtitle: 'Log incident with severity',
        icon: Icons.report_outlined,
        onTap: () => notify('Incident reported'),
      ),
      ActionItem(
        title: 'Schedule audit',
        subtitle: 'Plan upcoming HSE audit',
        icon: Icons.fact_check_outlined,
        onTap: () => notify('Audit scheduled'),
      ),
      ActionItem(
        title: 'Assign action',
        subtitle: 'Set owner and due date',
        icon: Icons.task_outlined,
        onTap: () => notify('Action assigned'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Health, safety & environment',
            child: FeatureGroup(
              entries: hseFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick HSE actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'hse',
            endpoint: '/hse/incidents',
            statusOptions: ['Open', 'In progress', 'Closed'],
            title: 'Incidents',
          ),
        ],
      ),
    );
  }
}
