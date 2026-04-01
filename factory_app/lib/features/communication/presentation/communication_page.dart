import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/communication_mock_data.dart';

class CommunicationPage extends ConsumerWidget {
  const CommunicationPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Publish announcement',
        subtitle: 'Send update to all staff',
        icon: Icons.campaign_outlined,
        onTap: () => notify('Announcement published'),
      ),
      ActionItem(
        title: 'Trigger workflow',
        subtitle: 'Start approval workflow',
        icon: Icons.playlist_add_check_outlined,
        onTap: () => notify('Workflow started'),
      ),
      ActionItem(
        title: 'View reports',
        subtitle: 'Open communication reports',
        icon: Icons.insert_chart_outlined,
        onTap: () => notify('Reports opened'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Communication & workflows',
            child: FeatureGroup(
              entries: communicationFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick communication actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'communication',
            endpoint: '/communication/announcements',
            statusOptions: ['Draft', 'Live', 'Expired'],
            title: 'Announcements',
          ),
        ],
      ),
    );
  }
}
