import 'package:flutter/material.dart';

class ActionItem {
  const ActionItem({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.onTap,
    this.badge,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final VoidCallback onTap;
  final String? badge;
}

class ActionGrid extends StatelessWidget {
  const ActionGrid({super.key, required this.items});

  final List<ActionItem> items;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return LayoutBuilder(
      builder: (context, constraints) {
        final maxWidth = constraints.maxWidth;
        final itemWidth = maxWidth > 1000
            ? (maxWidth - 32) / 3
            : maxWidth > 640
                ? (maxWidth - 16) / 2
                : maxWidth;
        return Wrap(
          spacing: 12,
          runSpacing: 12,
          children: items.map((item) {
            return SizedBox(
              width: itemWidth,
              child: Card(
                child: InkWell(
                  borderRadius: BorderRadius.circular(12),
                  onTap: item.onTap,
                  child: Padding(
                    padding: const EdgeInsets.all(14),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          decoration: BoxDecoration(
                            color: theme.colorScheme.primary.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          padding: const EdgeInsets.all(10),
                          child: Icon(item.icon, color: theme.colorScheme.primary),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      item.title,
                                      style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
                                    ),
                                  ),
                                  if (item.badge != null) Chip(label: Text(item.badge!)),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Text(item.subtitle, style: theme.textTheme.bodySmall),
                              const SizedBox(height: 8),
                              Text('Tap to proceed', style: theme.textTheme.labelMedium?.copyWith(color: theme.colorScheme.primary)),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            );
          }).toList(),
        );
      },
    );
  }
}
