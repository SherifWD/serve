import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/chart_cards.dart';
import '../../../core/widgets/section_card.dart';
import '../data/attendance_mock_data.dart';

class AttendancePage extends ConsumerWidget {
  const AttendancePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Today',
            trailing: FilledButton.tonalIcon(
              onPressed: () {},
              icon: const Icon(Icons.qr_code_scanner),
              label: const Text('Quick scan'),
            ),
            child: Column(
              children: [
                Wrap(
                  spacing: 8,
                  children: const [
                    Chip(label: Text('Check-in window: 07:00 - 07:30')),
                    Chip(label: Text('Overtime approvals open')),
                  ],
                ),
                const SizedBox(height: 10),
                for (final log in attendanceLogs)
                  ListTile(
                    leading: const Icon(Icons.schedule_outlined),
                    title: Text(log.name),
                    subtitle: Text('Status: ${log.status}'),
                    trailing: Text(log.time, style: theme.textTheme.titleMedium),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Monthly hours',
            child: SimpleBarChart(values: monthlyHours),
          ),
        ],
      ),
    );
  }
}
