import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:restaurant_suite/core/models/app_models.dart';
import 'package:restaurant_suite/features/auth/data/auth_repository.dart';
import 'package:restaurant_suite/features/auth/providers/auth_providers.dart';
import 'package:restaurant_suite/features/cashier/presentation/cashier_workspace.dart';
import 'package:restaurant_suite/features/customer/presentation/customer_workspace.dart';
import 'package:restaurant_suite/features/kitchen/presentation/kitchen_workspace.dart';
import 'package:restaurant_suite/features/owner/presentation/owner_workspace.dart';
import 'package:restaurant_suite/features/suite/data/suite_repository.dart';
import 'package:restaurant_suite/features/waiter/presentation/waiter_order_page.dart';
import 'package:restaurant_suite/features/waiter/presentation/waiter_workspace.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  final fakeRepo = _FirstDayScenarioRepository();

  setUp(() {
    debugDisableShadows = true;
  });

  Future<void> pumpScene(
    WidgetTester tester, {
    required Widget child,
    required AppSession session,
    required Size size,
  }) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = size;
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });

    final home = child is Scaffold ? child : Scaffold(body: child);

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authProvider.overrideWith((ref) => _FakeAuthNotifier(session)),
          suiteRepositoryProvider.overrideWithValue(fakeRepo),
        ],
        child: MaterialApp(
          debugShowCheckedModeBanner: false,
          theme: _goldenTheme(),
          home: home,
        ),
      ),
    );

    await tester.pumpAndSettle();
  }

  testWidgets('first day customer home golden', (tester) async {
    await pumpScene(
      tester,
      child: const CustomerWorkspacePage(),
      session: _Sessions.customer,
      size: const Size(1040, 1700),
    );

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile(
        '../../../docs/assets/scenario-first-day/flutter/customer-home.png',
      ),
    );
  });

  testWidgets('first day customer restaurant detail golden', (tester) async {
    await pumpScene(
      tester,
      child: const CustomerWorkspacePage(),
      session: _Sessions.customer,
      size: const Size(1040, 1700),
    );

    await tester.tap(find.text('Harbor Ember Kitchen').first);
    await tester.pumpAndSettle();

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile(
        '../../../docs/assets/scenario-first-day/flutter/customer-restaurant-detail.png',
      ),
    );
  });

  testWidgets('first day waiter floor golden', (tester) async {
    await pumpScene(
      tester,
      child: const WaiterWorkspacePage(),
      session: _Sessions.waiter,
      size: const Size(1366, 1024),
    );

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile(
        '../../../docs/assets/scenario-first-day/flutter/waiter-floor.png',
      ),
    );
  });

  testWidgets('first day waiter order golden', (tester) async {
    await pumpScene(
      tester,
      child: WaiterOrderPage(table: fakeRepo.tables.first),
      session: _Sessions.waiter,
      size: const Size(1366, 1100),
    );

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile(
        '../../../docs/assets/scenario-first-day/flutter/waiter-order-composer.png',
      ),
    );
  });

  testWidgets('first day kitchen board golden', (tester) async {
    await pumpScene(
      tester,
      child: const KitchenWorkspacePage(),
      session: _Sessions.kitchen,
      size: const Size(1366, 1100),
    );

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile(
        '../../../docs/assets/scenario-first-day/flutter/kitchen-board.png',
      ),
    );
  });

  testWidgets('first day cashier settlement golden', (tester) async {
    await pumpScene(
      tester,
      child: const CashierWorkspacePage(),
      session: _Sessions.cashier,
      size: const Size(1366, 1100),
    );

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile(
        '../../../docs/assets/scenario-first-day/flutter/cashier-settlement.png',
      ),
    );
  });

  testWidgets('first day owner mobile golden', (tester) async {
    await pumpScene(
      tester,
      child: const OwnerWorkspacePage(),
      session: _Sessions.owner,
      size: const Size(1366, 1100),
    );

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile(
        '../../../docs/assets/scenario-first-day/flutter/owner-mobile-overview.png',
      ),
    );
  });
}

