import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../data/project_mock_data.dart';

class ProjectsPage extends ConsumerWidget {
  const ProjectsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Projects',
            trailing: FilledButton.icon(
              onPressed: () {},
              icon: const Icon(Icons.add),
              label: const Text('New project'),
            ),
            child: Column(
              children: [
                for (final project in projects)
                  Card(
                    margin: const EdgeInsets.only(bottom: 10),
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(project.name, style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
                              ),
                              Chip(label: Text(project.status)),
                            ],
                          ),
                          const SizedBox(height: 8),
                          LinearProgressIndicator(value: project.progress, minHeight: 8),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Icon(Icons.event, size: 16, color: theme.colorScheme.onSurfaceVariant),
                              const SizedBox(width: 4),
                              Text('Due ${project.due}', style: theme.textTheme.labelMedium),
                              const Spacer(),
                              Icon(Icons.work_outline, size: 16, color: theme.colorScheme.onSurfaceVariant),
                              const SizedBox(width: 4),
                              Text(project.owner, style: theme.textTheme.labelMedium),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Wrap(
                            spacing: 8,
                            children: project.team
                                .map((member) => Chip(
                                      label: Text(member),
                                      avatar: const Icon(Icons.person, size: 16),
                                    ))
                                .toList(),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Gantt-style timeline',
            child: _Timeline(),
          ),
        ],
      ),
    );
  }
}

class _Timeline extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final items = [
      ('Week 1', 0.2),
      ('Week 2', 0.45),
      ('Week 3', 0.7),
      ('Week 4', 1.0),
    ];

    return Column(
      children: [
        for (final (label, progress) in items)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: Row(
              children: [
                SizedBox(width: 70, child: Text(label, style: theme.textTheme.labelMedium)),
                Expanded(
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: LinearProgressIndicator(
                      value: progress,
                      minHeight: 10,
                      backgroundColor: theme.colorScheme.surfaceVariant,
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Text('${(progress * 100).round()}%'),
              ],
            ),
          ),
      ],
    );
  }
}
