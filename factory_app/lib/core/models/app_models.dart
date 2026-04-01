import 'package:flutter/material.dart';

enum AppRole {
  customer,
  waiter,
  cashier,
  kitchen,
  owner,
}

extension AppRoleX on AppRole {
  String get label {
    switch (this) {
      case AppRole.customer:
        return 'Customer';
      case AppRole.waiter:
        return 'Waiter';
      case AppRole.cashier:
        return 'Cashier';
      case AppRole.kitchen:
        return 'Kitchen';
      case AppRole.owner:
        return 'Owner';
    }
  }

  String get apiType {
    switch (this) {
      case AppRole.customer:
        return 'customer';
      case AppRole.waiter:
        return 'waiter';
      case AppRole.cashier:
        return 'cashier';
      case AppRole.kitchen:
        return 'kitchen';
      case AppRole.owner:
        return 'owner';
    }
  }

  IconData get icon {
    switch (this) {
      case AppRole.customer:
        return Icons.storefront_outlined;
      case AppRole.waiter:
        return Icons.table_restaurant_outlined;
      case AppRole.cashier:
        return Icons.point_of_sale_outlined;
      case AppRole.kitchen:
        return Icons.soup_kitchen_outlined;
      case AppRole.owner:
        return Icons.bar_chart_outlined;
    }
  }

  static AppRole? tryParse(String raw) {
    switch (raw.toLowerCase()) {
      case 'customer':
        return AppRole.customer;
      case 'waiter':
        return AppRole.waiter;
      case 'cashier':
        return AppRole.cashier;
      case 'kitchen':
        return AppRole.kitchen;
      case 'owner':
      case 'stakeholder':
      case 'shareholder':
        return AppRole.owner;
      default:
        return null;
    }
  }
}

class AppSession {
  const AppSession({
    required this.id,
    required this.name,
    required this.token,
    required this.roles,
    required this.activeRole,
    this.email,
    this.phone,
    this.branchId,
    this.restaurantId,
    this.loyaltyPoints = 0,
  });

  final int id;
  final String name;
  final String token;
  final List<AppRole> roles;
  final AppRole activeRole;
  final String? email;
  final String? phone;
  final int? branchId;
  final int? restaurantId;
  final int loyaltyPoints;

  bool get isCustomer => activeRole == AppRole.customer;

  AppSession copyWith({
    int? id,
    String? name,
    String? token,
    List<AppRole>? roles,
    AppRole? activeRole,
    String? email,
    String? phone,
    int? branchId,
    int? restaurantId,
    int? loyaltyPoints,
  }) {
    return AppSession(
      id: id ?? this.id,
      name: name ?? this.name,
      token: token ?? this.token,
      roles: roles ?? this.roles,
      activeRole: activeRole ?? this.activeRole,
      email: email ?? this.email,
      phone: phone ?? this.phone,
      branchId: branchId ?? this.branchId,
      restaurantId: restaurantId ?? this.restaurantId,
      loyaltyPoints: loyaltyPoints ?? this.loyaltyPoints,
    );
  }

  factory AppSession.fromJson(Map<String, dynamic> json) {
    final roles = (json['roles'] as List<dynamic>? ?? const [])
        .map((item) => AppRoleX.tryParse(item.toString()))
        .whereType<AppRole>()
        .toList(growable: false);

    final activeRole =
        AppRoleX.tryParse(json['active_role']?.toString() ?? '') ??
            (roles.isNotEmpty ? roles.first : AppRole.owner);

    return AppSession(
      id: jsonInt(json['id']),
      name: jsonString(json['name'], fallback: 'User'),
      email: jsonNullableString(json['email']),
      phone: jsonNullableString(json['phone']),
      token: jsonString(json['token']),
      roles: roles.isEmpty ? <AppRole>[activeRole] : roles,
      activeRole: activeRole,
      branchId: jsonNullableInt(json['branch_id']),
      restaurantId: jsonNullableInt(json['restaurant_id']),
      loyaltyPoints: jsonInt(json['loyalty_points']),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'phone': phone,
        'token': token,
        'roles': roles.map((role) => role.apiType).toList(growable: false),
        'active_role': activeRole.apiType,
        'branch_id': branchId,
        'restaurant_id': restaurantId,
        'loyalty_points': loyaltyPoints,
      };
}

class PaginationMeta {
  const PaginationMeta({
    required this.currentPage,
    required this.lastPage,
    required this.perPage,
    required this.total,
  });

  final int currentPage;
  final int lastPage;
  final int perPage;
  final int total;

  bool get hasMore => currentPage < lastPage;

