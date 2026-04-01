import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

class StatusPie extends StatelessWidget {
  const StatusPie({super.key, required this.counts});

  final Map<String, int> counts;

  @override
  Widget build(BuildContext context) {
    if (counts.isEmpty) return const SizedBox.shrink();
    final colorScheme = Theme.of(context).colorScheme;
    final colors = [
      colorScheme.primary,
      colorScheme.secondary,
      colorScheme.tertiary,
      colorScheme.error,
      colorScheme.surfaceTint,
    ];
    var idx = 0;
    final sections = counts.entries.map((e) {
      final color = colors[idx % colors.length];
      idx++;
      return PieChartSectionData(
        value: e.value.toDouble(),
        title: '${e.key} (${e.value})',
        color: color,
        radius: 60,
        titleStyle: Theme.of(context).textTheme.labelSmall?.copyWith(color: Colors.white),
      );
    }).toList();

    return SizedBox(
      height: 220,
      child: PieChart(
        PieChartData(
          sections: sections,
          sectionsSpace: 3,
          centerSpaceRadius: 40,
        ),
      ),
    );
  }
}
