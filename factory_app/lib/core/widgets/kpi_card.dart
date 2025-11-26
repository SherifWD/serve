import 'package:flutter/material.dart';

class KpiCard extends StatelessWidget {
  const KpiCard({
    required this.title,
    required this.value,
    required this.trendLabel,
    required this.icon,
    this.color,
    super.key,
  });

  final String title;
  final String value;
  final String trendLabel;
  final IconData icon;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final resolvedColor = color ?? theme.colorScheme.primary;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  decoration: BoxDecoration(
                    color: resolvedColor.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  padding: const EdgeInsets.all(10),
                  child: Icon(icon, color: resolvedColor),
                ),
                const Spacer(),
                Text(trendLabel, style: theme.textTheme.labelMedium?.copyWith(color: Colors.green)),
              ],
            ),
            const SizedBox(height: 14),
            Text(title, style: theme.textTheme.labelLarge),
            Text(
              value,
              style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold),
            ),
          ],
        ),
      ),
    );
  }
}