  factory PaginationMeta.fromJson(Map<String, dynamic> json) {
    return PaginationMeta(
      currentPage: jsonInt(json['current_page'], fallback: 1),
      lastPage: jsonInt(json['last_page'], fallback: 1),
      perPage: jsonInt(json['per_page'], fallback: 10),
      total: jsonInt(json['total']),
    );
  }
}

class PagedResponse<T> {
  const PagedResponse({
    required this.items,
    required this.meta,
  });

  final List<T> items;
  final PaginationMeta meta;

  factory PagedResponse.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>) fromJson,
  ) {
    return PagedResponse<T>(
      items: jsonMapList(json['data']).map(fromJson).toList(growable: false),
      meta: PaginationMeta.fromJson(jsonMap(json['meta'])),
    );
  }
}

class BranchInfo {
  const BranchInfo({
    required this.id,
    required this.name,
    this.location,
  });

  final int id;
  final String name;
  final String? location;

  factory BranchInfo.fromJson(Map<String, dynamic> json) {
    return BranchInfo(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      location: jsonNullableString(json['location']),
    );
  }
}

class RestaurantListing {
  const RestaurantListing({
    required this.id,
    required this.name,
    required this.branchCount,
    required this.branches,
  });

  final int id;
  final String name;
  final int branchCount;
  final List<BranchInfo> branches;

  factory RestaurantListing.fromJson(Map<String, dynamic> json) {
    return RestaurantListing(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      branchCount: jsonInt(json['branch_count']),
      branches: jsonMapList(json['branches'])
          .map(BranchInfo.fromJson)
          .toList(growable: false),
    );
  }
}

class PaymentRecord {
  const PaymentRecord({
    required this.id,
    required this.method,
    required this.amount,
  });

  final int id;
  final String method;
  final double amount;

  factory PaymentRecord.fromJson(Map<String, dynamic> json) {
    return PaymentRecord(
      id: jsonInt(json['id']),
      method: jsonString(json['method']),
      amount: jsonDouble(json['amount']),
    );
  }
}

class OrderItemLine {
  const OrderItemLine({
    required this.id,
    required this.name,
    required this.quantity,
    required this.total,
    this.price = 0,
    this.status,
    this.kdsStatus,
    this.itemNote,
    this.changeNote,
    this.modifiers = const [],
  });

  final int id;
  final String name;
  final int quantity;
  final double total;
  final double price;
  final String? status;
  final String? kdsStatus;
  final String? itemNote;
  final String? changeNote;
  final List<String> modifiers;

  factory OrderItemLine.fromJson(Map<String, dynamic> json) {
    final product = jsonMap(json['product']);
    return OrderItemLine(
      id: jsonInt(json['id']),
      name: jsonString(product['name'] ?? json['name'], fallback: 'Item'),
      quantity: jsonInt(json['quantity'], fallback: 1),
      total: jsonDouble(json['total']),
      price: jsonDouble(json['price']),
      status: jsonNullableString(json['status']),
      kdsStatus: jsonNullableString(json['kds_status']),
      itemNote: jsonNullableString(json['item_note'] ?? json['note']),
      changeNote: jsonNullableString(json['change_note']),
      modifiers: jsonMapList(json['modifiers'])
          .map((modifier) {
            final nested = jsonMap(modifier['modifier']);
            return jsonString(
              nested['name'] ?? modifier['raw_modifier'] ?? modifier['name'],
              fallback: '',
            );
          })
          .where((name) => name.isNotEmpty)
          .toList(growable: false),
    );
  }
}

class StaffOrderSnapshot {
  const StaffOrderSnapshot({
    required this.id,
    required this.total,
    required this.status,
    required this.paymentStatus,
    required this.orderType,
    required this.items,
    required this.payments,
    this.tableName,
    this.customerName,
    this.customerPhone,
    this.branchName,
    this.restaurantName,
  });

  final int id;
  final double total;
  final String status;
  final String paymentStatus;
  final String orderType;
  final List<OrderItemLine> items;
  final List<PaymentRecord> payments;
  final String? tableName;
  final String? customerName;
  final String? customerPhone;
  final String? branchName;
  final String? restaurantName;

  double get paidAmount =>
      payments.fold(0, (sum, payment) => sum + payment.amount);
  double get outstandingAmount =>
      total - paidAmount > 0 ? total - paidAmount : 0;

