import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/app_models.dart';
import '../../../core/network/api_client.dart';

final suiteRepositoryProvider = Provider<SuiteRepository>((ref) {
  return SuiteRepository(ref.watch(dioProvider));
});

class SuiteRepository {
  SuiteRepository(this._dio);

  final Dio _dio;
  int _mutationCounter = 0;

  Future<CustomerHomeData> fetchCustomerHome() async {
    final response = await _dio.get('/customer/home');
    _throwIfNeeded(response);
    return CustomerHomeData.fromJson(_map(response.data));
  }

  Future<PagedResponse<RestaurantListing>> fetchRestaurants({
    int page = 1,
    int perPage = 12,
    String search = '',
    String? kind,
  }) async {
    final response = await _dio.get(
      '/customer/restaurants',
      queryParameters: {
        'page': page,
        'per_page': perPage,
        'search': search,
        if (kind != null && kind.isNotEmpty) 'kind': kind,
      },
    );
    _throwIfNeeded(response);
    return PagedResponse<RestaurantListing>.fromJson(
      _map(response.data),
      RestaurantListing.fromJson,
    );
  }

  Future<CustomerRestaurantDetail> fetchCustomerRestaurantDetail({
    required int restaurantId,
    int page = 1,
    int perPage = 12,
    String search = '',
    int? branchId,
  }) async {
    final response = await _dio.get(
      '/customer/restaurants/$restaurantId',
      queryParameters: {
        'page': page,
        'per_page': perPage,
        'search': search,
        if (branchId != null) 'branch_id': branchId,
      },
    );
    _throwIfNeeded(response);
    return CustomerRestaurantDetail.fromJson(_map(response.data));
  }

  Future<PagedResponse<CustomerOrder>> fetchCustomerOrders({
    int page = 1,
    int perPage = 10,
  }) async {
    final response = await _dio.get(
      '/customer/orders',
      queryParameters: {'page': page, 'per_page': perPage},
    );
    _throwIfNeeded(response);
    return PagedResponse<CustomerOrder>.fromJson(
      _map(response.data),
      CustomerOrder.fromJson,
    );
  }

  Future<CustomerOrder> fetchCustomerOrderDetail(int orderId) async {
    final response = await _dio.get('/customer/orders/$orderId');
    _throwIfNeeded(response);
    return CustomerOrder.fromJson(_map(_map(response.data)['data']));
  }

  Future<CustomerOrder> createCustomerCheckout({
    required int branchId,
    int? tableId,
    required List<Map<String, dynamic>> items,
    String orderType = 'takeaway',
    String paymentMethod = 'pay_at_counter',
    String? notes,
  }) async {
    final response = await _dio.post(
      '/customer/orders',
      data: {
        'branch_id': branchId,
        if (tableId != null) 'table_id': tableId,
        'order_type': orderType,
        'payment_method': paymentMethod,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
        'items': items,
      },
      options: _mutationOptions('customer-checkout'),
    );
    _throwIfNeeded(response);
    return CustomerOrder.fromJson(_map(_map(response.data)['data']));
  }

  Future<PagedResponse<LoyaltyEntry>> fetchCustomerLoyalty({
    int page = 1,
    int perPage = 10,
  }) async {
    final response = await _dio.get(
      '/customer/loyalty',
      queryParameters: {'page': page, 'per_page': perPage},
    );
    _throwIfNeeded(response);
    return PagedResponse<LoyaltyEntry>.fromJson(
      _map(response.data),
      LoyaltyEntry.fromJson,
    );
  }

  Future<List<TableOverview>> fetchTables() async {
    return (await fetchTableFloor()).tables;
  }

  Future<TableFloorBundle> fetchTableFloor() async {
    final response = await _dio.get('/mobile/tables');
    _throwIfNeeded(response);
    final payload = _map(response.data);
    return TableFloorBundle(
      tables: _list(response.data, 'data')
          .map(TableOverview.fromJson)
          .toList(growable: false),
      operationProfile: OperationProfile.fromJson(
        _map(_map(payload['meta'])['operation_profile']),
      ),
    );
  }

  Future<TableDetails> fetchTableDetails(int tableId) async {
    final response = await _dio.get('/mobile/tables/$tableId');
    _throwIfNeeded(response);
    return TableDetails.fromJson(
        _map(response.data)['data'] as Map<String, dynamic>? ?? const {});
  }

  Future<List<MenuCategoryData>> fetchMenu() async {
    final response = await _dio.get('/mobile/products');
    _throwIfNeeded(response);
    return _list(response.data, 'data')
        .map(MenuCategoryData.fromJson)
        .toList(growable: false);
  }

  Future<List<ModifierData>> fetchModifiers() async {
    final response = await _dio.get('/mobile/modifiers/available');
    _throwIfNeeded(response);
    return _list(response.data, 'data')
        .map(ModifierData.fromJson)
        .toList(growable: false);
  }

