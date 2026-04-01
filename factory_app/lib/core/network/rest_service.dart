import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../mock/module_records.dart';
import 'api_client.dart';

class ModuleDataResult {
  ModuleDataResult({required this.records, this.error});
  final List<Map<String, dynamic>> records;
  final String? error;
}

class RestService {
  RestService(this._dio);
  final Dio _dio;

  Future<ModuleDataResult> fetch(
    String endpoint, {
    Map<String, dynamic>? query,
    required String moduleId,
  }) async {
    final path = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;
    try {
      final response = await _dio.get(path, queryParameters: query);
      final data = response.data;
      if (data is List) {
        return ModuleDataResult(records: data.cast<Map<String, dynamic>>());
      }
      if (data is Map && data['data'] is List) {
        return ModuleDataResult(records: (data['data'] as List).cast<Map<String, dynamic>>());
      }
      return ModuleDataResult(records: ModuleRecords.fallback(moduleId), error: 'Unexpected response shape');
    } catch (e) {
      return ModuleDataResult(records: ModuleRecords.fallback(moduleId), error: e.toString());
    }
  }
}

final restServiceProvider = Provider<RestService>((ref) {
  final dio = ref.watch(dioProvider);
  return RestService(dio);
});
