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
    this.kind = 'restaurant',
    this.coverImageUrl,
    this.featuredItems = const [],
  });

  final int id;
  final String name;
  final int branchCount;
  final List<BranchInfo> branches;
  final String kind;
  final String? coverImageUrl;
  final List<RestaurantFeaturedItem> featuredItems;

  factory RestaurantListing.fromJson(Map<String, dynamic> json) {
    return RestaurantListing(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      branchCount: jsonInt(json['branch_count']),
      kind: jsonString(json['kind'], fallback: 'restaurant'),
      coverImageUrl: jsonNullableString(json['cover_image']),
      featuredItems: jsonMapList(json['featured_items'])
          .map(RestaurantFeaturedItem.fromJson)
          .toList(growable: false),
      branches: jsonMapList(json['branches'])
          .map(BranchInfo.fromJson)
          .toList(growable: false),
    );
  }
}

class RestaurantFeaturedItem {
  const RestaurantFeaturedItem({
    required this.id,
    required this.name,
    required this.price,
    this.imageUrl,
  });

  final int id;
  final String name;
  final double price;
  final String? imageUrl;

  factory RestaurantFeaturedItem.fromJson(Map<String, dynamic> json) {
    return RestaurantFeaturedItem(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      price: jsonDouble(json['price']),
      imageUrl: jsonNullableString(json['image']),
    );
  }
}

class CustomerMenuItem {
  const CustomerMenuItem({
    required this.id,
    required this.name,
    required this.price,
    this.imageUrl,
    this.branchId,
    this.branchName,
    this.branchLocation,
    this.categoryName,
  });

  final int id;
  final String name;
  final double price;
  final String? imageUrl;
  final int? branchId;
  final String? branchName;
  final String? branchLocation;
  final String? categoryName;

  factory CustomerMenuItem.fromJson(Map<String, dynamic> json) {
    return CustomerMenuItem(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      price: jsonDouble(json['price']),
      imageUrl: jsonNullableString(json['image']),
      branchId: jsonNullableInt(json['branch_id']),
      branchName: jsonNullableString(json['branch_name']),
      branchLocation: jsonNullableString(json['branch_location']),
      categoryName: jsonNullableString(json['category_name']),
    );
  }
}

class CustomerRestaurantDetail {
  const CustomerRestaurantDetail({
    required this.restaurant,
    required this.items,
    required this.meta,
  });

  final RestaurantListing restaurant;
  final List<CustomerMenuItem> items;
  final PaginationMeta meta;

  factory CustomerRestaurantDetail.fromJson(Map<String, dynamic> json) {
    return CustomerRestaurantDetail(
      restaurant: RestaurantListing.fromJson(jsonMap(json['restaurant'])),
      items: jsonMapList(json['data'])
          .map(CustomerMenuItem.fromJson)
          .toList(growable: false),
      meta: PaginationMeta.fromJson(jsonMap(json['meta'])),
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
    this.paymentStatus,
    this.paidAmount = 0,
    this.itemNote,
    this.changeNote,
    this.modifiers = const [],
    this.imageUrl,
  });

  final int id;
  final String name;
  final int quantity;
  final double total;
  final double price;
  final String? status;
  final String? kdsStatus;
  final String? paymentStatus;
  final double paidAmount;
  final String? itemNote;
  final String? changeNote;
  final List<String> modifiers;
  final String? imageUrl;

  double get unpaidAmount => total - paidAmount > 0 ? total - paidAmount : 0;
  bool get isPaid => paymentStatus == 'paid';
  bool get isVoided =>
      status == 'refunded' || status == 'canceled' || status == 'cancelled';
  bool get canReduceQuantity => !isVoided && quantity > 1;
  bool get canRefund {
    final kitchenStatus = kdsStatus ?? status;
    return !isVoided && kitchenStatus == 'returned';
  }

  bool get canSendToKitchen {
    final kitchenStatus = kdsStatus ?? status;
    return !isVoided &&
        (kitchenStatus == null ||
            kitchenStatus == 'pending' ||
            kitchenStatus == 'changed');
  }

  bool get canReturnToKitchen {
    final kitchenStatus = kdsStatus ?? status;
    return !isVoided && (kitchenStatus == 'queued' || kitchenStatus == 'served');
  }