ThemeData _goldenTheme() {
  const seed = Color(0xFFE86C2F);
  final colorScheme = ColorScheme.fromSeed(
    seedColor: seed,
    brightness: Brightness.light,
    primary: seed,
    secondary: const Color(0xFF0F766E),
    surface: const Color(0xFFFFFBF6),
  );

  return ThemeData(
    colorScheme: colorScheme,
    useMaterial3: true,
    scaffoldBackgroundColor: const Color(0xFFF6F0E7),
    cardTheme: CardThemeData(
      elevation: 0,
      color: Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(24)),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(18),
        borderSide: BorderSide(
          color: colorScheme.outlineVariant.withValues(alpha: 0.35),
        ),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(18),
        borderSide: BorderSide(
          color: colorScheme.outlineVariant.withValues(alpha: 0.35),
        ),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(18),
        borderSide: BorderSide(color: colorScheme.primary, width: 1.4),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
    ),
    chipTheme: ChipThemeData(
      backgroundColor: Colors.white,
      selectedColor: colorScheme.primary.withValues(alpha: 0.14),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      side: BorderSide(
        color: colorScheme.outlineVariant.withValues(alpha: 0.35),
      ),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: Colors.white,
      indicatorColor: colorScheme.primary.withValues(alpha: 0.14),
    ),
    navigationRailTheme: NavigationRailThemeData(
      backgroundColor: Colors.white,
      indicatorColor: colorScheme.primary.withValues(alpha: 0.14),
      selectedIconTheme: IconThemeData(color: colorScheme.primary),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: colorScheme.primary,
        foregroundColor: colorScheme.onPrimary,
        elevation: 0,
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: colorScheme.onSurface,
        side: BorderSide(
          color: colorScheme.outlineVariant.withValues(alpha: 0.4),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      ),
    ),
  );
}

class _FakeAuthNotifier extends AuthNotifier {
  _FakeAuthNotifier(AppSession session) : super(_StubAuthRepository(), null) {
    state = AuthState(
      session: session,
      hasBootstrapped: true,
      isLoading: false,
    );
  }
}

class _StubAuthRepository extends AuthRepository {
  _StubAuthRepository()
      : super(
          Dio(),
          const FlutterSecureStorage(),
          sessionKey: 'first_day_scenario_session',
        );

  @override
  Future<AppSession?> restoreSession() async => null;

  @override
  Future<void> logout(AppSession session) async {}
}

class _FirstDayScenarioRepository extends SuiteRepository {
  _FirstDayScenarioRepository() : super(Dio());

  final tables = const [
    TableOverview(
      id: 217,
      name: 'T1 Garden',
      seats: 2,
      status: 'occupied',
      orderId: 244,
      orderTotal: 752,
      itemCount: 6,
      customerName: 'Mona Sami',
    ),
    TableOverview(
      id: 218,
      name: 'T2 Patio',
      seats: 2,
      status: 'occupied',
      orderId: 245,
      orderTotal: 260,
      itemCount: 3,
      customerName: 'Youssef Nabil',
    ),
    TableOverview(
      id: 219,
      name: 'T3 Family',
      seats: 6,
      status: 'occupied',
      orderId: 246,
      orderTotal: 612,
      itemCount: 5,
      customerName: 'Laila Mourad',
    ),
    TableOverview(
      id: 220,
      name: 'T4 Lounge',
      seats: 6,
      status: 'open',
    ),
    TableOverview(
      id: 221,
      name: 'T5 Bar',
      seats: 2,
      status: 'open',
    ),
    TableOverview(
      id: 222,
      name: 'T6 Window',
      seats: 4,
      status: 'open',
    ),
  ];

