import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/qms_mock_data.dart';

class QmsPage extends ConsumerWidget {
  const QmsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Start inspection',
        subtitle: 'Run an inspection against a plan',
        icon: Icons.fact_check_outlined,
        onTap: () => notify('Inspection session created'),
      ),
      ActionItem(
        title: 'Log non-conformity',
        subtitle: 'Capture NCR with containment',
        icon: Icons.report_problem_outlined,
        onTap: () => notify('NCR logged'),
      ),
      ActionItem(
        title: 'Assign CAPA',
        subtitle: 'Create corrective action for QA',
        icon: Icons.task_alt_outlined,
        onTap: () => notify('CAPA assigned'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Quality management',
            child: FeatureGroup(
              entries: qmsFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick QMS actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'qms',
            endpoint: '/qms/non-conformities',
            statusOptions: ['Open', 'Containment', 'Closed'],
            title: 'Non-conformities',
          ),
        ],
      ),
    );
  }
}
