import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/models/app_models.dart';
import '../../../core/widgets/manual_refresh_header.dart';
import '../../../core/widgets/state_views.dart';
import '../../auth/providers/auth_providers.dart';
import '../../suite/data/realtime_service.dart';
import '../../suite/data/suite_repository.dart';
import 'waiter_order_page.dart';

class WaiterWorkspacePage extends ConsumerStatefulWidget {
  const WaiterWorkspacePage({super.key});

  @override
  ConsumerState<WaiterWorkspacePage> createState() =>
      _WaiterWorkspacePageState();
}

class _WaiterWorkspacePageState extends ConsumerState<WaiterWorkspacePage> {
  late Future<TableFloorBundle> _floorFuture;
  DateTime? _lastUpdatedAt;
  bool _hasUpdates = false;
  RealtimeSubscription? _realtimeSubscription;

  @override
  void initState() {
    super.initState();
    _floorFuture = _loadFloor();
    _connectRealtime();
  }

  Future<void> _connectRealtime() async {
    final subscription =
        await ref.read(realtimeServiceProvider).subscribeToBranch(
              surface: 'waiter',
              onEvent: () {
                if (!mounted) return;
                setState(() => _hasUpdates = true);
              },
            );
    if (!mounted) {
      await subscription?.close();
      return;
    }
    _realtimeSubscription = subscription;
  }

  Future<TableFloorBundle> _loadFloor() async {
    final bundle = await ref.read(suiteRepositoryProvider).fetchTableFloor();
    if (mounted) {
      setState(() {
        _lastUpdatedAt = DateTime.now();
        _hasUpdates = false;
      });
    }
    return bundle;
  }

  Future<void> _refreshTables() async {
    final future = _loadFloor();
    setState(() {
      _floorFuture = future;
    });
    await _floorFuture;
  }

  @override
  void dispose() {
    _realtimeSubscription?.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final session = ref.watch(currentSessionProvider);

    return FutureBuilder<TableFloorBundle>(
      future: _floorFuture,
      builder: (context, tableSnapshot) {
        if (tableSnapshot.connectionState != ConnectionState.done) {
          return const LoadingView(label: 'Loading floor map...');
        }
        if (tableSnapshot.hasError) {
          return ErrorView(
            message: tableSnapshot.error.toString(),
            onRetry: _refreshTables,
          );
        }

        final bundle = tableSnapshot.data!;
        final tables = bundle.tables;
        final occupied = tables.where((table) => table.isOccupied).toList();
        final covers = occupied.fold<int>(0, (sum, table) => sum + table.seats);
        final liveSales = occupied.fold<double>(
          0,
          (sum, table) => sum + table.orderTotal,
        );
        return RefreshIndicator(
          onRefresh: _refreshTables,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: [
              ManualRefreshHeader(
                title: 'Floor service',
                subtitle: bundle.operationProfile.label,
                icon: Icons.table_restaurant_outlined,
                lastUpdatedAt: _lastUpdatedAt,
                hasUpdates: _hasUpdates,
                onRefresh: _refreshTables,
              ),
              const SizedBox(height: 16),
              _WaiterHero(
                tableCount: tables.length,
                activeChecks: occupied.length,
                activeCovers: covers,
                liveSales: liveSales,
              ),
              const SizedBox(height: 16),
              _FloorTab(
                tables: tables,
                operationProfile: bundle.operationProfile,
                currentUserId: session?.id,
              ),
            ],
          ),
        );
      },
    );
  }
}

class _WaiterHero extends StatelessWidget {
  const _WaiterHero({
    required this.tableCount,
    required this.activeChecks,
    required this.activeCovers,
    required this.liveSales,
  });