  final _restaurants = const [
    RestaurantListing(
      id: 15,
      name: 'Harbor Ember Kitchen',
      kind: 'restaurant',
      branchCount: 3,
      coverImageUrl:
          'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80',
      branches: [
        BranchInfo(id: 28, name: 'Downtown Flagship', location: 'Downtown'),
        BranchInfo(id: 29, name: 'New Cairo Studio', location: 'New Cairo'),
        BranchInfo(id: 30, name: 'Maadi Terrace', location: 'Maadi'),
      ],
      featuredItems: [],
    ),
    RestaurantListing(
      id: 16,
      name: 'Bean Harbor Cafe',
      kind: 'cafe',
      branchCount: 2,
      coverImageUrl:
          'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=1200&q=80',
      branches: [
        BranchInfo(id: 31, name: 'Riverside Brew', location: 'Zamalek'),
        BranchInfo(id: 32, name: 'Courtyard Coffee', location: 'Helio'),
      ],
      featuredItems: [
        RestaurantFeaturedItem(
          id: 500,
          name: 'Cold Brew',
          price: 78,
          imageUrl:
              'https://images.unsplash.com/photo-1517705008128-361805f42e86?auto=format&fit=crop&w=1200&q=80',
        ),
      ],
    ),
    RestaurantListing(
      id: 17,
      name: 'Nile Grill House',
      kind: 'restaurant',
      branchCount: 2,
      coverImageUrl:
          'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&w=1200&q=80',
      branches: [
        BranchInfo(id: 33, name: 'Garden City Grill', location: 'Garden'),
        BranchInfo(id: 34, name: 'Katameya Grill', location: 'Katameya'),
      ],
      featuredItems: [],
    ),
  ];

  final menu = const [
    MenuCategoryData(
      id: 136,
      name: 'Breakfast & Brunch',
      products: [
        MenuProduct(
          id: 406,
          name: 'Cedar Chicken Bowl',
          price: 198,
          imageUrl:
              'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1200&q=80',
        ),
        MenuProduct(
          id: 407,
          name: 'Harissa Penne',
          price: 214,
          imageUrl:
              'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=1200&q=80',
        ),
      ],
      questions: [
        CategoryQuestionData(
          id: 1,
          question: 'Serving preference',
          choices: [
            CategoryChoiceData(id: 1, label: 'Regular'),
            CategoryChoiceData(id: 2, label: 'Extra spicy'),
            CategoryChoiceData(id: 3, label: 'No onion'),
          ],
        ),
      ],
    ),
    MenuCategoryData(
      id: 137,
      name: 'Signature Drinks',
      products: [
        MenuProduct(
          id: 408,
          name: 'Spanish Latte',
          price: 92,
          imageUrl:
              'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1200&q=80',
        ),
        MenuProduct(
          id: 409,
          name: 'Citrus Sparkling Cooler',
          price: 84,
          imageUrl:
              'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=1200&q=80',
        ),
      ],
      questions: [
        CategoryQuestionData(
          id: 2,
          question: 'Sweetness',
          choices: [
            CategoryChoiceData(id: 4, label: 'Regular'),
            CategoryChoiceData(id: 5, label: 'Less sweet'),
          ],
        ),
      ],
    ),
    MenuCategoryData(
      id: 138,
      name: 'Desserts',
      products: [
        MenuProduct(
          id: 410,
          name: 'Sea Salt Brownie',
          price: 74,
          imageUrl:
              'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=1200&q=80',
        ),
      ],
      questions: [],
    ),
  ];

  final modifiers = const [
    ModifierData(id: 1, name: 'No Ice', price: 0),
    ModifierData(id: 4, name: 'Oat Milk', price: 20),
    ModifierData(id: 5, name: 'Extra Shot', price: 24),
    ModifierData(id: 6, name: 'No Onion', price: 0),
  ];

