import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/plm_mock_data.dart';

class PlmPage extends ConsumerWidget {
  const PlmPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'New design',
        subtitle: 'Create a product design revision',
        icon: Icons.architecture_outlined,
        onTap: () => notify('Design creation started'),
      ),
      ActionItem(
        title: 'Raise ECO',
        subtitle: 'Propose an engineering change',
        icon: Icons.change_circle_outlined,
        onTap: () => notify('ECO draft created'),
      ),
      ActionItem(
        title: 'Attach document',
        subtitle: 'Upload new design document',
        icon: Icons.upload_file_outlined,
        onTap: () => notify('Document upload started'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Product lifecycle',
            child: FeatureGroup(
              entries: plmFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick PLM actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'plm',
            endpoint: '/plm/engineering-changes',
            statusOptions: ['Draft', 'Pending', 'Approved', 'Released'],
            title: 'Engineering changes',
          ),
        ],
      ),
    );
  }
}
