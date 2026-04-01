import 'package:flutter/material.dart';

class FeatureEntry {
  const FeatureEntry({required this.title, required this.subtitle, required this.status, this.icon});
  final String title;
  final String subtitle;
  final String status;
  final IconData? icon;
}

class FeatureGroup extends StatelessWidget {
  const FeatureGroup({super.key, required this.entries, this.onEntryTap});

  final List<FeatureEntry> entries;
  final void Function(FeatureEntry entry)? onEntryTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      children: [
        for (final entry in entries)
          Card(
            margin: const EdgeInsets.only(bottom: 8),
            child: ListTile(
              leading: entry.icon != null ? Icon(entry.icon, color: theme.colorScheme.primary) : null,
              title: Text(entry.title),
              subtitle: Text(entry.subtitle),
              trailing: Chip(label: Text(entry.status)),
              onTap: () {
                if (onEntryTap != null) {
                  onEntryTap!(entry);
                } else {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('${entry.title} tapped')),
                  );
                }
              },
            ),
          ),
      ],
    );
  }
}