  final int tableCount;
  final int activeChecks;
  final int activeCovers;
  final double liveSales;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'USD ');

    return LayoutBuilder(
      builder: (context, constraints) {
        final maxWidth = constraints.maxWidth;
        final compact = maxWidth < 560;
        final metricWidth = compact
            ? (maxWidth - 12) / 2
            : maxWidth < 900
                ? 170.0
                : 180.0;

        return Container(
          width: double.infinity,
          padding: EdgeInsets.all(compact ? 16 : 22),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF1E293B), Color(0xFF334155), Color(0xFF4B5563)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(compact ? 22 : 30),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 18),
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  _HeroMetric(
                    width: metricWidth,
                    title: 'Tables',
                    value: '$tableCount',
                    accent: const Color(0xFFF59E0B),
                  ),
                  _HeroMetric(
                    width: metricWidth,
                    title: 'Open orders',
                    value: '$activeChecks',
                    accent: const Color(0xFFE86C2F),
                  ),
                  _HeroMetric(
                    width: metricWidth,
                    title: 'Active covers',
                    value: '$activeCovers',
                    accent: const Color(0xFF38BDF8),
                  ),
                  _HeroMetric(
                    width: metricWidth,
                    title: 'Open value',
                    value: currency.format(liveSales),
                    accent: const Color(0xFF34D399),
                  ),
                ],
              ),
            ],
          ),
        );
      },
    );
  }
}

class _FloorTab extends StatefulWidget {
  const _FloorTab({
    required this.tables,
    required this.operationProfile,
    required this.currentUserId,
  });

  final List<TableOverview> tables;
  final OperationProfile operationProfile;
  final int? currentUserId;

  @override
  State<_FloorTab> createState() => _FloorTabState();
}

class _FloorTabState extends State<_FloorTab> {
  String _filter = 'all';

  List<TableOverview> get _filteredTables {
    return widget.tables.where((table) {
      switch (_filter) {
        case 'my':
          return widget.currentUserId != null &&
              table.isOpenedBy(widget.currentUserId!);
        case 'unassigned':
          return table.isUnassigned;
        case 'available':
          return table.isAvailable;
        case 'busy':
          return table.isOccupied;
        case 'kitchen':
          return table.serviceStatus == 'kitchen';
        case 'ready':
          return table.serviceStatus == 'ready';
        case 'served':
          return table.serviceStatus == 'served';
        case 'cashier':
          return table.serviceStatus == 'cashier';
        case 'needs_cashier':
          return table.serviceStatus == 'ready' ||
              table.serviceStatus == 'served';
        case 'returned':
          return table.serviceStatus == 'returned';
        default:
          return true;
      }
    }).toList(growable: false);
  }

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.of(context).size.width;
    final crossAxisCount =
        width > 1300 ? 4 : (width > 760 ? 3 : (width > 560 ? 2 : 1));
    final aspectRatio = switch (crossAxisCount) {
      1 => 1.55,
      2 => 1.25,
      _ => 1.18,
    };
    final filteredTables = _filteredTables;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _QuickActionStrip(
          canMoveTables: widget.operationProfile.tableTransfer,
          onOpenTable: () => _showTablePicker(
            title: 'Open table',
            emptyMessage: 'No open tables are available right now.',
            tables: widget.tables
                .where((table) => table.isAvailable)
                .toList(growable: false),
          ),
          onMyTables: () => setState(() => _filter = 'my'),
          onReady: () => setState(() => _filter = 'ready'),
          onNeedsCashier: () => setState(() => _filter = 'needs_cashier'),
          onReturned: () => setState(() => _filter = 'returned'),
          onMoveTable: () => _showTablePicker(
            title: 'Move table',
            emptyMessage: 'No occupied tables are available to move.',
            tables: widget.tables
                .where((table) => table.isOccupied)
                .toList(growable: false),
          ),
        ),
        const SizedBox(height: 16),
        _TableFilterStrip(
          value: _filter,
          onChanged: (value) => setState(() => _filter = value),
        ),
        const SizedBox(height: 16),
        GridView.builder(
          itemCount: filteredTables.length,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: crossAxisCount,
            crossAxisSpacing: 14,
            mainAxisSpacing: 14,
            childAspectRatio: aspectRatio,
          ),
          itemBuilder: (context, index) {
            final table = filteredTables[index];
            return _TableTile(
              table: table,
              showWaiterName: widget.operationProfile.showWaiterNames,
              currentUserId: widget.currentUserId,
            );
          },
        ),
      ],
    );
  }

  Future<void> _showTablePicker({
    required String title,
    required String emptyMessage,
    required List<TableOverview> tables,
  }) async {
    if (tables.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(emptyMessage)),
      );
      return;
    }

    final selected = await showModalBottomSheet<TableOverview>(
      context: context,
      showDragHandle: true,
      builder: (context) {
        return SafeArea(
          child: ListView.separated(
            shrinkWrap: true,
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 18),
            itemCount: tables.length + 1,
            separatorBuilder: (_, __) => const Divider(height: 1),
            itemBuilder: (context, index) {
              if (index == 0) {
                return Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Text(
                    title,
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.w900,
                        ),
                  ),
                );
              }

              final table = tables[index - 1];
              return ListTile(
                contentPadding: EdgeInsets.zero,
                leading: const Icon(Icons.table_restaurant_outlined),
                title: Text(table.name),
                subtitle: Text(
                  table.isOccupied
                      ? '${table.statusLabel} / ${table.itemCount} items'
                      : '${table.seats} covers',
                ),
                trailing: const Icon(Icons.chevron_right),
                onTap: () => Navigator.of(context).pop(table),
              );
            },
          ),
        );
      },
    );

    if (selected == null || !mounted) return;
    Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => WaiterOrderPage(table: selected),
      ),
    );
  }
}