  factory StaffOrderSnapshot.fromJson(Map<String, dynamic> json) {
    return StaffOrderSnapshot(
      id: jsonInt(json['id']),
      total: jsonDouble(json['total']),
      status: jsonString(json['status'], fallback: 'pending'),
      paymentStatus: jsonString(json['payment_status'], fallback: 'unpaid'),
      orderType: jsonString(json['order_type'], fallback: 'dine-in'),
      items: jsonMapList(json['items'])
          .map(OrderItemLine.fromJson)
          .toList(growable: false),
      payments: jsonMapList(json['payments'])
          .map(PaymentRecord.fromJson)
          .toList(growable: false),
      tableName: jsonNullableString(jsonMap(json['table'])['name']),
      customerName: jsonNullableString(jsonMap(json['customer'])['name']),
      customerPhone: jsonNullableString(jsonMap(json['customer'])['phone']),
      branchName: jsonNullableString(
          jsonMap(json['branch'])['name'] ?? json['branch_name']),
      restaurantName: jsonNullableString(
          jsonMap(jsonMap(json['branch'])['restaurant'])['name'] ??
              json['restaurant_name']),
    );
  }
}

class CustomerOrder {
  const CustomerOrder({
    required this.id,
    required this.total,
    required this.status,
    required this.paymentStatus,
    required this.orderType,
    required this.createdAt,
    required this.items,
    required this.payments,
    this.branchName,
    this.branchLocation,
    this.restaurantName,
    this.paymentMethod,
  });

  final int id;
  final double total;
  final String status;
  final String paymentStatus;
  final String orderType;
  final DateTime? createdAt;
  final List<OrderItemLine> items;
  final List<PaymentRecord> payments;
  final String? branchName;
  final String? branchLocation;
  final String? restaurantName;
  final String? paymentMethod;

  factory CustomerOrder.fromJson(Map<String, dynamic> json) {
    return CustomerOrder(
      id: jsonInt(json['id']),
      total: jsonDouble(json['total']),
      status: jsonString(json['status']),
      paymentStatus: jsonString(json['payment_status'], fallback: 'unpaid'),
      orderType: jsonString(json['order_type'], fallback: 'dine-in'),
      createdAt: jsonDate(json['created_at']),
      items: jsonMapList(json['items'])
          .map(OrderItemLine.fromJson)
          .toList(growable: false),
      payments: jsonMapList(json['payments'])
          .map(PaymentRecord.fromJson)
          .toList(growable: false),
      branchName: jsonNullableString(json['branch_name']),
      branchLocation: jsonNullableString(json['branch_location']),
      restaurantName: jsonNullableString(json['restaurant_name']),
      paymentMethod: jsonNullableString(json['payment_method']),
    );
  }
}

class LoyaltyEntry {
  const LoyaltyEntry({
    required this.id,
    required this.type,
    required this.points,
    this.createdAt,
    this.orderId,
    this.restaurantName,
    this.branchName,
  });

  final int id;
  final String type;
  final int points;
  final DateTime? createdAt;
  final int? orderId;
  final String? restaurantName;
  final String? branchName;

  factory LoyaltyEntry.fromJson(Map<String, dynamic> json) {
    return LoyaltyEntry(
      id: jsonInt(json['id']),
      type: jsonString(json['type']),
      points: jsonInt(json['points']),
      createdAt: jsonDate(json['created_at']),
      orderId: jsonNullableInt(json['order_id']),
      restaurantName: jsonNullableString(json['restaurant_name']),
      branchName: jsonNullableString(json['branch_name']),
    );
  }
}

class CustomerHomeData {
  const CustomerHomeData({
    required this.name,
    required this.loyaltyPoints,
    required this.restaurants,
    required this.recentOrders,
    required this.loyaltyPreview,
  });

  final String name;
  final int loyaltyPoints;
  final List<RestaurantListing> restaurants;
  final List<CustomerOrder> recentOrders;
  final List<LoyaltyEntry> loyaltyPreview;

  factory CustomerHomeData.fromJson(Map<String, dynamic> json) {
    final customer = jsonMap(json['customer']);
    return CustomerHomeData(
      name: jsonString(customer['name'], fallback: 'Guest'),
      loyaltyPoints: jsonInt(customer['loyalty_points']),
      restaurants: jsonMapList(json['restaurants'])
          .map(RestaurantListing.fromJson)
          .toList(growable: false),
      recentOrders: jsonMapList(json['recent_orders'])
          .map(CustomerOrder.fromJson)
          .toList(growable: false),
      loyaltyPreview: jsonMapList(json['loyalty_preview'])
          .map(LoyaltyEntry.fromJson)
          .toList(growable: false),
    );
  }
}

class CategoryChoiceData {
  const CategoryChoiceData({
    required this.id,
    required this.label,
  });

  final int id;
  final String label;

