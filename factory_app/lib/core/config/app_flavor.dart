import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../models/app_models.dart';

enum AppFlavor {
  suite,
  customer,
  waiter,
  cashier,
  kitchen,
  owner,
}

extension AppFlavorX on AppFlavor {
  String get title {
    switch (this) {
      case AppFlavor.suite:
        return 'Restaurant Staff App';
      case AppFlavor.customer:
        return 'Restaurant Customer App';
      case AppFlavor.waiter:
        return 'Restaurant Waiter App';
      case AppFlavor.cashier:
        return 'Restaurant Cashier App';
      case AppFlavor.kitchen:
        return 'Restaurant Kitchen App';
      case AppFlavor.owner:
        return 'Restaurant Owner App';
    }
  }

  String get shellLabel {
    switch (this) {
      case AppFlavor.suite:
        return 'Staff suite';
      case AppFlavor.customer:
        return 'Customer';
      case AppFlavor.waiter:
        return 'Waiter';
      case AppFlavor.cashier:
        return 'Cashier';
      case AppFlavor.kitchen:
        return 'Kitchen';
      case AppFlavor.owner:
        return 'Owner';
    }
  }

  AppRole? get fixedRole {
    switch (this) {
      case AppFlavor.suite:
        return null;
      case AppFlavor.customer:
        return AppRole.customer;
      case AppFlavor.waiter:
        return AppRole.waiter;
      case AppFlavor.cashier:
        return AppRole.cashier;
      case AppFlavor.kitchen:
        return AppRole.kitchen;
      case AppFlavor.owner:
        return AppRole.owner;
    }
  }

  String get sessionKeySuffix {
    switch (this) {
      case AppFlavor.suite:
        return 'suite';
      case AppFlavor.customer:
        return 'customer';
      case AppFlavor.waiter:
        return 'waiter';
      case AppFlavor.cashier:
        return 'cashier';
      case AppFlavor.kitchen:
        return 'kitchen';
      case AppFlavor.owner:
        return 'owner';
    }
  }
}

final appFlavorProvider = Provider<AppFlavor>((ref) => AppFlavor.suite);

final fixedRoleProvider =
    Provider<AppRole?>((ref) => ref.watch(appFlavorProvider).fixedRole);
