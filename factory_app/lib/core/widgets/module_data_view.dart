import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../network/rest_service.dart';
import '../widgets/filter_bar.dart';
import '../widgets/records_list.dart';
import '../widgets/section_card.dart';
import '../widgets/status_pie.dart';

class ModuleDataView extends ConsumerStatefulWidget {
  const ModuleDataView({
    super.key,
    required this.moduleId,
    required this.endpoint,
    this.statusOptions = const [],
    this.title = 'Data',
  });

  final String moduleId;
  final String endpoint;
  final List<String> statusOptions;
  final String title;

  @override
  ConsumerState<ModuleDataView> createState() => _ModuleDataViewState();
}

class _ModuleDataViewState extends ConsumerState<ModuleDataView> {
  FilterState _filters = FilterState();
  late Future<ModuleDataResult> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<ModuleDataResult> _load() {
    final service = ref.read(restServiceProvider);
    final query = <String, dynamic>{};
    if (_filters.search.isNotEmpty) query['q'] = _filters.search;
    if (_filters.status != null) query['status'] = _filters.status;
    if (_filters.from != null) query['from'] = _filters.from!.toIso8601String();
    if (_filters.to != null) query['to'] = _filters.to!.toIso8601String();
    return service.fetch(widget.endpoint, query: query, moduleId: widget.moduleId);
  }

  void _onFiltersChanged(FilterState next) {
    setState(() {
      _filters = next;
      _future = _load();
    });
  }

  Map<String, int> _statusCounts(List<Map<String, dynamic>> records) {
    final counts = <String, int>{};
    for (final r in records) {
      final key = (r['status'] ?? 'Unknown').toString();
      counts.update(key, (value) => value + 1, ifAbsent: () => 1);
    }
    return counts;
  }

  @override
  Widget build(BuildContext context) {
    return SectionCard(
      title: widget.title,
      trailing: IconButton(
        onPressed: () => setState(() => _future = _load()),
        icon: const Icon(Icons.refresh),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          FilterBar(state: _filters, onChanged: _onFiltersChanged, statusOptions: widget.statusOptions),
          const SizedBox(height: 12),
          FutureBuilder<ModuleDataResult>(
            future: _future,
            builder: (context, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const Padding(
                  padding: EdgeInsets.all(16),
                  child: Center(child: CircularProgressIndicator()),
                );
              }
              final result = snapshot.data;
              if (result == null) {
                return const Text('No data');
              }
              final counts = _statusCounts(result.records);
              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  StatusPie(counts: counts),
                  if (result.error != null)
                    Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: Text('Showing cached data: ${result.error}', style: TextStyle(color: Theme.of(context).colorScheme.error)),
                    ),
                  RecordsList(records: result.records),
                ],
              );
            },
          ),
        ],
      ),
    );
  }
}
