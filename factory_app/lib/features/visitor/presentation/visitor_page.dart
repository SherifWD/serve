import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/visitor_mock_data.dart';

class VisitorPage extends ConsumerWidget {
  const VisitorPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Pre-register visitor',
        subtitle: 'Capture details before arrival',
        icon: Icons.badge_outlined,
        onTap: () => notify('Visitor pre-registered'),
      ),
      ActionItem(
        title: 'Log entry',
        subtitle: 'Record check-in at gate',
        icon: Icons.login,
        onTap: () => notify('Visitor entry logged'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Visitor management',
            child: FeatureGroup(
              entries: visitorFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick visitor actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'visitor',
            endpoint: '/visitor/entries',
            statusOptions: ['Scheduled', 'Checked in', 'Checked out'],
            title: 'Visitor entries',
          ),
        ],
      ),
    );
  }
}
