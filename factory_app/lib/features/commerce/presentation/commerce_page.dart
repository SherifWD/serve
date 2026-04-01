import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/commerce_mock_data.dart';

class CommercePage extends ConsumerWidget {
  const CommercePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Create order',
        subtitle: 'Capture e-commerce order',
        icon: Icons.shopping_cart_outlined,
        onTap: () => notify('Commerce order created'),
      ),
      ActionItem(
        title: 'Add customer',
        subtitle: 'Register a new shopper',
        icon: Icons.person_add_alt_outlined,
        onTap: () => notify('Customer added'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Commerce',
            child: FeatureGroup(
              entries: commerceFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick commerce actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'commerce',
            endpoint: '/commerce/orders',
            statusOptions: ['New', 'Packed', 'Shipped', 'Delivered'],
            title: 'Commerce orders',
          ),
        ],
      ),
    );
  }
}
