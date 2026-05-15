import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/config/app_flavor.dart';
import 'core/config/app_router.dart';
import 'core/config/app_theme.dart';
import 'core/localization/app_language.dart';

class RestaurantSuiteApp extends ConsumerWidget {
  const RestaurantSuiteApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(appRouterProvider);
    final flavor = ref.watch(appFlavorProvider);
    final language = ref.watch(appLanguageProvider);
    return MaterialApp.router(
      title: flavor.title,
      locale: language.locale,
      supportedLocales: const [
        Locale('en'),
        Locale('ar'),
      ],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ],
      theme: AppTheme.light,
      debugShowCheckedModeBanner: false,
      routerConfig: router,
      builder: (context, child) {
        return Directionality(
          textDirection: language.textDirection,
          child: child ?? const SizedBox.shrink(),
        );
      },
    );
  }
}
