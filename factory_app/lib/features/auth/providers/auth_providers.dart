import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../../../core/models/app_models.dart';
import '../../../core/config/app_flavor.dart';
import '../../../core/network/api_client.dart';
import '../data/auth_repository.dart';

final secureStorageProvider = Provider<FlutterSecureStorage>(
  (ref) => const FlutterSecureStorage(),
);

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(
    ref.watch(rawDioProvider),
    ref.watch(secureStorageProvider),
    sessionKey:
        'restaurant_suite_session_${ref.watch(appFlavorProvider).sessionKeySuffix}',
  ),
);

class AuthState {
  const AuthState({
    this.session,
    this.error,
    this.isLoading = false,
    this.hasBootstrapped = false,
  });

  final AppSession? session;
  final String? error;
  final bool isLoading;
  final bool hasBootstrapped;

  AuthState copyWith({
    AppSession? session,
    String? error,
    bool? isLoading,
    bool? hasBootstrapped,
  }) {
    return AuthState(
      session: session ?? this.session,
      error: error,
      isLoading: isLoading ?? this.isLoading,
      hasBootstrapped: hasBootstrapped ?? this.hasBootstrapped,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  AuthNotifier(this._repository, this._fixedRole)
      : super(const AuthState(isLoading: true)) {
    _restore();
  }

  final AuthRepository _repository;
  final AppRole? _fixedRole;

  Future<void> _restore() async {
    final restored = await _repository.restoreSession();
    final session = await _enforceFixedRole(restored);
    state = AuthState(
      session: session,
      hasBootstrapped: true,
      isLoading: false,
    );
  }

  Future<void> loginStaff({
    required String email,
    required String password,
    required AppRole role,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final session = await _repository.loginStaff(
        email: email,
        password: password,
        requestedRole: _fixedRole ?? role,
      );
      final normalized = await _enforceFixedRole(session);
      state = state.copyWith(
        session: normalized,
        isLoading: false,
        hasBootstrapped: true,
      );
    } catch (e) {
      state = state.copyWith(
        error: e.toString(),
        isLoading: false,
        hasBootstrapped: true,
      );
    }
  }

  Future<void> loginCustomer({
    required String name,
    required String phone,
    String? email,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final session = await _repository.loginCustomer(
        name: name,
        phone: phone,
        email: email,
      );
      state = state.copyWith(
        session: session,
        isLoading: false,
        hasBootstrapped: true,
      );
    } catch (e) {
      state = state.copyWith(
        error: e.toString(),
        isLoading: false,
        hasBootstrapped: true,
      );
    }
  }

  Future<void> switchRole(AppRole role) async {
    if (_fixedRole != null) return;
    final session = state.session;
    if (session == null) return;
    final updated = await _repository.switchRole(session, role);
    state = state.copyWith(session: updated);
  }

  Future<void> logout() async {
    final session = state.session;
    if (session != null) {
      await _repository.logout(session);
    }
    state = const AuthState(hasBootstrapped: true);
  }

  Future<AppSession?> _enforceFixedRole(AppSession? session) async {
    if (session == null || _fixedRole == null) {
      return session;
    }

    if (!session.roles.contains(_fixedRole)) {
      await _repository.logout(session);
      return null;
    }

    if (session.activeRole == _fixedRole) {
      return session;
    }

    return _repository.switchRole(session, _fixedRole);
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(
    ref.watch(authRepositoryProvider),
    ref.watch(fixedRoleProvider),
  );
});

final currentSessionProvider =
    Provider<AppSession?>((ref) => ref.watch(authProvider).session);

final availableRolesProvider = Provider<List<AppRole>>(
  (ref) => ref.watch(currentSessionProvider)?.roles ?? const <AppRole>[],
);

final currentRoleProvider =
    Provider<AppRole?>((ref) => ref.watch(currentSessionProvider)?.activeRole);

final isAuthenticatedProvider =
    Provider<bool>((ref) => ref.watch(currentSessionProvider) != null);
