import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../localization/app_language.dart';

class LanguageToggle extends ConsumerWidget {
  const LanguageToggle({
    this.compact = false,
    this.foregroundColor,
    super.key,
  });

  final bool compact;
  final Color? foregroundColor;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final language = ref.watch(appLanguageProvider);
    final strings = ref.watch(appStringsProvider);
    final next = language == AppLanguage.en ? AppLanguage.ar : AppLanguage.en;
    final label = next == AppLanguage.ar ? 'AR' : 'EN';

    if (compact) {
      return IconButton(
        tooltip: strings.t('language.switch'),
        color: foregroundColor,
        onPressed: () => ref.read(appLanguageProvider.notifier).state = next,
        icon: Text(
          label,
          style: TextStyle(
            color: foregroundColor,
            fontWeight: FontWeight.w900,
          ),
        ),
      );
    }

    return OutlinedButton.icon(
      onPressed: () => ref.read(appLanguageProvider.notifier).state = next,
      icon: const Icon(Icons.language_rounded, size: 18),
      label: Text(next == AppLanguage.ar
          ? strings.t('language.arabic')
          : strings.t('language.english')),
    );
  }
}
