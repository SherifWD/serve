import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../config/app_theme.dart';

class ThemeModeToggle extends ConsumerWidget {
  const ThemeModeToggle({
    super.key,
    this.compact = false,
    this.foregroundColor,
  });

  final bool compact;
  final Color? foregroundColor;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final mode = ref.watch(appThemeModeProvider);
    final isDark = mode == ThemeMode.dark;
    final icon = isDark ? Icons.dark_mode_outlined : Icons.light_mode_outlined;
    final nextLabel = isDark ? 'Switch to light theme' : 'Switch to dark theme';
    final color = foregroundColor ?? Theme.of(context).colorScheme.onSurface;

    void toggle() {
      ref.read(appThemeModeProvider.notifier).toggle();
    }

    if (compact) {
      return IconButton(
        tooltip: nextLabel,
        color: color,
        onPressed: toggle,
        icon: Icon(icon),
      );
    }

    return Tooltip(
      message: nextLabel,
      child: SegmentedButton<ThemeMode>(
        segments: const [
          ButtonSegment<ThemeMode>(
            value: ThemeMode.dark,
            icon: Icon(Icons.dark_mode_outlined),
            label: Text('Dark'),
          ),
          ButtonSegment<ThemeMode>(
            value: ThemeMode.light,
            icon: Icon(Icons.light_mode_outlined),
            label: Text('Light'),
          ),
        ],
        selected: {mode},
        showSelectedIcon: false,
        onSelectionChanged: (selection) {
          ref.read(appThemeModeProvider.notifier).setMode(selection.first);
        },
      ),
    );
  }
}
