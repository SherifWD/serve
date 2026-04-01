import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/pos_mock_data.dart';

class PosPage extends ConsumerWidget {
  const PosPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Create order',
        subtitle: 'Start a new order for any table',
        icon: Icons.add_shopping_cart_outlined,
        onTap: () => notify('Order draft created'),
        badge: 'POS',
      ),
      ActionItem(
        title: 'Move table',
        subtitle: 'Reassign an open order to a new table',
        icon: Icons.table_bar_outlined,
        onTap: () => notify('Move table flow opened'),
      ),
      ActionItem(
        title: 'Send to KDS',
        subtitle: 'Push items to kitchen display',
        icon: Icons.kitchen,
        onTap: () => notify('Ticket sent to KDS'),
      ),
      ActionItem(
        title: 'Verify coupon',
        subtitle: 'Validate and apply discounts',
        icon: Icons.local_offer_outlined,
        onTap: () => notify('Coupon verified'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'POS endpoints',
            child: FeatureGroup(
              entries: posFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Quick actions',
            child: ActionGrid(items: actions),
          ),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'pos',
            endpoint: '/mobile/orders',
            statusOptions: ['Open', 'Preparing', 'Paid', 'Closed'],
            title: 'Live orders',
          ),
        ],
      ),
    );
  }
}