class _QuickActionStrip extends StatelessWidget {
  const _QuickActionStrip({
    required this.canMoveTables,
    required this.onOpenTable,
    required this.onMyTables,
    required this.onReady,
    required this.onNeedsCashier,
    required this.onReturned,
    required this.onMoveTable,
  });

  final bool canMoveTables;
  final VoidCallback onOpenTable;
  final VoidCallback onMyTables;
  final VoidCallback onReady;
  final VoidCallback onNeedsCashier;
  final VoidCallback onReturned;
  final VoidCallback onMoveTable;

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          _QuickActionCard(
            icon: Icons.table_bar_outlined,
            label: 'Open table',
            caption: 'Start dine-in',
            onTap: onOpenTable,
          ),
          const SizedBox(width: 10),
          _QuickActionCard(
            icon: Icons.person_pin_circle_outlined,
            label: 'My tables',
            caption: 'Only mine',
            onTap: onMyTables,
          ),
          const SizedBox(width: 10),
          _QuickActionCard(
            icon: Icons.room_service_outlined,
            label: 'Ready',
            caption: 'Serve now',
            onTap: onReady,
          ),
          const SizedBox(width: 10),
          _QuickActionCard(
            icon: Icons.point_of_sale_outlined,
            label: 'Needs cashier',
            caption: 'Close checks',
            onTap: onNeedsCashier,
          ),
          const SizedBox(width: 10),
          _QuickActionCard(
            icon: Icons.assignment_return_outlined,
            label: 'Returned',
            caption: 'Fix issues',
            onTap: onReturned,
          ),
          const SizedBox(width: 10),
          _QuickActionCard(
            icon: Icons.swap_horiz,
            label: 'Move table',
            caption: canMoveTables ? 'Pick order' : 'Disabled by mode',
            onTap: canMoveTables ? onMoveTable : null,
          ),
        ],
      ),
    );
  }
}

class _TableFilterStrip extends StatelessWidget {
  const _TableFilterStrip({
    required this.value,
    required this.onChanged,
  });

  final String value;
  final ValueChanged<String> onChanged;

  static const _filters = [
    ('all', 'All'),
    ('my', 'My tables'),
    ('unassigned', 'Unassigned'),
    ('busy', 'Busy'),
    ('available', 'Available'),
    ('kitchen', 'Kitchen'),
    ('ready', 'Ready'),
    ('needs_cashier', 'Needs cashier'),
    ('served', 'Served'),
    ('cashier', 'Cashier'),
    ('returned', 'Returned'),
  ];

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          for (final filter in _filters) ...[
            ChoiceChip(
              label: Text(filter.$2),
              selected: value == filter.$1,
              onSelected: (_) => onChanged(filter.$1),
            ),
            const SizedBox(width: 8),
          ],
        ],
      ),
    );
  }
}

