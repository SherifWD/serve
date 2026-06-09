import 'dart:async';
import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:web_socket_channel/web_socket_channel.dart';

import '../../../core/network/api_client.dart';

final realtimeServiceProvider = Provider<RealtimeService>((ref) {
  return RealtimeService(
    ref.watch(dioProvider),
    ref.watch(apiBaseUrlProvider),
  );
});

class RealtimeService {
  RealtimeService(this._dio, this._apiBaseUrl);

  final Dio _dio;
  final String _apiBaseUrl;

  Future<RealtimeSubscription?> subscribeToBranch({
    required String surface,
    required void Function() onEvent,
  }) async {
    try {
      final response = await _dio.get(
        '/mobile/sync/state',
        queryParameters: {'surface': surface},
      );
      final realtime = _map(_map(response.data)['realtime']);
      if (realtime['enabled'] != true) return null;

      final key = _string(realtime['key']);
      final channelName = _string(realtime['channel']);
      final host = _string(realtime['ws_host'] ?? realtime['host']);
      final events = _stringList(realtime['events']).toSet();
      final forceTls = realtime['force_tls'] == true;
      final port = _int(forceTls ? realtime['wss_port'] : realtime['ws_port']);
      if (key.isEmpty || channelName.isEmpty || host.isEmpty || port == null) {
        return null;
      }

      final socketUri = Uri(
        scheme: forceTls ? 'wss' : 'ws',
        host: host,
        port: port,
        path: '/app/$key',
        queryParameters: const {
          'protocol': '7',
          'client': 'janova-flutter',
          'version': '1.0',
          'flash': 'false',
        },
      );

      final socket = WebSocketChannel.connect(socketUri);
      late final StreamSubscription<dynamic> stream;
      stream = socket.stream.listen(
        (message) {
          _handleSocketMessage(
            socket: socket,
            raw: message,
            channelName: channelName,
            events: events,
            onEvent: onEvent,
          );
        },
        onError: (_) {},
      );

      return RealtimeSubscription._(socket, stream);
    } catch (_) {
      return null;
    }
  }

  Future<void> _handleSocketMessage({
    required WebSocketChannel socket,
    required dynamic raw,
    required String channelName,
    required Set<String> events,
    required void Function() onEvent,
  }) async {
    final payload = _map(jsonDecode(raw.toString()));
    final event = _string(payload['event']);

    if (event == 'pusher:connection_established') {
      final data = _map(jsonDecode(_string(payload['data'])));
      final socketId = _string(data['socket_id']);
      if (socketId.isEmpty) return;

      final auth = await _authorizeChannel(
        socketId: socketId,
        channelName: channelName,
      );
      if (auth == null) return;

      socket.sink.add(jsonEncode({
        'event': 'pusher:subscribe',
        'data': {
          'auth': auth,
          'channel': channelName,
        },
      }));
      return;
    }

    if (events.contains(event)) {
      onEvent();
    }
  }

  Future<String?> _authorizeChannel({
    required String socketId,
    required String channelName,
  }) async {
    final origin = _apiOrigin();
    if (origin == null) return null;

    final response = await _dio.post(
      '$origin/broadcasting/auth',
      data: {
        'socket_id': socketId,
        'channel_name': channelName,
      },
    );
    final auth = _string(_map(response.data)['auth']);
    return auth.isEmpty ? null : auth;
  }

  String? _apiOrigin() {
    final uri = Uri.tryParse(_apiBaseUrl);
    if (uri == null || uri.host.isEmpty) return null;
    return uri.replace(path: '', query: '', fragment: '').toString();
  }

  Map<String, dynamic> _map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return const <String, dynamic>{};
  }

  String _string(dynamic value) => value?.toString() ?? '';

  int? _int(dynamic value) {
    if (value is int) return value;
    return int.tryParse(value?.toString() ?? '');
  }

  List<String> _stringList(dynamic value) {
    if (value is! List) return const [];
    return value.map((item) => item.toString()).toList(growable: false);
  }
}

class RealtimeSubscription {
  RealtimeSubscription._(this._socket, this._stream);

  final WebSocketChannel _socket;
  final StreamSubscription<dynamic> _stream;

  Future<void> close() async {
    await _stream.cancel();
    await _socket.sink.close();
  }
}
