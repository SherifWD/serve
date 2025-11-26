import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../features/auth/providers/auth_providers.dart';

final dioProvider = Provider<Dio>((ref) {
  final authState = ref.watch(authProvider);
  final dio = Dio(
    BaseOptions(
      baseUrl: 'https://api.factory.local',
      connectTimeout: const Duration(seconds: 10),
    ),
  );

  dio.interceptors.add(InterceptorsWrapper(
    onRequest: (options, handler) {
      final token = authState.user?.token;
      if (token != null) {
        options.headers['Authorization'] = 'Bearer $token';
      }
      return handler.next(options);
    },
  ));

  return dio;
});
