import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

class ManualRefreshHeader extends StatelessWidget {
  const ManualRefreshHeader({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.onRefresh,
    this.lastUpdatedAt,
    this.hasUpdates = false,
    this.dark = false,
    super.key,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Future<void> Function() onRefresh;
  final DateTime? lastUpdatedAt;
  final bool hasUpdates;
  final bool dark;

  @override
  Widget build(BuildContext context) {
    final foreground = dark ? Colors.white : const Color(0xFF111827);
    final muted = dark ? Colors.white70 : const Color(0xFF6B7280);
    final border = dark
        ? Colors.white.withValues(alpha: 0.10)
        : const Color(0xFFE5E7EB);
    final background =
        dark ? Colors.white.withValues(alpha: 0.06) : Colors.white;
    final updated = lastUpdatedAt == null
        ? 'Not refreshed yet'
        : 'Last updated ${DateFormat('HH:mm').format(lastUpdatedAt!)}';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: background,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: border),
          ),
          child: LayoutBuilder(
            builder: (context, constraints) {
              final compact = constraints.maxWidth < 560;
              final titleBlock = Row(
                children: [
                  Container(
                    width: 42,
                    height: 42,
                    decoration: BoxDecoration(
                      color: const Color(0xFF2A9D8F).withValues(alpha: 0.14),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(icon, color: const Color(0xFF2A9D8F)),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: Theme.of(context)
                              .textTheme
                              .titleMedium
                              ?.copyWith(
                                color: foreground,
                                fontWeight: FontWeight.w900,
                              ),
                        ),
                        const SizedBox(height: 3),
                        Text(
                          subtitle,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(color: muted),
                        ),
                      ],
                    ),
                  ),
                ],
              );
              final actions = Wrap(
                spacing: 8,
                runSpacing: 8,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  Text(
                    updated,
                    style: TextStyle(color: muted, fontWeight: FontWeight.w700),
                  ),
                  FilledButton.tonalIcon(
                    onPressed: onRefresh,
                    icon: const Icon(Icons.refresh_rounded),
                    label: const Text('Refresh'),
                  ),
                ],
              );

              if (compact) {
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    titleBlock,
                    const SizedBox(height: 12),
                    actions,
                  ],
                );
              }

              return Row(
                children: [
                  Expanded(child: titleBlock),
                  const SizedBox(width: 12),
                  actions,
                ],
              );
            },
          ),
        ),
        if (hasUpdates) ...[
          const SizedBox(height: 8),
          Material(
            color: const Color(0xFFFFF7ED),
            borderRadius: BorderRadius.circular(8),
            child: InkWell(
              borderRadius: BorderRadius.circular(8),
              onTap: onRefresh,
              child: const Padding(
                padding: EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                child: Row(
                  children: [
                    Icon(Icons.notifications_active_outlined,
                        color: Color(0xFFC2410C)),
                    SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        'New updates are available. Refresh when ready.',
                        style: TextStyle(
                          color: Color(0xFF9A3412),
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ],
    );
  }
}