  factory CategoryChoiceData.fromJson(Map<String, dynamic> json) {
    return CategoryChoiceData(
      id: jsonInt(json['id']),
      label: jsonString(json['choice']),
    );
  }
}

class CategoryQuestionData {
  const CategoryQuestionData({
    required this.id,
    required this.question,
    required this.choices,
  });

  final int id;
  final String question;
  final List<CategoryChoiceData> choices;

  factory CategoryQuestionData.fromJson(Map<String, dynamic> json) {
    return CategoryQuestionData(
      id: jsonInt(json['id']),
      question: jsonString(json['question']),
      choices: jsonMapList(json['choices'])
          .map(CategoryChoiceData.fromJson)
          .toList(growable: false),
    );
  }
}

class MenuProduct {
  const MenuProduct({
    required this.id,
    required this.name,
    required this.price,
    this.imageUrl,
  });

  final int id;
  final String name;
  final double price;
  final String? imageUrl;

  factory MenuProduct.fromJson(Map<String, dynamic> json) {
    return MenuProduct(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      price: jsonDouble(json['price']),
      imageUrl: jsonNullableString(json['image']),
    );
  }
}

class MenuCategoryData {
  const MenuCategoryData({
    required this.id,
    required this.name,
    required this.products,
    required this.questions,
  });

  final int id;
  final String name;
  final List<MenuProduct> products;
  final List<CategoryQuestionData> questions;

  factory MenuCategoryData.fromJson(Map<String, dynamic> json) {
    return MenuCategoryData(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      products: jsonMapList(json['products'])
          .map(MenuProduct.fromJson)
          .toList(growable: false),
      questions: jsonMapList(json['questions'])
          .map(CategoryQuestionData.fromJson)
          .toList(growable: false),
    );
  }
}

class ModifierData {
  const ModifierData({
    required this.id,
    required this.name,
    required this.price,
  });

  final int id;
  final String name;
  final double price;

  factory ModifierData.fromJson(Map<String, dynamic> json) {
    return ModifierData(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      price: jsonDouble(json['price']),
    );
  }
}

class TableOverview {
  const TableOverview({
    required this.id,
    required this.name,
    required this.seats,
    required this.status,
    this.orderId,
    this.orderTotal = 0,
    this.itemCount = 0,
    this.customerName,
  });

  final int id;
  final String name;
  final int seats;
  final String status;
  final int? orderId;
  final double orderTotal;
  final int itemCount;
  final String? customerName;

  bool get isOccupied => orderId != null;

  factory TableOverview.fromJson(Map<String, dynamic> json) {
    final orders = jsonMapList(json['orders']);
    final order = orders.isNotEmpty ? orders.first : <String, dynamic>{};
    final items = jsonMapList(order['items']);
    final count = items.fold<int>(
        0, (sum, item) => sum + jsonInt(item['quantity'], fallback: 1));

    return TableOverview(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      seats: jsonInt(json['seats']),
      status: jsonString(json['status'],
          fallback: order.isEmpty ? 'open' : 'occupied'),
      orderId: order.isEmpty ? null : jsonInt(order['id']),
      orderTotal: jsonDouble(order['total']),
      itemCount: count,
      customerName: jsonNullableString(jsonMap(order['customer'])['name']),
    );
  }
}

class TableDetails {
  const TableDetails({
    required this.id,
    required this.name,
    required this.status,
    required this.items,
    this.orderId,
    this.orderTotal = 0,
    this.customerName,
    this.customerPhone,
  });

  final int id;
  final String name;
  final String status;
  final int? orderId;
  final double orderTotal;
  final List<OrderItemLine> items;
  final String? customerName;
  final String? customerPhone;

  factory TableDetails.fromJson(Map<String, dynamic> json) {
    final orders = jsonMapList(json['orders']);
    final order = orders.isNotEmpty ? orders.first : <String, dynamic>{};
    return TableDetails(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      status: jsonString(json['status'],
          fallback: order.isEmpty ? 'open' : 'occupied'),
      orderId: order.isEmpty ? null : jsonInt(order['id']),
      orderTotal: jsonDouble(order['total']),
      items: jsonMapList(order['items'])
          .map(OrderItemLine.fromJson)
          .toList(growable: false),
      customerName: jsonNullableString(jsonMap(order['customer'])['name']),
      customerPhone: jsonNullableString(jsonMap(order['customer'])['phone']),
    );
  }
}

class KdsTicket {
  const KdsTicket({
    required this.id,
    required this.tableName,
    required this.items,
    this.waiter,
  });

  final int id;
  final String tableName;
  final List<OrderItemLine> items;
  final String? waiter;

