import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

class TrendLineChart extends StatelessWidget {
  const TrendLineChart({super.key, required this.points, required this.color});

  final List<double> points;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final spots = [
      for (var i = 0; i < points.length; i++)
        FlSpot(i.toDouble(), points[i]),
    ];
    return SizedBox(
      height: 220,
      child: LineChart(
        LineChartData(
          gridData: FlGridData(show: false),
          titlesData: FlTitlesData(show: false),
          borderData: FlBorderData(show: false),
          lineBarsData: [
            LineChartBarData(
              spots: spots,
              isCurved: true,
              color: color,
              dotData: const FlDotData(show: false),
              belowBarData: BarAreaData(show: true, color: color.withOpacity(0.15)),
            ),
          ],
        ),
      ),
    );
  }
}

class DonutChart extends StatelessWidget {
  const DonutChart({super.key, required this.sections});

  final Map<String, double> sections;

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final colors = [
      colorScheme.primary,
      colorScheme.tertiary,
      colorScheme.secondary,
      colorScheme.error,
    ];

    var index = 0;
    final pieSections = sections.entries.map((entry) {
      final color = colors[index % colors.length];
      index++;
      return PieChartSectionData(
        value: entry.value,
        title: entry.key,
        color: color,
        radius: 60,
        titleStyle: Theme.of(context).textTheme.labelMedium?.copyWith(color: Colors.white),
      );
    }).toList();

    return SizedBox(
      height: 220,
      child: PieChart(
        PieChartData(
          sections: pieSections,
          sectionsSpace: 4,
          centerSpaceRadius: 40,
        ),
      ),
    );
  }
}

class SimpleBarChart extends StatelessWidget {
  const SimpleBarChart({super.key, required this.values});

  final List<double> values;

  @override
  Widget build(BuildContext context) {
    final bars = [
      for (var i = 0; i < values.length; i++)
        BarChartGroupData(
          x: i,
          barRods: [
            BarChartRodData(
              toY: values[i],
              color: Theme.of(context).colorScheme.primary,
              width: 18,
              borderRadius: BorderRadius.circular(6),
            ),
          ],
        ),
    ];

    return SizedBox(
      height: 220,
      child: BarChart(
        BarChartData(
          gridData: FlGridData(show: false),
          titlesData: FlTitlesData(show: false),
          borderData: FlBorderData(show: false),
          barGroups: bars,
        ),
      ),
    );
  }
}
