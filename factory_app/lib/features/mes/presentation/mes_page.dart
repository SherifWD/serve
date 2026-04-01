import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/mes_mock_data.dart';

class MesPage extends ConsumerWidget {
  const MesPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Schedule work order',
        subtitle: 'Assign WO to a production line',
        icon: Icons.calendar_today_outlined,
        onTap: () => notify('Work order scheduling started'),
      ),
      ActionItem(
        title: 'Log downtime event',
        subtitle: 'Capture an unplanned stop',
        icon: Icons.report_problem_outlined,
        onTap: () => notify('Downtime event logged'),
      ),
      ActionItem(
        title: 'Release work order',
        subtitle: 'Send WO to execution',
        icon: Icons.play_circle_outline,
        onTap: () => notify('Work order released'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Manufacturing execution',
            child: FeatureGroup(
              entries: mesFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick MES actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'mes',
            endpoint: '/mes/work-orders',
            statusOptions: ['Scheduled', 'Running', 'Completed'],
            title: 'Work orders',
          ),
        ],
      ),
    );
  }
}
