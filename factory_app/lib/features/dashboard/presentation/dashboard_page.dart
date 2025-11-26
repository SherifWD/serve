import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/chart_cards.dart';
import '../../../core/widgets/kpi_card.dart';
import '../../../core/widgets/section_card.dart';
import '../data/dashboard_mock_data.dart';

class DashboardPage extends ConsumerWidget {
  const DashboardPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);

    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          LayoutBuilder(
            builder: (context, constraints) {
              final isWide = constraints.maxWidth > 900;
              return GridView.builder(
                itemCount: kpiData.length,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: isWide ? 4 : 2,
                  crossAxisSpacing: 12,
                  mainAxisSpacing: 12,
                  childAspectRatio: 1.3,
                ),
                itemBuilder: (context, index) {
                  final kpi = kpiData[index];
                  final icons = [
                    Icons.factory_outlined,
                    Icons.groups_2_outlined,
                    Icons.checklist_outlined,
                    Icons.verified_outlined,
                  ];
                  return KpiCard(
                    title: kpi.title,
                    value: kpi.value,
                    trendLabel: kpi.trend,
                    icon: icons[index % icons.length],
                  );
                },
              );
            },
          ),
          const SizedBox(height: 16),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              SizedBox(
                width: MediaQuery.of(context).size.width > 1200
                    ? (MediaQuery.of(context).size.width - 140) * 0.62
                    : MediaQuery.of(context).size.width - 40,
                child: SectionCard(
                  title: 'Production trend',
                  trailing: IconButton(
                    onPressed: () {},
                    icon: const Icon(Icons.refresh),
                  ),
                  child: TrendLineChart(
                    points: productionTrend,
                    color: theme.colorScheme.primary,
                  ),
                ),
              ),
              SizedBox(
                width: MediaQuery.of(context).size.width > 1200
                    ? (MediaQuery.of(context).size.width - 140) * 0.34
                    : MediaQuery.of(context).size.width - 40,
                child: SectionCard(
                  title: 'Task distribution',
                  child: DonutChart(sections: taskDistribution),
                  trailing: Chip(label: Text('Live')), // simple status
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          LayoutBuilder(
            builder: (context, constraints) {
              final isWide = constraints.maxWidth > 1000;
              return Flex(
                direction: isWide ? Axis.horizontal : Axis.vertical,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    flex: 3,
                    child: SectionCard(
                      title: 'Alerts & actions',
                      trailing: FilledButton.tonal(
                        onPressed: () {},
                        child: const Text('Acknowledge all'),
                      ),
                      child: Column(
                        children: [
                          for (final alert in alerts)
                            ListTile(
                              contentPadding: EdgeInsets.zero,
                              leading: Icon(
                                Icons.warning_amber_rounded,
                                color: _alertColor(alert.level, theme),
                              ),
                              title: Text(alert.message),
                              subtitle: Text(alert.level),
                              trailing: TextButton(onPressed: () {}, child: const Text('Open')),
                            ),
                        ],
                      ),
                    ),
                  ),
                  if (isWide) const SizedBox(width: 12) else const SizedBox(height: 12),
                  Expanded(
                    flex: 2,
                    child: SectionCard(
                      title: 'Activity',
                      child: Column(
                        children: [
                          for (final item in activities)
                            ListTile(
                              contentPadding: EdgeInsets.zero,
                              leading: const Icon(Icons.bolt_outlined),
                              title: Text(item.label),
                              subtitle: Text(item.timestamp),
                            ),
                        ],
                      ),
                    ),
                  ),
                ],
              );
            },
          ),
        ],
      ),
    );
  }

  Color _alertColor(String level, ThemeData theme) {
    switch (level) {
      case 'High':
        return theme.colorScheme.error;
      case 'Medium':
        return Colors.orange;
      default:
        return theme.colorScheme.primary;
    }
  }
}
