import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:restaurant_suite/core/models/app_models.dart';
import 'package:restaurant_suite/features/suite/data/suite_repository.dart';
import 'package:restaurant_suite/features/waiter/presentation/waiter_order_page.dart';

void main() {
  testWidgets('adding an item closes the sheet before creating the order',
      (tester) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = const Size(1200, 1600);
    addTearDown(() {
      tester.view.resetDevicePixelRatio();
      tester.view.resetPhysicalSize();
    });

    final repository = _WaiterOrderRepository();

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          suiteRepositoryProvider.overrideWithValue(repository),
        ],
        child: MaterialApp(
          home: WaiterOrderPage(table: repository.table),
        ),
      ),
    );

    await tester.pumpAndSettle();

    await tester.tap(find.text('Add'));
    await tester.pumpAndSettle();

    await tester.tap(find.text('Add to order'));
    await tester.pumpAndSettle();

    expect(repository.createdOrders, hasLength(1));
    expect(tester.takeException(), isNull);
  });

  testWidgets('waiter item actions respect kitchen and refund state',
      (tester) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = const Size(1200, 1600);
    addTearDown(() {
      tester.view.resetDevicePixelRatio();
      tester.view.resetPhysicalSize();
    });

    final repository = _WaiterOrderRepository(
      details: const TableDetails(
        id: 1,
        name: 'Table 1',
        status: 'occupied',
        orderId: 99,
        items: [
          OrderItemLine(
            id: 10,
            name: 'Tomato soup',
            quantity: 1,
            total: 80,
            price: 80,
            status: 'pending',
            kdsStatus: 'pending',
          ),
        ],
      ),
    );

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          suiteRepositoryProvider.overrideWithValue(repository),
        ],
        child: MaterialApp(
          home: WaiterOrderPage(table: repository.table),
        ),
      ),
    );

    await tester.pumpAndSettle();

    expect(
      tester
          .widget<IconButton>(
              find.widgetWithIcon(IconButton, Icons.remove_circle_outline))
          .onPressed,
      isNull,
    );
    expect(
      tester
          .widget<IconButton>(
              find.widgetWithIcon(IconButton, Icons.soup_kitchen_outlined))
          .onPressed,
      isNull,
    );
    expect(
      tester
          .widget<IconButton>(
              find.widgetWithIcon(IconButton, Icons.restart_alt_outlined))
          .onPressed,
      isNotNull,
    );
    expect(
      _buttonWithText(tester, 'Send to kitchen').onPressed,
      isNotNull,
    );
  });

  testWidgets('send to kitchen is disabled when every item is refunded',
      (tester) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = const Size(1200, 1600);
    addTearDown(() {
      tester.view.resetDevicePixelRatio();
      tester.view.resetPhysicalSize();
    });

    final repository = _WaiterOrderRepository(
      details: const TableDetails(
        id: 1,
        name: 'Table 1',
        status: 'occupied',
        orderId: 99,
        items: [
          OrderItemLine(
            id: 10,
            name: 'Tomato soup',
            quantity: 1,
            total: 80,
            price: 80,
            status: 'refunded',
            kdsStatus: 'refunded',
          ),
        ],
      ),
    );

    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          suiteRepositoryProvider.overrideWithValue(repository),
        ],
        child: MaterialApp(
          home: WaiterOrderPage(table: repository.table),
        ),
      ),
    );

    await tester.pumpAndSettle();

    expect(
      _buttonWithText(tester, 'Send to kitchen').onPressed,
      isNull,
    );
    expect(
      tester
          .widget<IconButton>(
              find.widgetWithIcon(IconButton, Icons.restart_alt_outlined))
          .onPressed,
      isNull,
    );
  });
}

ButtonStyleButton _buttonWithText(WidgetTester tester, String text) {
  final finder = find.ancestor(
    of: find.text(text),
    matching: find.byWidgetPredicate((widget) => widget is ButtonStyleButton),
  );
  expect(finder, findsOneWidget);
  return tester.widget<ButtonStyleButton>(finder);
}

class _WaiterOrderRepository extends SuiteRepository {
  _WaiterOrderRepository({TableDetails? details})
      : _details = details,
        super(Dio());

  final table = const TableOverview(
    id: 1,
    name: 'Table 1',
    seats: 2,
    status: 'open',
  );

  final TableDetails? _details;
  final createdOrders = <Map<String, dynamic>>[];

  @override
  Future<TableDetails> fetchTableDetails(int tableId) async {
    return _details ??
        TableDetails(
          id: table.id,
          name: table.name,
          status: table.status,
          items: const [],
        );
  }

  @override
  Future<List<MenuCategoryData>> fetchMenu() async {
    return const [
      MenuCategoryData(
        id: 1,
        name: 'Mains',
        products: [
          MenuProduct(id: 10, name: 'Grilled Chicken', price: 180),
        ],
        questions: [],
      ),
    ];
  }

  @override
  Future<List<ModifierData>> fetchModifiers() async {
    return const [];
  }

  @override
  Future<List<TableOverview>> fetchTables() async {
    return [table];
  }

  @override
  Future<void> createOrder({
    required int tableId,
    required List<Map<String, dynamic>> items,
    String? customerName,
    String? customerPhone,
    String? customerEmail,
  }) async {
    createdOrders.add({
      'table_id': tableId,
      'items': items,
      'customer_name': customerName,
      'customer_phone': customerPhone,
      'customer_email': customerEmail,
    });
  }
}
