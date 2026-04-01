import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/erp_mock_data.dart';

class ErpPage extends ConsumerWidget {
  const ErpPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Add item',
        subtitle: 'Create a new ERP item master',
        icon: Icons.add_box_outlined,
        onTap: () => notify('Item form opened'),
      ),
      ActionItem(
        title: 'Create BOM',
        subtitle: 'Define components for a product',
        icon: Icons.list_alt_outlined,
        onTap: () => notify('BOM builder launched'),
      ),
      ActionItem(
        title: 'New site',
        subtitle: 'Register a new manufacturing site',
        icon: Icons.location_city_outlined,
        onTap: () => notify('Site creation started'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'ERP master data',
            child: FeatureGroup(
              entries: erpFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick ERP actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'erp',
            endpoint: '/erp/items',
            statusOptions: ['Active', 'Draft', 'Archived'],
            title: 'ERP items',
          ),
        ],
      ),
    );
  }
}
