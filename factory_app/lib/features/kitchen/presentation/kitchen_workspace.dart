import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/app_models.dart';
import '../../../core/widgets/state_views.dart';
import '../../suite/data/suite_repository.dart';

class KitchenWorkspacePage extends ConsumerStatefulWidget {
  const KitchenWorkspacePage({super.key});

  @override
  ConsumerState<KitchenWorkspacePage> createState() =>
      _KitchenWorkspacePageState();
}

class _KitchenWorkspacePageState extends ConsumerState<KitchenWorkspacePage> {
  late Future<List<KdsTicket>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(suiteRepositoryProvider).fetchKitchenBoard();
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<KdsTicket>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const LoadingView(label: 'Loading kitchen board...');
        }
        if (snapshot.hasError) {
          return ErrorView(
            message: snapshot.error.toString(),
            onRetry: () => setState(() {
              _future = ref.read(suiteRepositoryProvider).fetchKitchenBoard();
            }),
          );
        }

        final tickets = snapshot.data!;
        final queued = tickets
            .expand((ticket) => ticket.items)
            .where((item) => item.kdsStatus == 'queued')
            .length;
        final preparing = tickets
            .expand((ticket) => ticket.items)
            .where((item) => item.kdsStatus == 'preparing')
            .length;
        final ready = tickets
            .expand((ticket) => ticket.items)
            .where((item) => item.kdsStatus == 'ready')
            .length;

        return RefreshIndicator(
          onRefresh: () async {
            setState(() {
              _future = ref.read(suiteRepositoryProvider).fetchKitchenBoard();
            });
            await _future;
          },
          child: ListView(
            children: [
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  _MetricTile(
                      label: 'Queued',
                      value: '$queued',
                      color: const Color(0xFFF59E0B)),
                  _MetricTile(
                      label: 'Preparing',
                      value: '$preparing',
                      color: const Color(0xFF2563EB)),
                  _MetricTile(
                      label: 'Ready',
                      value: '$ready',
                      color: const Color(0xFF0F766E)),
                ],
              ),
              const SizedBox(height: 16),
              if (tickets.isEmpty)
                const EmptyView(
                  title: 'Kitchen is clear',
                  description:
                      'Orders sent from waiter will show here when they are queued for prep.',
                  icon: Icons.soup_kitchen_outlined,
                )
              else
                for (final ticket in tickets)
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  'Order #${ticket.id} • ${ticket.tableName}',
                                  style: Theme.of(context)
                                      .textTheme
                                      .titleLarge
                                      ?.copyWith(fontWeight: FontWeight.w800),
                                ),
                              ),
                              if (ticket.waiter != null &&
                                  ticket.waiter!.isNotEmpty)
                                Text('Waiter: ${ticket.waiter}'),
                            ],
                          ),
                          const SizedBox(height: 16),
                          for (final item in ticket.items)
                            Padding(
                              padding: const EdgeInsets.only(bottom: 12),
                              child: Container(
                                padding: const EdgeInsets.all(14),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF8FAFC),
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Row(
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            '${item.quantity}x ${item.name}',
                                            style: Theme.of(context)
                                                .textTheme
                                                .titleMedium
                                                ?.copyWith(
                                                    fontWeight:
                                                        FontWeight.w700),
                                          ),
                                          if (item.itemNote != null &&
                                              item.itemNote!.isNotEmpty) ...[
                                            const SizedBox(height: 4),
                                            Text(item.itemNote!),
                                          ],
                                          if (item.modifiers.isNotEmpty) ...[
                                            const SizedBox(height: 6),
                                            Wrap(
                                              spacing: 8,
                                              runSpacing: 8,
                                              children: [
                                                for (final modifier
                                                    in item.modifiers)
                                                  Chip(label: Text(modifier)),
                                              ],
                                            ),
                                          ],
                                        ],
                                      ),
                                    ),
                                    FilledButton.tonal(
                                      onPressed: () => _advanceItem(item),
                                      child: Text(_nextLabel(item.kdsStatus)),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                        ],
                      ),
                    ),
                  ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _advanceItem(OrderItemLine item) async {
    final nextStatus = _nextStatus(item.kdsStatus);
    try {
      await ref.read(suiteRepositoryProvider).updateKitchenItemStatus(
            itemId: item.id,
            status: nextStatus,
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('${item.name} marked $nextStatus')),
      );
      setState(() {
        _future = ref.read(suiteRepositoryProvider).fetchKitchenBoard();
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  String _nextStatus(String? status) {
    switch (status) {
      case 'queued':
        return 'preparing';
      case 'preparing':
        return 'ready';
      default:
        return 'served';
    }
  }

  String _nextLabel(String? status) {
    switch (status) {
      case 'queued':
        return 'Start';
      case 'preparing':
        return 'Ready';
      default:
        return 'Serve';
    }
  }
}

class _MetricTile extends StatelessWidget {
  const _MetricTile({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: SizedBox(
        width: 180,
        child: Padding(
          padding: const EdgeInsets.all(18),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Icon(Icons.local_fire_department_outlined, color: color),
              ),
              const SizedBox(height: 12),
              Text(label),
              Text(
                value,
                style: Theme.of(context)
                    .textTheme
                    .headlineMedium
                    ?.copyWith(fontWeight: FontWeight.w800),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
