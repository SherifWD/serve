import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../data/notifications_mock_data.dart';

class NotificationsPage extends ConsumerWidget {
  const NotificationsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Notifications & activity log',
            trailing: FilledButton.tonalIcon(
              onPressed: () {},
              icon: const Icon(Icons.mark_email_read_outlined),
              label: const Text('Mark all read'),
            ),
            child: Column(
              children: [
                for (final item in notifications)
                  Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: ListTile(
                      leading: Icon(
                        _iconFor(item.severity),
                        color: _colorFor(item.severity, theme),
                      ),
                      title: Text(item.message),
                      subtitle: Text(item.time),
                      trailing: Text(item.severity.toUpperCase(), style: theme.textTheme.labelSmall),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Color _colorFor(String severity, ThemeData theme) {
    switch (severity) {
      case 'alert':
        return theme.colorScheme.error;
      case 'warning':
        return Colors.orange;
      default:
        return theme.colorScheme.primary;
    }
  }

  IconData _iconFor(String severity) {
    switch (severity) {
      case 'alert':
        return Icons.warning_amber_outlined;
      case 'warning':
        return Icons.error_outline;
      default:
        return Icons.notifications_outlined;
    }
  }
}