  Future<StaffOrderSnapshot> createOrder({
    int? tableId,
    int? branchId,
    String orderType = 'dine-in',
    bool sendToCashier = false,
    required List<Map<String, dynamic>> items,
    String? customerName,
    String? customerPhone,
    String? customerEmail,
  }) async {
    final response = await _dio.post(
      '/mobile/orders',
      data: {
        if (tableId != null) 'table_id': tableId,
        if (branchId != null) 'branch_id': branchId,
        'order_type': orderType,
        if (sendToCashier) 'send_to_cashier': true,
        if (customerName != null && customerName.isNotEmpty)
          'customer_name': customerName,
        if (customerPhone != null && customerPhone.isNotEmpty)
          'customer_phone': customerPhone,
        if (customerEmail != null && customerEmail.isNotEmpty)
          'customer_email': customerEmail,
        'items': items,
      },
      options: _mutationOptions('create-order'),
    );
    _throwIfNeeded(response);
    return StaffOrderSnapshot.fromJson(_map(_map(response.data)['order']));
  }

  Future<void> sendToKds(int orderId) async {
    final response = await _dio.post(
      '/mobile/orders/$orderId/send-to-kds',
      options: _mutationOptions('send-to-kds'),
    );
    _throwIfNeeded(response);
  }

  Future<void> sendToCashier(int orderId) async {
    final response = await _dio.patch(
      '/mobile/orders/$orderId/send-to-cashier',
      options: _mutationOptions('send-to-cashier'),
    );
    _throwIfNeeded(response);
  }

  Future<void> changeOrderItem({
    required int orderItemId,
    required int quantity,
    String? note,
  }) async {
    final response = await _dio.patch(
      '/mobile/order-items/$orderItemId/refund-change',
      data: {
        'action': 'change',
        'quantity': quantity,
        if (note != null && note.isNotEmpty) 'note': note,
      },
      options: _mutationOptions('change-order-item'),
    );
    _throwIfNeeded(response);
  }

  Future<void> refundOrderItem({
    required int orderItemId,
    String? note,
  }) async {
    final response = await _dio.patch(
      '/mobile/order-items/$orderItemId/refund-change',
      data: {
        'action': 'refund',
        if (note != null && note.isNotEmpty) 'note': note,
      },
      options: _mutationOptions('refund-order-item'),
    );
    _throwIfNeeded(response);
  }

  Future<void> returnOrderItemToKitchen({
    required int orderItemId,
    String? note,
  }) async {
    final response = await _dio.patch(
      '/mobile/order-items/$orderItemId/refund-change',
      data: {
        'action': 'return',
        if (note != null && note.isNotEmpty) 'note': note,
      },
      options: _mutationOptions('return-order-item'),
    );
    _throwIfNeeded(response);
  }

  Future<void> moveTable({
    required int fromTableId,
    required int toTableId,
  }) async {
    final response = await _dio.patch(
      '/mobile/tables/$fromTableId/move',
      data: {'to_table_id': toTableId},
      options: _mutationOptions('move-table'),
    );
    _throwIfNeeded(response);
  }

  Future<List<KdsTicket>> fetchKitchenBoard({String? station}) async {
    final response = await _dio.get(
      '/mobile/kds/orders',
      queryParameters: {
        if (station != null && station.isNotEmpty && station != 'all')
          'station': station,
      },
    );
    _throwIfNeeded(response);
    return _list(response.data, 'data')
        .map(KdsTicket.fromJson)
        .toList(growable: false);
  }

  Future<void> updateKitchenItemStatus({
    required int itemId,
    required String status,
  }) async {
    final response = await _dio.patch(
      '/mobile/kds/order-items/$itemId',
      data: {'status': status},
      options: _mutationOptions('kds-item-status'),
    );
    _throwIfNeeded(response);
  }

  Future<void> openCashDrawer({required int branchId}) async {
    final response = await _dio.post(
      '/mobile/print-jobs',
      data: {
        'branch_id': branchId,
        'type': 'cash_drawer',
        'priority': 10,
        'payload': {
          'command': 'open_cash_drawer',
          'requested_at': DateTime.now().toIso8601String(),
        },
      },
      options: _mutationOptions('open-cash-drawer'),
    );
    _throwIfNeeded(response);
  }

  Future<List<StaffOrderSnapshot>> fetchCashierOrders() async {
    final response = await _dio.get(
      '/mobile/orders',
      queryParameters: {'status': 'cashier,paid'},
    );
    _throwIfNeeded(response);
    return _list(response.data, 'data')
        .map(StaffOrderSnapshot.fromJson)
        .toList(growable: false);
  }

  Future<void> payOrder({
    required int orderId,
    required List<Map<String, dynamic>> payments,
    List<int> itemIds = const [],
  }) async {
    final response = await _dio.post(
      '/mobile/orders/$orderId/pay',
      data: {
        'payments': payments,
        if (itemIds.isNotEmpty) 'item_ids': itemIds,
      },
      options: _mutationOptions('pay-order'),
    );
    _throwIfNeeded(response);
  }

