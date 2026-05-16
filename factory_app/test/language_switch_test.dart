import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:restaurant_suite/app.dart';
import 'package:restaurant_suite/core/config/app_flavor.dart';
import 'package:restaurant_suite/core/models/app_models.dart';
import 'package:restaurant_suite/features/auth/data/auth_repository.dart';
import 'package:restaurant_suite/features/auth/providers/auth_providers.dart';
import 'package:restaurant_suite/features/suite/data/suite_repository.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

void main() {
  testWidgets('language can switch back and forth on the routed app',
      (tester) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = const Size(1200, 1600);
    addTearDown(() {
      tester.view.resetDevicePixelRatio();
      tester.view.resetPhysicalSize();
    });

    const session = AppSession(
      id: 1,
      name: 'Floor Team',
      token: 'token',
      roles: [AppRole.waiter],
      activeRole: AppRole.waiter,
    );

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          appFlavorProvider.overrideWithValue(AppFlavor.waiter),
          authProvider.overrideWith((ref) => _FakeAuthNotifier(session)),
          suiteRepositoryProvider
              .overrideWithValue(_LanguageSwitchRepository()),
        ],
        child: const RestaurantSuiteApp(),
      ),
    );

    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);

    await tester.tap(find.text('AR'));
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);

    await tester.tap(find.text('EN'));
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
  });
}

class _FakeAuthNotifier extends AuthNotifier {
  _FakeAuthNotifier(AppSession session)
      : super(_FakeAuthRepository(session), AppRole.waiter) {
    state = AuthState(
      session: session,
      hasBootstrapped: true,
      isLoading: false,
    );
  }
}

class _FakeAuthRepository extends AuthRepository {
  _FakeAuthRepository(this.session)
      : super(
          Dio(),
          const FlutterSecureStorage(),
          sessionKey: 'language_switch_test_session',
        );

  final AppSession session;

  @override
  Future<AppSession?> restoreSession() async => session;

  @override
  Future<AppSession> switchRole(AppSession session, AppRole role) async {
    return session.copyWith(activeRole: role);
  }

  @override
  Future<void> logout(AppSession session) async {}
}

class _LanguageSwitchRepository extends SuiteRepository {
  _LanguageSwitchRepository() : super(Dio());

  @override
  Future<List<TableOverview>> fetchTables() async {
    return const [
      TableOverview(
        id: 1,
        name: 'Table 1',
        seats: 4,
        status: 'open',
      ),
      TableOverview(
        id: 2,
        name: 'Table 2',
        seats: 2,
        status: 'occupied',
        orderId: 22,
        serviceStatus: 'kitchen',
        orderTotal: 250,
        itemCount: 3,
      ),
    ];
  }
}
