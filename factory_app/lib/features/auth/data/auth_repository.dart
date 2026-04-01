import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../../../core/models/app_models.dart';

class AuthRepository {
  AuthRepository(this._dio, this._storage, {required this.sessionKey});

  final Dio _dio;
  final FlutterSecureStorage _storage;
  final String sessionKey;

  Future<AppSession?> restoreSession() async {
    final raw = await _storage.read(key: sessionKey);
    if (raw == null || raw.isEmpty) return null;
    return AppSession.fromJson(jsonDecode(raw) as Map<String, dynamic>);
  }

  Future<AppSession> loginStaff({
    required String email,
    required String password,
    required AppRole requestedRole,
  }) async {
    final response = await _dio.post(
      '/login',
      data: {
        'email': email,
        'password': password,
        'type': requestedRole.apiType,
      },
    );

    if ((response.statusCode ?? 500) >= 400) {
      throw Exception(_errorMessage(response.data));
    }

    final payload = _map(response.data);
    final user = _map(payload['user']);
    final availableRoles = (user['types'] as List<dynamic>? ?? const [])
        .map((type) => AppRoleX.tryParse(type.toString()))
        .whereType<AppRole>()
        .toList(growable: false);

    final session = AppSession(
      id: jsonInt(user['id']),
      name: jsonString(user['name'], fallback: 'Team Member'),
      email: jsonNullableString(user['email']),
      token: jsonString(payload['token']),
      roles: availableRoles.isEmpty ? <AppRole>[requestedRole] : availableRoles,
      activeRole: availableRoles.contains(requestedRole)
          ? requestedRole
          : (availableRoles.isNotEmpty ? availableRoles.first : requestedRole),
      branchId: jsonNullableInt(user['branch_id']),
      restaurantId: jsonNullableInt(user['restaurant_id']),
    );

    await _persist(session);
    return session;
  }

  Future<AppSession> loginCustomer({
    required String name,
    required String phone,
    String? email,
  }) async {
    final response = await _dio.post(
      '/customer/auth/login',
      data: {
        'name': name,
        'phone': phone,
        if (email != null && email.isNotEmpty) 'email': email,
      },
    );

    if ((response.statusCode ?? 500) >= 400) {
      throw Exception(_errorMessage(response.data));
    }

    final payload = _map(response.data);
    final customer = _map(payload['customer']);
    final session = AppSession(
      id: jsonInt(customer['id']),
      name: jsonString(customer['name'], fallback: 'Customer'),
      email: jsonNullableString(customer['email']),
      phone: jsonNullableString(customer['phone']),
      token: jsonString(payload['token']),
      roles: const [AppRole.customer],
      activeRole: AppRole.customer,
      loyaltyPoints: jsonInt(customer['loyalty_points']),
    );

    await _persist(session);
    return session;
  }

  Future<AppSession> switchRole(AppSession session, AppRole role) async {
    final updated = session.copyWith(activeRole: role);
    await _persist(updated);
    return updated;
  }

  Future<void> logout(AppSession session) async {
    try {
      await _dio.post(
        session.isCustomer ? '/customer/auth/logout' : '/logout',
        options: Options(
          headers: {'Authorization': 'Bearer ${session.token}'},
        ),
      );
    } catch (_) {
      // Clear local session regardless of remote logout result.
    }

    await _storage.delete(key: sessionKey);
  }

  Future<void> _persist(AppSession session) {
    return _storage.write(key: sessionKey, value: jsonEncode(session.toJson()));
  }

  Map<String, dynamic> _map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return const <String, dynamic>{};
  }

  String _errorMessage(dynamic payload) {
    final data = _map(payload);
    return data['message']?.toString() ??
        data['error']?.toString() ??
        'Authentication failed';
  }
}
