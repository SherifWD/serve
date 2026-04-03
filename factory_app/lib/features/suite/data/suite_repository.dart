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
  }) async {
    final response = await _dio.get(
      '/customer/restaurants/$restaurantId',
      queryParameters: {
        'page': page,
        'per_page': perPage,
        'search': search,
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
    final response = await _dio.get('/mobile/tables');
    _throwIfNeeded(response);
    return _list(response.data, 'data')
        .map(TableOverview.fromJson)
        .toList(growable: false);
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

  Future<void> createOrder({
    required int tableId,
    required List<Map<String, dynamic>> items,
    String? customerName,
    String? customerPhone,
    String? customerEmail,
  }) async {
    final response = await _dio.post(
      '/mobile/orders',
      data: {
        'table_id': tableId,
        if (customerName != null && customerName.isNotEmpty)
          'customer_name': customerName,
        if (customerPhone != null && customerPhone.isNotEmpty)
          'customer_phone': customerPhone,
        if (customerEmail != null && customerEmail.isNotEmpty)
          'customer_email': customerEmail,
        'items': items,
      },
    );
    _throwIfNeeded(response);
  }

  Future<void> sendToKds(int orderId) async {
    final response = await _dio.post('/mobile/orders/$orderId/send-to-kds');
    _throwIfNeeded(response);
  }

  Future<void> sendToCashier(int orderId) async {
    final response =
        await _dio.patch('/mobile/orders/$orderId/send-to-cashier');
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
    );
    _throwIfNeeded(response);
  }

  Future<List<KdsTicket>> fetchKitchenBoard() async {
    final response = await _dio.get('/mobile/kds/orders');
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
  }) async {
    final response = await _dio.post(
      '/mobile/orders/$orderId/pay',
      data: {'payments': payments},
    );
    _throwIfNeeded(response);
  }

  Future<OwnerSummary> fetchOwnerSummary({int? branchId}) async {
    final response = await _dio.get(
      '/dashboard/summary',
      queryParameters: {if (branchId != null) 'branch_id': branchId},
    );
    _throwIfNeeded(response);
    return OwnerSummary.fromJson(_map(response.data));
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