  factory KdsTicket.fromJson(Map<String, dynamic> json) {
    return KdsTicket(
      id: jsonInt(json['id']),
      tableName: jsonString(json['table_name'], fallback: 'Table'),
      waiter: jsonNullableString(json['waiter']),
      items: jsonMapList(json['items'])
          .map(OrderItemLine.fromJson)
          .toList(growable: false),
    );
  }
}

class PaymentMixEntry {
  const PaymentMixEntry({
    required this.method,
    required this.total,
  });

  final String method;
  final double total;

  factory PaymentMixEntry.fromJson(Map<String, dynamic> json) {
    return PaymentMixEntry(
      method: jsonString(json['method']),
      total: jsonDouble(json['total']),
    );
  }
}

class BranchPerformance {
  const BranchPerformance({
    required this.id,
    required this.name,
    required this.sales,
    required this.ordersCount,
    this.location,
  });

  final int id;
  final String name;
  final double sales;
  final int ordersCount;
  final String? location;

  factory BranchPerformance.fromJson(Map<String, dynamic> json) {
    return BranchPerformance(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      sales: jsonDouble(json['sales']),
      ordersCount: jsonInt(json['orders_count']),
      location: jsonNullableString(json['location']),
    );
  }
}

class OwnerSummary {
  const OwnerSummary({
    required this.totalSales,
    required this.ordersCount,
    required this.avgOrderValue,
    required this.productCount,
    required this.employeeCount,
    required this.activeTables,
    required this.cashierQueue,
    required this.kdsBacklog,
    required this.loyaltyMembers,
    required this.paymentMix,
    required this.branchPerformance,
    required this.topProducts,
    required this.lowStockItems,
    required this.recentOrders,
  });

  final double totalSales;
  final int ordersCount;
  final double avgOrderValue;
  final int productCount;
  final int employeeCount;
  final int activeTables;
  final int cashierQueue;
  final int kdsBacklog;
  final int loyaltyMembers;
  final List<PaymentMixEntry> paymentMix;
  final List<BranchPerformance> branchPerformance;
  final List<Map<String, dynamic>> topProducts;
  final List<Map<String, dynamic>> lowStockItems;
  final List<Map<String, dynamic>> recentOrders;

  factory OwnerSummary.fromJson(Map<String, dynamic> json) {
    return OwnerSummary(
      totalSales: jsonDouble(json['total_sales']),
      ordersCount: jsonInt(json['orders_count']),
      avgOrderValue: jsonDouble(json['avg_order_value']),
      productCount: jsonInt(json['product_count']),
      employeeCount: jsonInt(json['employee_count']),
      activeTables: jsonInt(json['active_tables']),
      cashierQueue: jsonInt(json['cashier_queue']),
      kdsBacklog: jsonInt(json['kds_backlog']),
      loyaltyMembers: jsonInt(json['loyalty_members']),
      paymentMix: jsonMapList(json['payment_mix'])
          .map(PaymentMixEntry.fromJson)
          .toList(growable: false),
      branchPerformance: jsonMapList(json['branch_performance'])
          .map(BranchPerformance.fromJson)
          .toList(growable: false),
      topProducts: jsonMapList(json['top_products']),
      lowStockItems: jsonMapList(json['low_stock_items']),
      recentOrders: jsonMapList(json['recent_orders']),
    );
  }
}

int jsonInt(dynamic value, {int fallback = 0}) {
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value) ?? fallback;
  return fallback;
}

int? jsonNullableInt(dynamic value) {
  if (value == null) return null;
  return jsonInt(value);
}

double jsonDouble(dynamic value, {double fallback = 0}) {
  if (value is double) return value;
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value) ?? fallback;
  return fallback;
}

String jsonString(dynamic value, {String fallback = ''}) {
  if (value == null) return fallback;
  final stringValue = value.toString();
  return stringValue.isEmpty ? fallback : stringValue;
}

String? jsonNullableString(dynamic value) {
  if (value == null) return null;
  final stringValue = value.toString();
  return stringValue.isEmpty ? null : stringValue;
}

DateTime? jsonDate(dynamic value) {
  final text = jsonNullableString(value);
  if (text == null) return null;
  return DateTime.tryParse(text);
}

Map<String, dynamic> jsonMap(dynamic value) {
  if (value is Map<String, dynamic>) return value;
  if (value is Map) return Map<String, dynamic>.from(value);
  return const <String, dynamic>{};
}

List<Map<String, dynamic>> jsonMapList(dynamic value) {
  if (value is! List) return const <Map<String, dynamic>>[];
  return value.map((item) => jsonMap(item)).toList(growable: false);
}