  @override
  Future<CustomerHomeData> fetchCustomerHome() async {
    return CustomerHomeData(
      name: 'Mona Sami',
      loyaltyPoints: 61,
      restaurants: _restaurants,
      recentOrders: [
        CustomerOrder(
          id: 244,
          restaurantId: 15,
          branchId: 28,
          total: 616,
          status: 'paid',
          paymentStatus: 'paid',
          orderType: 'dine-in',
          createdAt: DateTime(2026, 4, 3, 18, 42),
          items: const [
            OrderItemLine(
              id: 568,
              name: 'Cedar Chicken Bowl',
              quantity: 2,
              total: 396,
              itemNote: 'One without onion on the pass.',
              imageUrl:
                  'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1200&q=80',
            ),
            OrderItemLine(
              id: 569,
              name: 'Spanish Latte',
              quantity: 1,
              total: 136,
              modifiers: ['Oat Milk', 'Extra Shot'],
              itemNote: 'Less sweet for the second cup.',
              imageUrl:
                  'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1200&q=80',
            ),
            OrderItemLine(
              id: 570,
              name: 'Citrus Sparkling Cooler',
              quantity: 1,
              total: 84,
              modifiers: ['No Ice'],
              imageUrl:
                  'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=1200&q=80',
            ),
          ],
          payments: const [
            PaymentRecord(id: 190, method: 'card', amount: 616),
          ],
          branchName: 'Downtown Flagship',
          branchLocation: 'Downtown Cairo',
          restaurantName: 'Harbor Ember Kitchen',
          paymentMethod: 'card',
        ),
      ],
      loyaltyPreview: [
        LoyaltyEntry(
          id: 1,
          type: 'earn',
          points: 61,
          createdAt: DateTime(2026, 4, 3, 18, 44),
          orderId: 244,
          restaurantId: 15,
          branchId: 28,
          restaurantName: 'Harbor Ember Kitchen',
          branchName: 'Downtown Flagship',
        ),
      ],
    );
  }

  @override
  Future<PagedResponse<RestaurantListing>> fetchRestaurants({
    int page = 1,
    int perPage = 12,
    String search = '',
    String? kind,
  }) async {
    final normalizedSearch = search.trim().toLowerCase();
    final filtered = _restaurants.where((restaurant) {
      final matchesKind = kind == null || restaurant.kind == kind;
      final matchesSearch = normalizedSearch.isEmpty ||
          restaurant.name.toLowerCase().contains(normalizedSearch) ||
          restaurant.branches.any(
            (branch) =>
                branch.name.toLowerCase().contains(normalizedSearch) ||
                (branch.location ?? '')
                    .toLowerCase()
                    .contains(normalizedSearch),
          );
      return matchesKind && matchesSearch;
    }).toList(growable: false);

    return PagedResponse<RestaurantListing>(
      items: filtered,
      meta: PaginationMeta(
        currentPage: page,
        lastPage: 1,
        perPage: perPage,
        total: filtered.length,
      ),
    );
  }

  @override
  Future<CustomerRestaurantDetail> fetchCustomerRestaurantDetail({
    required int restaurantId,
    int page = 1,
    int perPage = 12,
    String search = '',
  }) async {
    final restaurant = _restaurants.firstWhere(
      (item) => item.id == restaurantId,
      orElse: () => _restaurants.first,
    );
    const items = [
      CustomerMenuItem(
        id: 406,
        name: 'Cedar Chicken Bowl',
        price: 198,
        imageUrl:
            'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1200&q=80',
        categoryName: 'Breakfast & Brunch',
        branchId: 28,
        branchName: 'Downtown Flagship',
        branchLocation: 'Downtown Cairo',
      ),
      CustomerMenuItem(
        id: 407,
        name: 'Harissa Penne',
        price: 214,
        imageUrl:
            'https://images.unsplash.com/photo-1473093295043-cdd812d0e601?auto=format&fit=crop&w=1200&q=80',
        categoryName: 'Breakfast & Brunch',
        branchId: 29,
        branchName: 'New Cairo Studio',
        branchLocation: 'New Cairo',
      ),
      CustomerMenuItem(
        id: 408,
        name: 'Spanish Latte',
        price: 92,
        imageUrl:
            'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1200&q=80',
        categoryName: 'Signature Drinks',
        branchId: 28,
        branchName: 'Downtown Flagship',
        branchLocation: 'Downtown Cairo',
      ),
      CustomerMenuItem(
        id: 410,
        name: 'Sea Salt Brownie',
        price: 74,
        imageUrl:
            'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=1200&q=80',
        categoryName: 'Desserts',
        branchId: 30,
        branchName: 'Maadi Terrace',
        branchLocation: 'Maadi',
      ),
    ];

    final normalizedSearch = search.trim().toLowerCase();
    final filtered = items.where((item) {
      if (normalizedSearch.isEmpty) return true;
      return item.name.toLowerCase().contains(normalizedSearch) ||
          (item.categoryName ?? '').toLowerCase().contains(normalizedSearch) ||
          (item.branchName ?? '').toLowerCase().contains(normalizedSearch);
    }).toList(growable: false);

    return CustomerRestaurantDetail(
      restaurant: restaurant,
      items: filtered,
      meta: PaginationMeta(
        currentPage: page,
        lastPage: 1,
        perPage: perPage,
        total: filtered.length,
      ),
    );
  }

