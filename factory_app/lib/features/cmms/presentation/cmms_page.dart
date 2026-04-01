import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/cmms_mock_data.dart';

class CmmsPage extends ConsumerWidget {
  const CmmsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Create work order',
        subtitle: 'Open a maintenance WO',
        icon: Icons.home_repair_service_outlined,
        onTap: () => notify('Work order created'),
      ),
      ActionItem(
        title: 'Schedule PM',
        subtitle: 'Add preventive maintenance plan',
        icon: Icons.event_repeat_outlined,
        onTap: () => notify('PM scheduled'),
      ),
      ActionItem(
        title: 'Issue spare parts',
        subtitle: 'Reserve parts from stock',
        icon: Icons.memory_outlined,
        onTap: () => notify('Spare part issued'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Maintenance management',
            child: FeatureGroup(
              entries: cmmsFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick CMMS actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'cmms',
            endpoint: '/cmms/work-orders',
            statusOptions: ['Open', 'In progress', 'Closed'],
            title: 'Maintenance work orders',
          ),
        ],
      ),
    );
  }
}