class _QuickActionCard extends StatelessWidget {
  const _QuickActionCard({
    required this.icon,
    required this.label,
    required this.caption,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final String caption;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final enabled = onTap != null;

    return Material(
      color: enabled ? Colors.white : const Color(0xFFF3F4F6),
      borderRadius: BorderRadius.circular(8),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Container(
          width: 156,
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: const Color(0xFFE7DED2)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: enabled
                      ? const Color(0xFFFFE7D4)
                      : const Color(0xFFE5E7EB),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(
                  icon,
                  color: enabled
                      ? const Color(0xFFE86C2F)
                      : const Color(0xFF9CA3AF),
                ),
              ),
              const SizedBox(height: 12),
              Text(
                label,
                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w800,
                      color: enabled ? null : const Color(0xFF9CA3AF),
                    ),
              ),
              const SizedBox(height: 4),
              Text(
                caption,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: enabled
                          ? const Color(0xFF6B7280)
                          : const Color(0xFF9CA3AF),
                    ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _TableTile extends StatelessWidget {
  const _TableTile({
    required this.table,
    required this.showWaiterName,
    required this.currentUserId,
  });

  final TableOverview table;
  final bool showWaiterName;
  final int? currentUserId;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'USD ');
    final statusColor = _tableStatusColor(table.serviceStatus);
    final waiterLabel = table.waiterName == null
        ? 'Unassigned'
        : currentUserId != null && table.isOpenedBy(currentUserId!)
            ? 'Mine / ${table.waiterName}'
            : table.waiterName!;

    return InkWell(
      borderRadius: BorderRadius.circular(8),
      onTap: () => Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => WaiterOrderPage(table: table),
        ),
      ),
      child: Card(
        color: table.isOccupied
            ? statusColor.withValues(alpha: 0.10)
            : Colors.white,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: table.isOccupied
                          ? statusColor.withValues(alpha: 0.18)
                          : const Color(0xFFE6F7F4),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(
                      Icons.table_restaurant_outlined,
                      color: table.isOccupied
                          ? statusColor
                          : const Color(0xFF0F766E),
                    ),
                  ),
                  const Spacer(),
                  _MiniStatus(
                    label: table.statusLabel,
                    color: table.isOccupied
                        ? statusColor
                        : const Color(0xFF0F766E),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                table.name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.w800),
              ),
              const SizedBox(height: 4),
              Text('${table.seats} covers'),
              const SizedBox(height: 8),
              Expanded(
                child: Align(
                  alignment: Alignment.bottomLeft,
                  child: table.isOccupied
                      ? Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              currency.format(table.orderTotal),
                              style: Theme.of(context)
                                  .textTheme
                                  .titleMedium
                                  ?.copyWith(fontWeight: FontWeight.w900),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              '${table.itemCount} items',
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                            if (showWaiterName) ...[
                              const SizedBox(height: 2),
                              Text(
                                waiterLabel,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: Theme.of(context)
                                    .textTheme
                                    .bodySmall
                                    ?.copyWith(fontWeight: FontWeight.w800),
                              ),
                            ],
                          ],
                        )
                      : const Text('Tap to start dine-in'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

Color _tableStatusColor(String status) {
  switch (status) {
    case 'cashier':
      return const Color(0xFF7C3AED);
    case 'kitchen':
      return const Color(0xFFF59E0B);
    case 'ready':
      return const Color(0xFF059669);
    case 'served':
      return const Color(0xFF2563EB);
    case 'returned':
      return const Color(0xFFDC2626);
    default:
      return const Color(0xFFE86C2F);
  }
}

class _HeroMetric extends StatelessWidget {
  const _HeroMetric({
    required this.width,
    required this.title,
    required this.value,
    required this.accent,
  });

  final double width;
  final String title;
  final String value;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(color: Colors.white60),
          ),
          const SizedBox(height: 8),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  color: accent,
                  fontWeight: FontWeight.w900,
                ),
          ),
        ],
      ),
    );
  }
}

class _MiniStatus extends StatelessWidget {
  const _MiniStatus({
    required this.label,
    required this.color,
  });

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}
