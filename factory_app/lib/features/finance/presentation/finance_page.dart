import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/finance_mock_data.dart';

class FinancePage extends ConsumerWidget {
  const FinancePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Post journal',
        subtitle: 'Create a manual journal entry',
        icon: Icons.receipt_long_outlined,
        onTap: () => notify('Journal entry posted'),
      ),
      ActionItem(
        title: 'New ledger account',
        subtitle: 'Add account to chart of accounts',
        icon: Icons.account_balance_wallet_outlined,
        onTap: () => notify('Ledger account created'),
      ),
      ActionItem(
        title: 'Pay vendor',
        subtitle: 'Record accounts payable payment',
        icon: Icons.outbox_outlined,
        onTap: () => notify('Vendor payment processed'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Finance & accounting',
            child: FeatureGroup(
              entries: financeFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick finance actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'finance',
            endpoint: '/finance/journal-entries',
            statusOptions: ['Posted', 'Draft', 'Pending'],
            title: 'Journal entries',
          ),
        ],
      ),
    );
  }
}
