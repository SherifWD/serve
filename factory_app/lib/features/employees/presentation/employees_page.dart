import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../data/employees_mock_data.dart';

class EmployeesPage extends ConsumerWidget {
  const EmployeesPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Team overview',
            trailing: FilledButton.icon(
              onPressed: () {},
              icon: const Icon(Icons.person_add_alt),
              label: const Text('Invite'),
            ),
            child: Column(
              children: [
                for (final emp in employees)
                  Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: ListTile(
                      leading: CircleAvatar(child: Text(emp.name.characters.first)),
                      title: Text(emp.name),
                      subtitle: Text('${emp.role} - ${emp.department}'),
                      trailing: Chip(label: Text('Perf ${emp.performance}')),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Roles & permissions',
            child: Column(
              children: permissionsByRole.entries
                  .map(
                    (entry) => ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(entry.key, style: theme.textTheme.titleMedium),
                      subtitle: Wrap(
                        spacing: 8,
                        runSpacing: 4,
                        children: entry.value.map((p) => Chip(label: Text(p))).toList(),
                      ),
                    ),
                  )
                  .toList(),
            ),
          ),
        ],
      ),
    );
  }
}
