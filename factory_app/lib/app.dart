import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/config/app_flavor.dart';
import 'core/config/app_router.dart';
import 'core/config/app_theme.dart';

class RestaurantSuiteApp extends ConsumerWidget {
  const RestaurantSuiteApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(appRouterProvider);
    final flavor = ref.watch(appFlavorProvider);
    return MaterialApp.router(
      title: flavor.title,
      theme: AppTheme.light,
      debugShowCheckedModeBanner: false,
      routerConfig: router,
    );
  }
}
