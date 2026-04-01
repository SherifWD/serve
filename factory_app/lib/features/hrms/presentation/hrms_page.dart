import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/hrms_mock_data.dart';

class HrmsPage extends ConsumerWidget {
  const HrmsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Run payroll',
        subtitle: 'Start payroll run for current period',
        icon: Icons.calculate_outlined,
        onTap: () => notify('Payroll run created'),
      ),
      ActionItem(
        title: 'Approve leave',
        subtitle: 'Review pending leave requests',
        icon: Icons.beach_access_outlined,
        onTap: () => notify('Leave approval opened'),
      ),
      ActionItem(
        title: 'Assign training',
        subtitle: 'Schedule training for workers',
        icon: Icons.school_outlined,
        onTap: () => notify('Training assignment started'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'HRMS domain',
            child: FeatureGroup(
              entries: hrmsFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick HRMS actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'hrms',
            endpoint: '/hrms/leave-requests',
            statusOptions: ['Pending', 'Approved', 'Rejected'],
            title: 'Leave requests',
          ),
        ],
      ),
    );
  }
}
