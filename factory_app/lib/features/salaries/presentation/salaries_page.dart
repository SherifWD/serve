import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../data/salaries_mock_data.dart';

class SalariesPage extends ConsumerWidget {
  const SalariesPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Payroll',
            trailing: FilledButton.icon(
              onPressed: () {},
              icon: const Icon(Icons.lock_outline),
              label: const Text('Owner approval'),
            ),
            child: Column(
              children: [
                for (final item in salaryItems)
                  Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: ListTile(
                      leading: const Icon(Icons.receipt_long_outlined),
                      title: Text(item.employee),
                      subtitle: Text('${item.month} - ${item.adjustments}'),
                      trailing: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Text(item.net, style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
                          Chip(label: Text(item.status)),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Security controls',
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: const [
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: Icon(Icons.verified_user_outlined),
                  title: Text('Role-restricted access'),
                  subtitle: Text('Only owners and managers see payroll adjustments'),
                ),
                ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: Icon(Icons.history_toggle_off),
                  title: Text('Full audit log'),
                  subtitle: Text('Actions tracked for approvals and exports'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
