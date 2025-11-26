import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../../../core/models/roles.dart';
import '../data/auth_repository.dart';
import '../domain/user.dart';

final secureStorageProvider = Provider<FlutterSecureStorage>(
  (ref) => const FlutterSecureStorage(),
);

final authRepositoryProvider = Provider<AuthRepository>(
  (ref) => AuthRepository(ref.watch(secureStorageProvider)),
);

class AuthState {
  const AuthState({this.user, this.error, this.isLoading = false});

  final AppUser? user;
  final String? error;
  final bool isLoading;

  AuthState copyWith({AppUser? user, String? error, bool? isLoading}) {
    return AuthState(
      user: user ?? this.user,
      error: error,
      isLoading: isLoading ?? this.isLoading,
    );
  }
}

class AuthNotifier extends StateNotifier<AuthState> {
  AuthNotifier(this._repository) : super(const AuthState());

  final AuthRepository _repository;

  Future<void> login(String email, String password) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final user = await _repository.login(email: email, password: password);
      state = state.copyWith(user: user, isLoading: false);
    } catch (e) {
      state = state.copyWith(error: e.toString(), isLoading: false);
    }
  }

  Future<void> logout() async {
    await _repository.logout();
    state = const AuthState();
  }
}

final authProvider = StateNotifierProvider<AuthNotifier, AuthState>((ref) {
  return AuthNotifier(ref.watch(authRepositoryProvider));
});

final currentUserProvider = Provider<AppUser?>((ref) => ref.watch(authProvider).user);

final currentRoleProvider = Provider<UserRole?>((ref) => ref.watch(currentUserProvider)?.role);

final isAuthenticatedProvider = Provider<bool>((ref) => ref.watch(currentUserProvider) != null);
