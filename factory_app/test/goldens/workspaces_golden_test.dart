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

  final fakeRepo = _FakeSuiteRepository();

  setUp(() {
    debugDisableShadows = true;
  });

  Future<void> pumpWorkspace(
    WidgetTester tester, {
    required Widget child,
    required AppSession session,
  }) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = const Size(1440, 2200);
    addTearDown(() {
      tester.view.resetPhysicalSize();
      tester.view.resetDevicePixelRatio();
    });

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authProvider.overrideWith((ref) => _FakeAuthNotifier(session)),
          suiteRepositoryProvider.overrideWithValue(fakeRepo),
        ],
        child: MaterialApp(
          debugShowCheckedModeBanner: false,
          theme: _goldenTheme(),
          home: Scaffold(
            body: SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: child,
              ),
            ),
          ),
        ),
      ),
    );

    await tester.pumpAndSettle();
  }

  testWidgets('customer workspace golden', (tester) async {
    await pumpWorkspace(
      tester,
      child: const CustomerWorkspacePage(),
      session: _Sessions.customer,
    );

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile('../../../docs/assets/flutter/customer-workspace.png'),
    );
  });

  testWidgets('waiter workspace golden', (tester) async {
    await pumpWorkspace(
      tester,
      child: const WaiterWorkspacePage(),
      session: _Sessions.waiter,
    );

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile('../../../docs/assets/flutter/waiter-workspace.png'),
    );
  });

  testWidgets('waiter order detail golden', (tester) async {
    await pumpWorkspace(
      tester,
      child: WaiterOrderPage(table: fakeRepo.tables.first),
      session: _Sessions.waiter,
    );

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile('../../../docs/assets/flutter/waiter-order-detail.png'),
    );
  });

  testWidgets('cashier workspace golden', (tester) async {
    await pumpWorkspace(
      tester,
      child: const CashierWorkspacePage(),
      session: _Sessions.cashier,
    );

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile('../../../docs/assets/flutter/cashier-workspace.png'),
    );
  });

  testWidgets('kitchen workspace golden', (tester) async {
    await pumpWorkspace(
      tester,
      child: const KitchenWorkspacePage(),
      session: _Sessions.kitchen,
    );

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile('../../../docs/assets/flutter/kitchen-workspace.png'),
    );
  });

  testWidgets('owner workspace golden', (tester) async {
    await pumpWorkspace(
      tester,
      child: const OwnerWorkspacePage(),
      session: _Sessions.owner,
    );

    await expectLater(
      find.byType(MaterialApp),
      matchesGoldenFile('../../../docs/assets/flutter/owner-workspace.png'),
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
            color: colorScheme.outlineVariant.withValues(alpha: 0.35)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(18),
        borderSide: BorderSide(
            color: colorScheme.outlineVariant.withValues(alpha: 0.35)),
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
      side:
          BorderSide(color: colorScheme.outlineVariant.withValues(alpha: 0.35)),
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
            color: colorScheme.outlineVariant.withValues(alpha: 0.4)),
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
          sessionKey: 'golden_test_session',
        );

  @override
  Future<AppSession?> restoreSession() async => null;

  @override
  Future<void> logout(AppSession session) async {}
}

class _FakeSuiteRepository extends SuiteRepository {
  _FakeSuiteRepository() : super(Dio());

  final tables = const [
    TableOverview(
      id: 1,
      name: 'T1 Terrace',
      seats: 4,
      status: 'occupied',
      orderId: 81,
      orderTotal: 318,
      itemCount: 4,
      customerName: 'Nour',
    ),
    TableOverview(
      id: 2,
      name: 'T2 Window',
      seats: 2,
      status: 'open',
    ),
    TableOverview(
      id: 3,
      name: 'T3 Family',
      seats: 6,
      status: 'occupied',
      orderId: 82,
      orderTotal: 540,
      itemCount: 7,
      customerName: 'Mazen',
    ),
    TableOverview(
      id: 4,
      name: 'T4 Bar',
      seats: 2,
      status: 'open',
    ),
  ];

