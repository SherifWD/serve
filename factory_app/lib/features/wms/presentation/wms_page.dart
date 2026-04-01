import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/wms_mock_data.dart';

class WmsPage extends ConsumerWidget {
  const WmsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Receive pallet',
        subtitle: 'Place goods into a bin',
        icon: Icons.inventory_outlined,
        onTap: () => notify('Receiving workflow opened'),
      ),
      ActionItem(
        title: 'Create transfer',
        subtitle: 'Move inventory between bins',
        icon: Icons.swap_horiz_outlined,
        onTap: () => notify('Transfer order drafted'),
      ),
      ActionItem(
        title: 'Cycle count',
        subtitle: 'Start a quick cycle count',
        icon: Icons.checklist_rtl_outlined,
        onTap: () => notify('Cycle count initiated'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Warehouse management',
            child: FeatureGroup(
              entries: wmsFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick WMS actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'wms',
            endpoint: '/wms/transfer-orders',
            statusOptions: ['In transit', 'Completed', 'Hold'],
            title: 'Transfer orders',
          ),
        ],
      ),
    );
  }
}
