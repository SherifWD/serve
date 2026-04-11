import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/models/app_models.dart';
import '../../../core/widgets/state_views.dart';
import '../../suite/data/suite_repository.dart';
import 'waiter_order_page.dart';

class WaiterWorkspacePage extends ConsumerStatefulWidget {
  const WaiterWorkspacePage({super.key});

  @override
  ConsumerState<WaiterWorkspacePage> createState() =>
      _WaiterWorkspacePageState();
}

class _WaiterWorkspacePageState extends ConsumerState<WaiterWorkspacePage> {
  late Future<List<TableOverview>> _tablesFuture;

  @override
  void initState() {
    super.initState();
    _tablesFuture = ref.read(suiteRepositoryProvider).fetchTables();
  }

  Future<void> _refreshTables() async {
    setState(() {
      _tablesFuture = ref.read(suiteRepositoryProvider).fetchTables();
    });
    await _tablesFuture;
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<TableOverview>>(
      future: _tablesFuture,
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

        final tables = tableSnapshot.data!;
        final occupied = tables.where((table) => table.isOccupied).toList();
        final covers = occupied.fold<int>(0, (sum, table) => sum + table.seats);
        final liveSales = occupied.fold<double>(
          0,
          (sum, table) => sum + table.orderTotal,
        );
        return Column(
          children: [
            _WaiterHero(
              tableCount: tables.length,
              activeChecks: occupied.length,
              activeCovers: covers,
              liveSales: liveSales,
            ),
            const SizedBox(height: 16),
            Expanded(
              child: _FloorTab(
                tables: tables,
                onRefresh: _refreshTables,
              ),
            ),
          ],
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
    final currency = NumberFormat.currency(symbol: 'EGP ');

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1E293B), Color(0xFF334155), Color(0xFF4B5563)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(30),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Tableside service board',
                      style:
                          Theme.of(context).textTheme.headlineSmall?.copyWith(
                                color: Colors.white,
                                fontWeight: FontWeight.w900,
                              ),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Fast table access with direct handoff to kitchen and cashier.',
                      style: TextStyle(color: Colors.white70, height: 1.35),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              _HeroMetric(
                title: 'Tables',
                value: '$tableCount',
                accent: const Color(0xFFF59E0B),
              ),
              _HeroMetric(
                title: 'Open orders',
                value: '$activeChecks',
                accent: const Color(0xFFE86C2F),
              ),
              _HeroMetric(
                title: 'Active covers',
                value: '$activeCovers',
                accent: const Color(0xFF38BDF8),
              ),
              _HeroMetric(
                title: 'Open value',
                value: currency.format(liveSales),
                accent: const Color(0xFF34D399),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _FloorTab extends StatefulWidget {
  const _FloorTab({
    required this.tables,
    required this.onRefresh,
  });

  final List<TableOverview> tables;
  final Future<void> Function() onRefresh;

  @override
  State<_FloorTab> createState() => _FloorTabState();
}

class _FloorTabState extends State<_FloorTab> {
  String _filter = 'all';

  List<TableOverview> get _filteredTables {
    return widget.tables.where((table) {
      switch (_filter) {
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
    final crossAxisCount = width > 1300 ? 4 : (width > 800 ? 3 : 2);
    final filteredTables = _filteredTables;

    return RefreshIndicator(
      onRefresh: widget.onRefresh,
      child: ListView(
        children: [
          const _QuickActionStrip(),
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
              childAspectRatio: 1.18,
            ),
            itemBuilder: (context, index) {
              final table = filteredTables[index];
              return _TableTile(table: table);
            },
          ),
        ],
      ),
    );
  }
}

class _QuickActionStrip extends StatelessWidget {
  const _QuickActionStrip();

  @override
  Widget build(BuildContext context) {
    return const SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(
        children: [
          _QuickActionCard(
            icon: Icons.table_bar_outlined,
            label: 'Open table',
            caption: 'Start dine-in',
          ),
          SizedBox(width: 10),
          _QuickActionCard(
            icon: Icons.person_add_alt_1_outlined,
            label: 'Attach guest',
            caption: 'Track loyalty',
          ),
          SizedBox(width: 10),
          _QuickActionCard(
            icon: Icons.tune_rounded,
            label: 'Add modifiers',
            caption: 'Upsell cleanly',
          ),
          SizedBox(width: 10),
          _QuickActionCard(
            icon: Icons.soup_kitchen_outlined,
            label: 'Send to kitchen',
            caption: 'Fire the course',
          ),
          SizedBox(width: 10),
          _QuickActionCard(
            icon: Icons.point_of_sale_outlined,
            label: 'Send to cashier',
            caption: 'Close the check',
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
    ('busy', 'Busy'),
    ('available', 'Available'),
    ('kitchen', 'Kitchen'),
    ('ready', 'Ready'),
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
  });

  final IconData icon;
  final String label;
  final String caption;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 156,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE7DED2)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: const Color(0xFFFFE7D4),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(icon, color: const Color(0xFFE86C2F)),
          ),
          const SizedBox(height: 12),
          Text(
            label,
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
          ),
          const SizedBox(height: 4),
          Text(
            caption,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: const Color(0xFF6B7280),
                ),
          ),
        ],
      ),
    );
  }
}

class _TableTile extends StatelessWidget {
  const _TableTile({required this.table});

  final TableOverview table;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');
    final statusColor = _tableStatusColor(table.serviceStatus);

    return InkWell(
      borderRadius: BorderRadius.circular(26),
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
                      borderRadius: BorderRadius.circular(16),
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
    required this.title,
    required this.value,
    required this.accent,
  });

  final String title;
  final String value;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 180,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(22),
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
        borderRadius: BorderRadius.circular(14),
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