  final menu = const [
    MenuCategoryData(
      id: 1,
      name: 'Coffee',
      products: [
        MenuProduct(id: 1, name: 'Flat White', price: 95),
        MenuProduct(id: 2, name: 'Iced Spanish Latte', price: 110),
      ],
      questions: [
        CategoryQuestionData(
          id: 1,
          question: 'Sugar preference',
          choices: [
            CategoryChoiceData(id: 1, label: 'No sugar'),
            CategoryChoiceData(id: 2, label: 'Medium'),
            CategoryChoiceData(id: 3, label: 'Sweet'),
          ],
        ),
      ],
    ),
    MenuCategoryData(
      id: 2,
      name: 'Kitchen',
      products: [
        MenuProduct(id: 3, name: 'Chicken Caesar Wrap', price: 148),
        MenuProduct(id: 4, name: 'San Sebastian Cheesecake', price: 75),
      ],
      questions: [],
    ),
  ];

  final modifiers = const [
    ModifierData(id: 1, name: 'Extra Shot', price: 20),
    ModifierData(id: 2, name: 'Oat Milk', price: 18),
    ModifierData(id: 3, name: 'No Onions', price: 0),
  ];

  @override
  Future<CustomerHomeData> fetchCustomerHome() async {
    return CustomerHomeData(
      name: 'Nour',
      loyaltyPoints: 148,
      restaurants: await _restaurantsPage(),
      recentOrders: [
        CustomerOrder(
          id: 81,
          total: 318,
          status: 'paid',
          paymentStatus: 'paid',
          orderType: 'dine-in',
          createdAt: DateTime(2026, 4, 1, 20, 15),
          items: const [
            OrderItemLine(id: 1, name: 'Flat White', quantity: 1, total: 95),
            OrderItemLine(
                id: 2, name: 'Chicken Caesar Wrap', quantity: 1, total: 148),
            OrderItemLine(id: 3, name: 'Cheesecake', quantity: 1, total: 75),
          ],
          payments: const [PaymentRecord(id: 1, method: 'card', amount: 318)],
          branchName: 'Nile Bean Downtown',
          branchLocation: 'Zamalek',
          restaurantName: 'Nile Bean',
          paymentMethod: 'card',
        ),
      ],
      loyaltyPreview: [
        LoyaltyEntry(
          id: 1,
          type: 'earn',
          points: 31,
          createdAt: DateTime(2026, 4, 1, 20, 17),
          orderId: 81,
          restaurantName: 'Nile Bean',
          branchName: 'Nile Bean Downtown',
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
    return PagedResponse<RestaurantListing>(
      items: await _restaurantsPage(),
      meta: const PaginationMeta(
          currentPage: 1, lastPage: 1, perPage: 12, total: 3),
    );
  }

  Future<List<RestaurantListing>> _restaurantsPage() async {
    return const [
      RestaurantListing(
        id: 1,
        name: 'Nile Bean',
        branchCount: 2,
        branches: [
          BranchInfo(id: 1, name: 'Nile Bean Downtown', location: 'Zamalek'),
          BranchInfo(id: 2, name: 'Nile Bean Mall', location: 'New Cairo'),
        ],
      ),
      RestaurantListing(
        id: 2,
        name: 'Saffron Table',
        branchCount: 2,
        branches: [
          BranchInfo(id: 3, name: 'Saffron Heliopolis', location: 'Heliopolis'),
          BranchInfo(id: 4, name: 'Saffron North Coast', location: 'Marassi'),
        ],
      ),
      RestaurantListing(
        id: 3,
        name: 'Cairo Brunch Club',
        branchCount: 1,
        branches: [
          BranchInfo(id: 5, name: 'CBC Sheikh Zayed', location: 'Sheikh Zayed'),
        ],
      ),
    ];
  }

  @override
  Future<CustomerRestaurantDetail> fetchCustomerRestaurantDetail({
    required int restaurantId,
    int page = 1,
    int perPage = 12,
    String search = '',
  }) async {
    final restaurants = await _restaurantsPage();
    final restaurant = restaurants.firstWhere(
      (item) => item.id == restaurantId,
      orElse: () => restaurants.first,
    );
    return CustomerRestaurantDetail(
      restaurant: restaurant,
      items: const [
        CustomerMenuItem(
          id: 1,
          name: 'Flat White',
          price: 95,
          categoryName: 'Coffee',
          branchName: 'Nile Bean Downtown',
          branchLocation: 'Zamalek',
        ),
        CustomerMenuItem(
          id: 2,
          name: 'Chicken Caesar Wrap',
          price: 148,
          categoryName: 'Kitchen',
          branchName: 'Nile Bean Downtown',
          branchLocation: 'Zamalek',
        ),
      ],
      meta: const PaginationMeta(
        currentPage: 1,
        lastPage: 1,
        perPage: 12,
        total: 2,
      ),
    );
  }

  @override
  Future<PagedResponse<CustomerOrder>> fetchCustomerOrders(
      {int page = 1, int perPage = 10}) async {
    return PagedResponse<CustomerOrder>(
      items: [
        (await fetchCustomerHome()).recentOrders.first,
        CustomerOrder(
          id: 72,
          total: 410,
          status: 'paid',
          paymentStatus: 'paid',
          orderType: 'dine-in',
          createdAt: DateTime(2026, 3, 23, 13, 30),
          items: const [
            OrderItemLine(
                id: 8, name: 'Iced Spanish Latte', quantity: 2, total: 220),
            OrderItemLine(
                id: 9, name: 'Halloumi Croissant', quantity: 2, total: 190),
          ],
          payments: const [PaymentRecord(id: 2, method: 'cash', amount: 410)],
          branchName: 'Nile Bean Mall',
          branchLocation: 'New Cairo',
          restaurantName: 'Nile Bean',
          paymentMethod: 'cash',
        ),
      ],
      meta: const PaginationMeta(
          currentPage: 1, lastPage: 1, perPage: 10, total: 2),
    );
  }

  @override
  Future<CustomerOrder> fetchCustomerOrderDetail(int orderId) async {
    final orders = (await fetchCustomerOrders()).items;
    return orders.firstWhere((item) => item.id == orderId, orElse: () => orders.first);
  }

  @override
  Future<PagedResponse<LoyaltyEntry>> fetchCustomerLoyalty(
      {int page = 1, int perPage = 10}) async {
    return PagedResponse<LoyaltyEntry>(
      items: [
        LoyaltyEntry(
          id: 1,
          type: 'earn',
          points: 31,
          createdAt: DateTime(2026, 4, 1, 20, 17),
          orderId: 81,
          restaurantName: 'Nile Bean',
          branchName: 'Nile Bean Downtown',
        ),
        LoyaltyEntry(
          id: 2,
          type: 'earn',
          points: 41,
          createdAt: DateTime(2026, 3, 23, 13, 44),
          orderId: 72,
          restaurantName: 'Nile Bean',
          branchName: 'Nile Bean Mall',
        ),
      ],
      meta: const PaginationMeta(
          currentPage: 1, lastPage: 1, perPage: 10, total: 2),
    );
  }

  @override
  Future<List<TableOverview>> fetchTables() async => tables;

  @override
  Future<TableDetails> fetchTableDetails(int tableId) async {
    return const TableDetails(
      id: 1,
      name: 'T1 Terrace',
      status: 'occupied',
      orderId: 81,
      orderTotal: 318,
      customerName: 'Nour',
      customerPhone: '01012345678',
      items: [
        OrderItemLine(
          id: 11,
          name: 'Flat White',
          quantity: 1,
          total: 95,
          itemNote: 'Extra hot',
          modifiers: ['Extra Shot', 'Oat Milk'],
        ),
        OrderItemLine(
          id: 12,
          name: 'Chicken Caesar Wrap',
          quantity: 1,
          total: 148,
          itemNote: 'Cut in half',
        ),
        OrderItemLine(
          id: 13,
          name: 'San Sebastian Cheesecake',
          quantity: 1,
          total: 75,
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
        id: 81,
        tableName: 'T1 Terrace',
        waiter: 'Amr',
        items: [
          OrderItemLine(
            id: 11,
            name: 'Flat White',
            quantity: 1,
            total: 95,
            kdsStatus: 'queued',
            itemNote: 'Extra hot',
            modifiers: ['Extra Shot', 'Oat Milk'],
          ),
          OrderItemLine(
            id: 12,
            name: 'Chicken Caesar Wrap',
            quantity: 1,
            total: 148,
            kdsStatus: 'preparing',
          ),
        ],
      ),
      KdsTicket(
        id: 82,
        tableName: 'T3 Family',
        waiter: 'Salma',
        items: [
          OrderItemLine(
            id: 14,
            name: 'Iced Spanish Latte',
            quantity: 2,
            total: 220,
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
        id: 81,
        total: 318,
        status: 'cashier',
        paymentStatus: 'partial',
        orderType: 'dine-in',
        tableName: 'T1 Terrace',
        customerName: 'Nour',
        customerPhone: '01012345678',
        branchName: 'Nile Bean Downtown',
        restaurantName: 'Nile Bean',
        items: [
          OrderItemLine(id: 11, name: 'Flat White', quantity: 1, total: 95),
          OrderItemLine(
              id: 12, name: 'Chicken Caesar Wrap', quantity: 1, total: 148),
          OrderItemLine(
              id: 13, name: 'San Sebastian Cheesecake', quantity: 1, total: 75),
        ],
        payments: [
          PaymentRecord(id: 1, method: 'cash', amount: 100),
        ],
      ),
      StaffOrderSnapshot(
        id: 82,
        total: 540,
        status: 'cashier',
        paymentStatus: 'unpaid',
        orderType: 'dine-in',
        tableName: 'T3 Family',
        customerName: 'Mazen',
        customerPhone: '01000000000',
        branchName: 'Nile Bean Downtown',
        restaurantName: 'Nile Bean',
        items: [
          OrderItemLine(
              id: 14, name: 'Iced Spanish Latte', quantity: 2, total: 220),
          OrderItemLine(
              id: 15, name: 'Halloumi Croissant', quantity: 2, total: 190),
          OrderItemLine(
              id: 16, name: 'Sparkling Water', quantity: 2, total: 130),
        ],
        payments: [],
      ),
    ];
  }

  @override
  Future<OwnerSummary> fetchOwnerSummary({int? branchId}) async {
    return const OwnerSummary(
      totalSales: 15420,
      ordersCount: 126,
      avgOrderValue: 122.38,
      productCount: 48,
      employeeCount: 27,
      activeTables: 8,
      cashierQueue: 3,
      kdsBacklog: 5,
      loyaltyMembers: 382,
      paymentMix: [
        PaymentMixEntry(method: 'card', total: 10240),
        PaymentMixEntry(method: 'cash', total: 4120),
        PaymentMixEntry(method: 'wallet', total: 1060),
      ],
      branchPerformance: [
        BranchPerformance(
            id: 1,
            name: 'Nile Bean Downtown',
            sales: 8420,
            ordersCount: 68,
            location: 'Zamalek'),
        BranchPerformance(
            id: 2,
            name: 'Nile Bean Mall',
            sales: 7000,
            ordersCount: 58,
            location: 'New Cairo'),
      ],
      topProducts: [
        {'name': 'Flat White', 'quantity': 94},
        {'name': 'Chicken Caesar Wrap', 'quantity': 72},
        {'name': 'Iced Spanish Latte', 'quantity': 61},
      ],
      lowStockItems: [
        {'name': 'Coffee Beans', 'stock': 6, 'unit': 'kg'},
        {'name': 'Halloumi', 'stock': 4, 'unit': 'kg'},
      ],
      recentOrders: [
        {'id': 81, 'status': 'paid'},
        {'id': 82, 'status': 'cashier'},
      ],
    );
  }
}

class _Sessions {
  static const customer = AppSession(
    id: 1,
    name: 'Nour',
    token: 'customer-token',
    roles: [AppRole.customer],
    activeRole: AppRole.customer,
    phone: '01012345678',
    loyaltyPoints: 148,
  );

  static const waiter = AppSession(
    id: 10,
    name: 'Amr',
    token: 'waiter-token',
    roles: [AppRole.waiter],
    activeRole: AppRole.waiter,
    branchId: 1,
    restaurantId: 1,
  );

  static const cashier = AppSession(
    id: 11,
    name: 'Dina',
    token: 'cashier-token',
    roles: [AppRole.cashier],
    activeRole: AppRole.cashier,
    branchId: 1,
    restaurantId: 1,
  );

  static const kitchen = AppSession(
    id: 12,
    name: 'Salma',
    token: 'kitchen-token',
    roles: [AppRole.kitchen],
    activeRole: AppRole.kitchen,
    branchId: 1,
    restaurantId: 1,
  );

  static const owner = AppSession(
    id: 13,
    name: 'Sherif',
    token: 'owner-token',
    roles: [AppRole.owner],
    activeRole: AppRole.owner,
    restaurantId: 1,
  );
}
