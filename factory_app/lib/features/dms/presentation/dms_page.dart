import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/dms_mock_data.dart';

class DmsPage extends ConsumerWidget {
  const DmsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Upload document',
        subtitle: 'Add new file to a folder',
        icon: Icons.cloud_upload_outlined,
        onTap: () => notify('Upload started'),
      ),
      ActionItem(
        title: 'Share link',
        subtitle: 'Generate secure share link',
        icon: Icons.link_outlined,
        onTap: () => notify('Share link copied'),
      ),
      ActionItem(
        title: 'New folder',
        subtitle: 'Create folder with permissions',
        icon: Icons.create_new_folder_outlined,
        onTap: () => notify('Folder created'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Document management',
            child: FeatureGroup(
              entries: dmsFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick DMS actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'dms',
            endpoint: '/dms/documents',
            statusOptions: ['Draft', 'Published', 'Archived'],
            title: 'Documents',
          ),
        ],
      ),
    );
  }
}