  @override
  Future<PagedResponse<CustomerOrder>> fetchCustomerOrders({
    int page = 1,
    int perPage = 10,
  }) async {
    final items = [
      (await fetchCustomerHome()).recentOrders.first,
      CustomerOrder(
        id: 233,
        restaurantId: 16,
        branchId: 31,
        total: 168,
        status: 'paid',
        paymentStatus: 'paid',
        orderType: 'dine-in',
        createdAt: DateTime(2026, 3, 29, 11, 20),
        items: const [
          OrderItemLine(
            id: 700,
            name: 'Cold Brew Tonic',
            quantity: 2,
            total: 156,
          ),
        ],
        payments: const [
          PaymentRecord(id: 191, method: 'cash', amount: 168),
        ],
        branchName: 'Riverside Brew',
        branchLocation: 'Zamalek',
        restaurantName: 'Bean Harbor Cafe',
        paymentMethod: 'cash',
      ),
    ];

    return PagedResponse<CustomerOrder>(
      items: items,
      meta: PaginationMeta(
        currentPage: page,
        lastPage: 1,
        perPage: perPage,
        total: items.length,
      ),
    );
  }

  @override
  Future<CustomerOrder> fetchCustomerOrderDetail(int orderId) async {
    final orders = (await fetchCustomerOrders()).items;
    return orders.firstWhere((item) => item.id == orderId,
        orElse: () => orders.first);
  }

  @override
  Future<PagedResponse<LoyaltyEntry>> fetchCustomerLoyalty({
    int page = 1,
    int perPage = 10,
  }) async {
    final items = [
      LoyaltyEntry(
        id: 1,
        type: 'earn',
        points: 61,
        createdAt: DateTime(2026, 4, 3, 18, 44),
        orderId: 244,
        restaurantId: 15,
        branchId: 28,
        restaurantName: 'Harbor Ember Kitchen',
        branchName: 'Downtown Flagship',
      ),
      LoyaltyEntry(
        id: 2,
        type: 'earn',
        points: 16,
        createdAt: DateTime(2026, 3, 29, 11, 30),
        orderId: 233,
        restaurantId: 16,
        branchId: 31,
        restaurantName: 'Bean Harbor Cafe',
        branchName: 'Riverside Brew',
      ),
    ];

    return PagedResponse<LoyaltyEntry>(
      items: items,
      meta: PaginationMeta(
        currentPage: page,
        lastPage: 1,
        perPage: perPage,
        total: items.length,
      ),
    );
  }

  @override
  Future<List<TableOverview>> fetchTables() async => tables;