  String get kitchenStatusLabel {
    switch (kdsStatus ?? status) {
      case 'queued':
        return 'Queued';
      case 'preparing':
        return 'In kitchen';
      case 'ready':
        return 'Ready';
      case 'served':
        return 'Served';
      case 'returned':
        return 'Returned';
      case 'refunded':
        return 'Refunded';
      case 'canceled':
      case 'cancelled':
        return 'Canceled';
      case 'pending':
      case null:
        return 'Not sent';
      default:
        return jsonString(kdsStatus ?? status);
    }
  }

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
      paymentStatus: jsonNullableString(json['payment_status']),
      paidAmount: jsonDouble(json['paid_amount']),
      itemNote: jsonNullableString(json['item_note'] ?? json['note']),
      changeNote: jsonNullableString(json['change_note']),
      imageUrl: jsonNullableString(product['image'] ?? json['image']),
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
    this.restaurantId,
    this.branchId,
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
  final int? restaurantId;
  final int? branchId;
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
      restaurantId: jsonNullableInt(json['restaurant_id']),
      branchId: jsonNullableInt(json['branch_id']),
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
    this.restaurantId,
    this.branchId,
    this.restaurantName,
    this.branchName,
  });

  final int id;
  final String type;
  final int points;
  final DateTime? createdAt;
  final int? orderId;
  final int? restaurantId;
  final int? branchId;
  final String? restaurantName;
  final String? branchName;

  factory LoyaltyEntry.fromJson(Map<String, dynamic> json) {
    return LoyaltyEntry(
      id: jsonInt(json['id']),
      type: jsonString(json['type']),
      points: jsonInt(json['points']),
      createdAt: jsonDate(json['created_at']),
      orderId: jsonNullableInt(json['order_id']),
      restaurantId: jsonNullableInt(json['restaurant_id']),
      branchId: jsonNullableInt(json['branch_id']),
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
    this.categoryId,
  });

  final int id;
  final String name;
  final double price;
  final int? categoryId;

  bool appliesToCategory(int categoryId) {
    return this.categoryId == null || this.categoryId == categoryId;
  }

  factory ModifierData.fromJson(Map<String, dynamic> json) {
    return ModifierData(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      price: jsonDouble(json['price']),
      categoryId:
          json['category_id'] == null ? null : jsonInt(json['category_id']),
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
    this.orderStatus,
    this.paymentStatus,
    this.serviceStatus = 'available',
    this.orderTotal = 0,
    this.itemCount = 0,
    this.customerName,
  });

  final int id;
  final String name;
  final int seats;
  final String status;
  final String serviceStatus;
  final int? orderId;
  final String? orderStatus;
  final String? paymentStatus;
  final double orderTotal;
  final int itemCount;
  final String? customerName;

  bool get isOccupied => orderId != null && serviceStatus != 'available';
  bool get isAvailable => !isOccupied;

  String get statusLabel {
    switch (serviceStatus) {
      case 'available':
        return 'Available';
      case 'cashier':
        return 'Cashier';
      case 'kitchen':
        return 'In kitchen';
      case 'ready':
        return 'Ready';
      case 'served':
        return 'Served';
      case 'returned':
        return 'Returned';
      default:
        return 'Busy';
    }
  }

  factory TableOverview.fromJson(Map<String, dynamic> json) {
    final orders = jsonMapList(json['orders']);
    final order = orders.isNotEmpty ? orders.first : <String, dynamic>{};
    final items = jsonMapList(order['items']);
    final count = items.fold<int>(
        0, (sum, item) => sum + jsonInt(item['quantity'], fallback: 1));
    final fallbackStatus = order.isEmpty
        ? 'available'
        : jsonString(order['status'], fallback: 'busy');

    return TableOverview(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      seats: jsonInt(json['seats']),
      status: jsonString(json['status'],
          fallback: order.isEmpty ? 'open' : 'occupied'),
      serviceStatus: jsonString(json['service_status'],
          fallback: fallbackStatus == 'cashier' ? 'cashier' : fallbackStatus),
      orderId: order.isEmpty ? null : jsonInt(order['id']),
      orderStatus:
          jsonNullableString(json['active_order_status'] ?? order['status']),
      paymentStatus: jsonNullableString(
          json['active_payment_status'] ?? order['payment_status']),
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
    this.orderStatus,
    this.paymentStatus,
    this.serviceStatus = 'available',
    this.orderTotal = 0,
    this.customerName,
    this.customerPhone,
  });

  final int id;
  final String name;
  final String status;
  final String serviceStatus;
  final int? orderId;
  final String? orderStatus;
  final String? paymentStatus;
  final double orderTotal;
  final List<OrderItemLine> items;
  final String? customerName;
  final String? customerPhone;

  factory TableDetails.fromJson(Map<String, dynamic> json) {
    final orders = jsonMapList(json['orders']);
    final order = orders.isNotEmpty ? orders.first : <String, dynamic>{};
    final fallbackStatus = order.isEmpty
        ? 'available'
        : jsonString(order['status'], fallback: 'busy');
    return TableDetails(
      id: jsonInt(json['id']),
      name: jsonString(json['name']),
      status: jsonString(json['status'],
          fallback: order.isEmpty ? 'open' : 'occupied'),
      serviceStatus: jsonString(json['service_status'],
          fallback: fallbackStatus == 'cashier' ? 'cashier' : fallbackStatus),
      orderId: order.isEmpty ? null : jsonInt(order['id']),
      orderStatus:
          jsonNullableString(json['active_order_status'] ?? order['status']),
      paymentStatus: jsonNullableString(
          json['active_payment_status'] ?? order['payment_status']),
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

class OwnerEmployeeActivity {
  const OwnerEmployeeActivity({
    required this.id,
    required this.name,
    required this.active,
    this.userId,
    this.position,
    this.role,
    this.type,
    this.branchId,
    this.branchName,
    this.activeSource,
    this.checkIn,
    this.shiftStart,
  });

  final int id;
  final int? userId;
  final String name;
  final String? position;
  final String? role;
  final String? type;
  final int? branchId;
  final String? branchName;
  final bool active;
  final String? activeSource;
  final String? checkIn;
  final String? shiftStart;

  String get roleLabel {
    return type ?? position ?? role ?? 'Staff';
  }

  String? get activityLabel {
    if (!active) return null;
    if (activeSource == 'shift') {
      return shiftStart == null ? 'Open shift' : 'Shift $shiftStart';
    }
    return checkIn == null ? 'Checked in' : 'Checked in $checkIn';
  }

  factory OwnerEmployeeActivity.fromJson(Map<String, dynamic> json) {
    return OwnerEmployeeActivity(
      id: jsonInt(json['id']),
      userId: jsonNullableInt(json['user_id']),
      name: jsonString(json['name'], fallback: 'Employee'),
      position: jsonNullableString(json['position']),
      role: jsonNullableString(json['role']),
      type: jsonNullableString(json['type']),
      branchId: jsonNullableInt(json['branch_id']),
      branchName: jsonNullableString(json['branch_name']),
      active: jsonBool(json['active']),
      activeSource: jsonNullableString(json['active_source']),
      checkIn: jsonNullableString(json['check_in']),
      shiftStart: jsonNullableString(json['shift_start']),
    );
  }
}

class OwnerTableSnapshot {
  const OwnerTableSnapshot({
    required this.id,
    required this.name,
    required this.seats,
    required this.status,
  });

  final int id;
  final String name;
  final int seats;
  final String status;

  factory OwnerTableSnapshot.fromJson(Map<String, dynamic> json) {
    return OwnerTableSnapshot(
      id: jsonInt(json['id']),
      name: jsonString(json['name'], fallback: 'Table'),
      seats: jsonInt(json['seats']),
      status: jsonString(json['status'], fallback: 'open'),
    );
  }
}

class OwnerOrderItemDetail {
  const OwnerOrderItemDetail({
    required this.id,
    required this.name,
    required this.quantity,
    required this.total,
    required this.status,
    this.kdsStatus,
    this.refundedQuantity = 0,
  });

  final int id;
  final String name;
  final int quantity;
  final double total;
  final String status;
  final String? kdsStatus;
  final int refundedQuantity;

  factory OwnerOrderItemDetail.fromJson(Map<String, dynamic> json) {
    return OwnerOrderItemDetail(
      id: jsonInt(json['id']),
      name: jsonString(json['name'], fallback: 'Item'),
      quantity: jsonInt(json['quantity']),
      total: jsonDouble(json['total']),
      status: jsonString(json['status'], fallback: 'pending'),
      kdsStatus: jsonNullableString(json['kds_status']),
      refundedQuantity: jsonInt(json['refunded_quantity']),
    );
  }
}

class OwnerOrderDetail {
  const OwnerOrderDetail({
    required this.id,
    required this.total,
    required this.status,
    required this.paymentStatus,
    required this.returnedItemsCount,
    required this.returnedAmount,
    required this.items,
    required this.returnedBy,
    this.branchId,
    this.branchName,
    this.tableId,
    this.tableName,
    this.orderType,
    this.orderDate,
    this.createdAt,
    this.waiterName,
    this.cashierName,
  });

  final int id;
  final int? branchId;
  final String? branchName;
  final int? tableId;
  final String? tableName;
  final String? orderType;
  final String status;
  final String paymentStatus;
  final double total;
  final String? orderDate;
  final String? createdAt;
  final String? waiterName;
  final String? cashierName;
  final List<String> returnedBy;
  final int returnedItemsCount;
  final double returnedAmount;
  final List<OwnerOrderItemDetail> items;

  factory OwnerOrderDetail.fromJson(Map<String, dynamic> json) {
    return OwnerOrderDetail(
      id: jsonInt(json['id']),
      branchId: jsonNullableInt(json['branch_id']),
      branchName: jsonNullableString(json['branch_name']),
      tableId: jsonNullableInt(json['table_id']),
      tableName: jsonNullableString(json['table_name']),
      orderType: jsonNullableString(json['order_type']),
      status: jsonString(json['status'], fallback: 'pending'),
      paymentStatus: jsonString(json['payment_status'], fallback: 'unpaid'),
      total: jsonDouble(json['total']),
      orderDate: jsonNullableString(json['order_date']),
      createdAt: jsonNullableString(json['created_at']),
      waiterName: jsonNullableString(json['waiter_name']),
      cashierName: jsonNullableString(json['cashier_name']),
      returnedBy: jsonStringList(json['returned_by']),
      returnedItemsCount: jsonInt(json['returned_items_count']),
      returnedAmount: jsonDouble(json['returned_amount']),
      items: jsonMapList(json['items'])
          .map(OwnerOrderItemDetail.fromJson)
          .toList(growable: false),
    );
  }
}

class OwnerBranchDetail {
  const OwnerBranchDetail({
    required this.id,
    required this.name,
    required this.sales,
    required this.ordersCount,
    required this.returnedOrdersCount,
    required this.employees,
    required this.activeEmployees,
    required this.kitchenShift,
    required this.tables,
    required this.orders,
    required this.returnedOrders,
    this.location,
  });

  final int id;
  final String name;
  final String? location;
  final double sales;
  final int ordersCount;
  final int returnedOrdersCount;
  final List<OwnerEmployeeActivity> employees;
  final List<OwnerEmployeeActivity> activeEmployees;
  final List<OwnerEmployeeActivity> kitchenShift;
  final List<OwnerTableSnapshot> tables;
  final List<OwnerOrderDetail> orders;
  final List<OwnerOrderDetail> returnedOrders;

  factory OwnerBranchDetail.fromJson(Map<String, dynamic> json) {
    return OwnerBranchDetail(
      id: jsonInt(json['id']),
      name: jsonString(json['name'], fallback: 'Branch'),
      location: jsonNullableString(json['location']),
      sales: jsonDouble(json['sales']),
      ordersCount: jsonInt(json['orders_count']),
      returnedOrdersCount: jsonInt(json['returned_orders_count']),
      employees: jsonMapList(json['employees'])
          .map(OwnerEmployeeActivity.fromJson)
          .toList(growable: false),
      activeEmployees: jsonMapList(json['active_employees'])
          .map(OwnerEmployeeActivity.fromJson)
          .toList(growable: false),
      kitchenShift: jsonMapList(json['kitchen_shift'])
          .map(OwnerEmployeeActivity.fromJson)
          .toList(growable: false),
      tables: jsonMapList(json['tables'])
          .map(OwnerTableSnapshot.fromJson)
          .toList(growable: false),
      orders: jsonMapList(json['orders'])
          .map(OwnerOrderDetail.fromJson)
          .toList(growable: false),
      returnedOrders: jsonMapList(json['returned_orders'])
          .map(OwnerOrderDetail.fromJson)
          .toList(growable: false),
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
    this.branchOptions = const [],
    this.activeEmployees = const [],
    this.branchDetails = const [],
    required this.topProducts,
    required this.lowStockItems,
    required this.recentOrders,
    this.selectedBranchId,
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
  final List<BranchInfo> branchOptions;
  final List<OwnerEmployeeActivity> activeEmployees;
  final List<OwnerBranchDetail> branchDetails;
  final List<Map<String, dynamic>> topProducts;
  final List<Map<String, dynamic>> lowStockItems;
  final List<Map<String, dynamic>> recentOrders;
  final int? selectedBranchId;

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
      branchOptions: jsonMapList(json['branch_options'])
          .map(BranchInfo.fromJson)
          .toList(growable: false),
      activeEmployees: jsonMapList(json['active_employees'])
          .map(OwnerEmployeeActivity.fromJson)
          .toList(growable: false),
      branchDetails: jsonMapList(json['branch_details'])
          .map(OwnerBranchDetail.fromJson)
          .toList(growable: false),
      topProducts: jsonMapList(json['top_products']),
      lowStockItems: jsonMapList(json['low_stock_items']),
      recentOrders: jsonMapList(json['recent_orders']),
      selectedBranchId: jsonNullableInt(json['selected_branch_id']),
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

bool jsonBool(dynamic value, {bool fallback = false}) {
  if (value is bool) return value;
  if (value is num) return value != 0;
  if (value is String) {
    final normalized = value.toLowerCase();
    if (normalized == 'true' || normalized == '1' || normalized == 'yes') {
      return true;
    }
    if (normalized == 'false' || normalized == '0' || normalized == 'no') {
      return false;
    }
  }
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

List<String> jsonStringList(dynamic value) {
  if (value is! List) return const <String>[];
  return value
      .map((item) => item?.toString() ?? '')
      .where((item) => item.isNotEmpty)
      .toList(growable: false);
}
