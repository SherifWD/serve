import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/platform_mock_data.dart';

class PlatformPage extends ConsumerWidget {
  const PlatformPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Add tenant',
        subtitle: 'Provision new tenant',
        icon: Icons.apartment_outlined,
        onTap: () => notify('Tenant creation started'),
      ),
      ActionItem(
        title: 'Assign modules',
        subtitle: 'Enable/disable tenant modules',
        icon: Icons.extension_outlined,
        onTap: () => notify('Module assignment opened'),
      ),
      ActionItem(
        title: 'Invite user',
        subtitle: 'Create a tenant user account',
        icon: Icons.person_add_alt_outlined,
        onTap: () => notify('User invitation sent'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Platform & tenants',
            child: FeatureGroup(
              entries: platformFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick platform actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'platform',
            endpoint: '/platform/tenants',
            statusOptions: ['Active', 'Pending', 'Suspended'],
            title: 'Tenants',
          ),
        ],
      ),
    );
  }
}