  Future<ReceiptDocument> generateReceipt({
    required int orderId,
    List<int> itemIds = const [],
    String scope = 'full',
    bool reprint = false,
  }) async {
    final response = await _dio.get<List<int>>(
      '/mobile/orders/$orderId/receipt',
      queryParameters: {
        'scope': reprint ? 'last' : (itemIds.isEmpty ? scope : 'paid'),
        if (itemIds.isNotEmpty) 'item_ids': itemIds.join(','),
        if (reprint) 'reprint': 1,
      },
      options: Options(
        responseType: ResponseType.bytes,
        headers: const {'Accept': 'application/pdf'},
      ),
    );

    if ((response.statusCode ?? 500) >= 400) {
      throw SuiteException(
        'Receipt request failed with status ${response.statusCode}',
      );
    }

    return ReceiptDocument(
      bytes: Uint8List.fromList(response.data ?? const []),
      filename: _filenameFromDisposition(
        response.headers.value('content-disposition'),
        fallback: 'receipt-$orderId.pdf',
      ),
    );
  }

  Future<ReceiptDocument> generateOwnerReceipt({
    String? preset,
    String? startDate,
    String? endDate,
    int? branchId,
  }) async {
    final response = await _dio.get<List<int>>(
      '/dashboard/receipt',
      queryParameters: {
        if (preset != null) 'preset': preset,
        if (startDate != null) 'start_date': startDate,
        if (endDate != null) 'end_date': endDate,
        if (branchId != null) 'branch_id': branchId,
      },
      options: Options(
        responseType: ResponseType.bytes,
        headers: const {'Accept': 'application/pdf'},
      ),
    );

    if ((response.statusCode ?? 500) >= 400) {
      throw SuiteException(
        'Owner receipt request failed with status ${response.statusCode}',
      );
    }

    return ReceiptDocument(
      bytes: Uint8List.fromList(response.data ?? const []),
      filename: _filenameFromDisposition(
        response.headers.value('content-disposition'),
        fallback: 'owner-receipt.pdf',
      ),
    );
  }

  Future<DataExportDocument> generateDataExport({
    required String dataset,
    int? branchId,
    String? startDate,
    String? endDate,
  }) async {
    final response = await _dio.get<List<int>>(
      '/data-exports/$dataset',
      queryParameters: {
        if (branchId != null) 'branch_id': branchId,
        if (startDate != null) 'from_date': startDate,
        if (endDate != null) 'to_date': endDate,
      },
      options: Options(
        responseType: ResponseType.bytes,
        headers: const {'Accept': 'text/csv'},
      ),
    );

    if ((response.statusCode ?? 500) >= 400) {
      throw SuiteException(
        'Data export failed with status ${response.statusCode}',
      );
    }

    return DataExportDocument(
      bytes: Uint8List.fromList(response.data ?? const []),
      filename: _filenameFromDisposition(
        response.headers.value('content-disposition'),
        fallback: 'janova-$dataset.csv',
      ),
      mimeType: response.headers.value('content-type') ?? 'text/csv',
    );
  }

  Future<OwnerSummary> fetchOwnerSummary({
    int? branchId,
    String? preset,
    String? startDate,
    String? endDate,
  }) async {
    final response = await _dio.get(
      '/dashboard/summary',
      queryParameters: {
        if (branchId != null) 'branch_id': branchId,
        if (preset != null) 'preset': preset,
        if (startDate != null) 'start_date': startDate,
        if (endDate != null) 'end_date': endDate,
      },
    );
    _throwIfNeeded(response);
    return OwnerSummary.fromJson(_map(response.data));
  }

  String _filenameFromDisposition(String? disposition,
      {required String fallback}) {
    if (disposition == null || disposition.isEmpty) return fallback;
    final match = RegExp('filename="?([^";]+)"?').firstMatch(disposition);
    return match?.group(1) ?? fallback;
  }

  Options _mutationOptions(String action) {
    return Options(
      headers: {
        'X-Client-Mutation-Id': _nextMutationId(action),
      },
    );
  }

  String _nextMutationId(String action) {
    _mutationCounter += 1;
    return '$action-${DateTime.now().microsecondsSinceEpoch}-$_mutationCounter';
  }

  void _throwIfNeeded(Response<dynamic> response) {
    if ((response.statusCode ?? 500) < 400) return;
    final payload = _map(response.data);
    throw SuiteException(
      payload['message']?.toString() ??
          payload['error']?.toString() ??
          payload['details']?.toString() ??
          'Request failed with status ${response.statusCode}',
    );
  }

  Map<String, dynamic> _map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return const <String, dynamic>{};
  }

  List<Map<String, dynamic>> _list(dynamic payload, String key) {
    final data = _map(payload)[key];
    if (data is! List) return const <Map<String, dynamic>>[];
    return data.map((item) => _map(item)).toList(growable: false);
  }
}

class SuiteException implements Exception {
  const SuiteException(this.message);

  final String message;

  @override
  String toString() => message;
}

class ReceiptDocument {
  const ReceiptDocument({
    required this.bytes,
    required this.filename,
  });

  final Uint8List bytes;
  final String filename;
}

class DataExportDocument {
  const DataExportDocument({
    required this.bytes,
    required this.filename,
    required this.mimeType,
  });

  final Uint8List bytes;
  final String filename;
  final String mimeType;
}
