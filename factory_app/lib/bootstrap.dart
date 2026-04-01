import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'app.dart';
import 'core/config/app_flavor.dart';

void bootstrap(AppFlavor flavor) {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(
    ProviderScope(
      overrides: [
        appFlavorProvider.overrideWithValue(flavor),
      ],
      child: const RestaurantSuiteApp(),
    ),
  );
}
