import 'package:flutter/material.dart';

class RecordsList extends StatelessWidget {
  const RecordsList({super.key, required this.records});

  final List<Map<String, dynamic>> records;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    if (records.isEmpty) {
      return Padding(
        padding: const EdgeInsets.symmetric(vertical: 12),
        child: Text('No records yet', style: theme.textTheme.bodySmall),
      );
    }
    return Column(
      children: records.map((record) {
        final title = record['title'] ?? record['name'] ?? record['id'] ?? 'Item';
        final subtitle = record['subtitle'] ?? record['description'] ?? record['date'] ?? '';
        final status = record['status']?.toString();
        final date = record['date']?.toString();
        return Card(
          margin: const EdgeInsets.only(bottom: 8),
          child: ListTile(
            leading: status != null ? Chip(label: Text(status)) : null,
            title: Text(title.toString()),
            subtitle: Text(subtitle.toString()),
            trailing: date != null ? Text(date, style: theme.textTheme.labelSmall) : null,
          ),
        );
      }).toList(),
    );
  }
}
