import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/inventory_mock_data.dart';

class InventoryPage extends ConsumerWidget {
  const InventoryPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Adjust stock',
        subtitle: 'Create an inventory adjustment',
        icon: Icons.tune_outlined,
        onTap: () => notify('Adjustment draft created'),
      ),
      ActionItem(
        title: 'Receive shipment',
        subtitle: 'Log inbound stock from supplier',
        icon: Icons.inbox_outlined,
        onTap: () => notify('Inbound receiving started'),
      ),
      ActionItem(
        title: 'New ingredient',
        subtitle: 'Add ingredient to catalog',
        icon: Icons.spa_outlined,
        onTap: () => notify('Ingredient form opened'),
      ),
      ActionItem(
        title: 'Create recipe',
        subtitle: 'Link ingredients to menu item',
        icon: Icons.science_outlined,
        onTap: () => notify('Recipe builder launched'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Inventory & recipes',
            child: FeatureGroup(
              entries: inventoryFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Quick inventory actions',
            child: ActionGrid(items: actions),
          ),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'inventory',
            endpoint: '/inventory-items',
            statusOptions: ['In stock', 'Low', 'Out'],
            title: 'Inventory items',
          ),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'inventory',
            endpoint: '/inventory-transactions',
            statusOptions: ['Receipt', 'Issue', 'Adjustment'],
            title: 'Inventory transactions',
          ),
        ],
      ),
    );
  }
}