  @override
  Future<TableDetails> fetchTableDetails(int tableId) async {
    return const TableDetails(
      id: 217,
      name: 'T1 Garden',
      status: 'occupied',
      orderId: 244,
      orderTotal: 752,
      customerName: 'Mona Sami',
      customerPhone: '01017770001',
      items: [
        OrderItemLine(
          id: 568,
          name: 'Cedar Chicken Bowl',
          quantity: 2,
          total: 396,
          imageUrl:
              'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1200&q=80',
          itemNote: 'One without onion on the pass.',
        ),
        OrderItemLine(
          id: 569,
          name: 'Spanish Latte',
          quantity: 1,
          total: 136,
          imageUrl:
              'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=1200&q=80',
          itemNote: 'Less sweet for the second cup.',
          modifiers: ['Oat Milk', 'Extra Shot'],
        ),
        OrderItemLine(
          id: 570,
          name: 'Citrus Sparkling Cooler',
          quantity: 1,
          total: 84,
          imageUrl:
              'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=1200&q=80',
          modifiers: ['No Ice'],
        ),
        OrderItemLine(
          id: 571,
          name: 'Sea Salt Brownie',
          quantity: 1,
          total: 74,
          imageUrl:
              'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=1200&q=80',
          itemNote: 'Send after mains.',
        ),
      ],
    );
  }

  @override
  Future<List<MenuCategoryData>> fetchMenu() async => menu;

  @override
  Future<List<ModifierData>> fetchModifiers() async => modifiers;

  @override
  Future<List<KdsTicket>> fetchKitchenBoard() async {
    return const [
      KdsTicket(
        id: 244,
        tableName: 'T1 Garden',
        waiter: 'Karim Adel',
        items: [
          OrderItemLine(
            id: 568,
            name: 'Cedar Chicken Bowl',
            quantity: 2,
            total: 396,
            kdsStatus: 'queued',
            itemNote: 'One without onion on the pass.',
          ),
          OrderItemLine(
            id: 569,
            name: 'Spanish Latte',
            quantity: 1,
            total: 136,
            kdsStatus: 'queued',
            modifiers: ['Oat Milk', 'Extra Shot'],
            itemNote: 'Less sweet for the second cup.',
            changeNote: 'Guest kept one latte only.',
          ),
        ],
      ),
      KdsTicket(
        id: 245,
        tableName: 'T2 Patio',
        waiter: 'Karim Adel',
        items: [
          OrderItemLine(
            id: 572,
            name: 'Spanish Latte',
            quantity: 2,
            total: 184,
            kdsStatus: 'preparing',
            modifiers: ['Oat Milk'],
          ),
          OrderItemLine(
            id: 573,
            name: 'Citrus Sparkling Cooler',
            quantity: 1,
            total: 84,
            kdsStatus: 'preparing',
          ),
        ],
      ),
      KdsTicket(
        id: 247,
        tableName: 'T4 Lounge',
        waiter: 'Karim Adel',
        items: [
          OrderItemLine(
            id: 574,
            name: 'Harissa Penne',
            quantity: 2,
            total: 428,
            kdsStatus: 'ready',
          ),
          OrderItemLine(
            id: 575,
            name: 'Spanish Latte',
            quantity: 1,
            total: 92,
            kdsStatus: 'ready',
          ),
        ],
      ),
    ];
  }

  @override
  Future<List<StaffOrderSnapshot>> fetchCashierOrders() async {
    return const [
      StaffOrderSnapshot(
        id: 244,
        total: 616,
        status: 'cashier',
        paymentStatus: 'unpaid',
        orderType: 'dine-in',
        tableName: 'T1 Garden',
        customerName: 'Mona Sami',
        customerPhone: '01017770001',
        branchName: 'Downtown Flagship',
        restaurantName: 'Harbor Ember Kitchen',
        items: [
          OrderItemLine(
            id: 568,
            name: 'Cedar Chicken Bowl',
            quantity: 2,
            total: 396,
          ),
          OrderItemLine(
            id: 569,
            name: 'Spanish Latte',
            quantity: 1,
            total: 136,
          ),
          OrderItemLine(
            id: 570,
            name: 'Citrus Sparkling Cooler',
            quantity: 1,
            total: 84,
          ),
        ],
        payments: [],
      ),
      StaffOrderSnapshot(
        id: 247,
        total: 520,
        status: 'cashier',
        paymentStatus: 'partial',
        orderType: 'dine-in',
        tableName: 'T4 Lounge',
        customerName: 'Laila Mourad',
        customerPhone: '01017770002',
        branchName: 'Downtown Flagship',
        restaurantName: 'Harbor Ember Kitchen',
        items: [
          OrderItemLine(
            id: 574,
            name: 'Harissa Penne',
            quantity: 2,
            total: 428,
          ),
          OrderItemLine(
            id: 575,
            name: 'Spanish Latte',
            quantity: 1,
            total: 92,
          ),
        ],
        payments: [
          PaymentRecord(id: 192, method: 'cash', amount: 200),
        ],
      ),
    ];
  }

