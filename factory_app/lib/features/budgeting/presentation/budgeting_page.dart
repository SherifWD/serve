import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/budgeting_mock_data.dart';

class BudgetingPage extends ConsumerWidget {
  const BudgetingPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Create budget',
        subtitle: 'Draft budget for cost center',
        icon: Icons.calculate_outlined,
        onTap: () => notify('Budget draft created'),
      ),
      ActionItem(
        title: 'Load actuals',
        subtitle: 'Import actual spend data',
        icon: Icons.analytics_outlined,
        onTap: () => notify('Actuals import simulated'),
      ),
      ActionItem(
        title: 'Export report',
        subtitle: 'Share summary as PDF/Excel',
        icon: Icons.picture_as_pdf_outlined,
        onTap: () => notify('Budget report exported'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Budgeting',
            child: FeatureGroup(
              entries: budgetingFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick budgeting actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'budgeting',
            endpoint: '/budgeting/budgets',
            statusOptions: ['Draft', 'Approved', 'Submitted'],
            title: 'Budgets',
          ),
        ],
      ),
    );
  }
}
