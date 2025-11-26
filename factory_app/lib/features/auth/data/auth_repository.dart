import 'dart:async';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../../../core/models/roles.dart';
import '../domain/user.dart';

class AuthRepository {
  AuthRepository(this._storage);

  final FlutterSecureStorage _storage;
  static const _tokenKey = 'auth_token';

  final Map<String, UserRole> _mockDirectory = {
    'owner@factory.com': UserRole.owner,
    'manager@factory.com': UserRole.manager,
    'employee@factory.com': UserRole.employee,
  };

  Future<AppUser> login({required String email, required String password}) async {
    await Future.delayed(const Duration(milliseconds: 700));
    if (!_mockDirectory.containsKey(email)) {
      throw Exception('Invalid credentials');
    }
    final role = _mockDirectory[email]!;
    final token = 'jwt-${role.name}-${DateTime.now().millisecondsSinceEpoch}';
    await _storage.write(key: _tokenKey, value: token);
    return AppUser(
      id: email,
      name: email.split('@').first,
      email: email,
      role: role,
      token: token,
    );
  }

  Future<void> logout() async {
    await _storage.delete(key: _tokenKey);
  }

  Future<String?> getStoredToken() => _storage.read(key: _tokenKey);
}