  @override
  Future<OwnerSummary> fetchOwnerSummary({
    int? branchId,
    String? preset,
    String? startDate,
    String? endDate,
  }) async {
    return const OwnerSummary(
      totalSales: 1932,
      ordersCount: 5,
      avgOrderValue: 386.4,
      productCount: 15,
      employeeCount: 10,
      activeTables: 0,
      cashierQueue: 0,
      kdsBacklog: 0,
      loyaltyMembers: 5,
      paymentMix: [
        PaymentMixEntry(method: 'card', total: 1464),
        PaymentMixEntry(method: 'cash', total: 468),
      ],
      branchPerformance: [
        BranchPerformance(
          id: 28,
          name: 'Downtown Flagship',
          sales: 1396,
          ordersCount: 3,
          location: 'Downtown Cairo',
        ),
        BranchPerformance(
          id: 29,
          name: 'New Cairo Studio',
          sales: 268,
          ordersCount: 1,
          location: 'New Cairo',
        ),
        BranchPerformance(
          id: 30,
          name: 'Maadi Terrace',
          sales: 268,
          ordersCount: 1,
          location: 'Maadi',
        ),
      ],
      topProducts: [
        {'name': 'Citrus Sparkling Cooler', 'quantity': 3},
        {'name': 'Spanish Latte', 'quantity': 3},
        {'name': 'Harissa Penne', 'quantity': 2},
      ],
      lowStockItems: [
        {'name': 'Sea Salt Brownie', 'stock': 3, 'unit': 'pcs'},
        {'name': 'Cedar Chicken Bowl', 'stock': 7, 'unit': 'pcs'},
      ],
      recentOrders: [
        {'id': 244, 'status': 'paid'},
        {'id': 245, 'status': 'paid'},
        {'id': 247, 'status': 'paid'},
      ],
    );
  }
}

class _Sessions {
  static const customer = AppSession(
    id: 19,
    name: 'Mona Sami',
    token: 'customer-token',
    roles: [AppRole.customer],
    activeRole: AppRole.customer,
    phone: '01017770001',
    loyaltyPoints: 61,
  );

  static const waiter = AppSession(
    id: 193,
    name: 'Karim Adel',
    token: 'waiter-token',
    roles: [AppRole.waiter],
    activeRole: AppRole.waiter,
    branchId: 28,
    restaurantId: 15,
  );

  static const cashier = AppSession(
    id: 194,
    name: 'Salma Nasser',
    token: 'cashier-token',
    roles: [AppRole.cashier],
    activeRole: AppRole.cashier,
    branchId: 28,
    restaurantId: 15,
  );

  static const kitchen = AppSession(
    id: 195,
    name: 'Tamer Wael',
    token: 'kitchen-token',
    roles: [AppRole.kitchen],
    activeRole: AppRole.kitchen,
    branchId: 28,
    restaurantId: 15,
  );

  static const owner = AppSession(
    id: 192,
    name: 'Rana Soliman',
    token: 'owner-token',
    roles: [AppRole.owner],
    activeRole: AppRole.owner,
    restaurantId: 15,
  );
}
