import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/models/app_models.dart';
import '../../../core/widgets/branded_image.dart';
import '../../../core/widgets/section_card.dart';
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
  late Future<List<MenuCategoryData>> _menuFuture;

  @override
  void initState() {
    super.initState();
    _tablesFuture = ref.read(suiteRepositoryProvider).fetchTables();
    _menuFuture = ref.read(suiteRepositoryProvider).fetchMenu();
  }

  Future<void> _refreshTables() async {
    setState(() {
      _tablesFuture = ref.read(suiteRepositoryProvider).fetchTables();
    });
    await _tablesFuture;
  }

  Future<void> _refreshMenu() async {
    setState(() {
      _menuFuture = ref.read(suiteRepositoryProvider).fetchMenu();
    });
    await _menuFuture;
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
        final wide = MediaQuery.of(context).size.width > 1180;

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
              child: wide
                  ? _WaiterWideLayout(
                      tables: tables,
                      menuFuture: _menuFuture,
                      onRefreshTables: _refreshTables,
                      onRefreshMenu: _refreshMenu,
                    )
                  : DefaultTabController(
                      length: 4,
                      child: Column(
                        children: [
                          const Align(
                            alignment: Alignment.centerLeft,
                            child: TabBar(
                              isScrollable: true,
                              tabAlignment: TabAlignment.start,
                              tabs: [
                                Tab(text: 'Floor'),
                                Tab(text: 'Live checks'),
                                Tab(text: 'Menu cues'),
                                Tab(text: 'Service'),
                              ],
                            ),
                          ),
                          const SizedBox(height: 16),
                          Expanded(
                            child: TabBarView(
                              children: [
                                _FloorTab(
                                  tables: tables,
                                  onRefresh: _refreshTables,
                                ),
                                _LiveChecksTab(
                                  tables: tables,
                                  onRefresh: _refreshTables,
                                ),
                                _MenuCuesTab(
                                  future: _menuFuture,
                                  onRefresh: _refreshMenu,
                                ),
                                const _ServicePlaybookTab(),
                              ],
                            ),
                          ),
                        ],
                      ),
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
                      'Floor-first ordering with fast table access, live check values, and direct handoff to kitchen and cashier.',
                      style: TextStyle(color: Colors.white70, height: 1.35),
                    ),
                  ],
                ),
              ),
              const _HeroChip(
                label: 'One-hand ordering',
                icon: Icons.pan_tool_alt_outlined,
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
                title: 'Active checks',
                value: '$activeChecks',
                accent: const Color(0xFFE86C2F),
              ),
              _HeroMetric(
                title: 'Active covers',
                value: '$activeCovers',
                accent: const Color(0xFF38BDF8),
              ),
              _HeroMetric(
                title: 'Live value',
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

class _WaiterWideLayout extends StatelessWidget {
  const _WaiterWideLayout({
    required this.tables,
    required this.menuFuture,
    required this.onRefreshTables,
    required this.onRefreshMenu,
  });

  final List<TableOverview> tables;
  final Future<List<MenuCategoryData>> menuFuture;
  final Future<void> Function() onRefreshTables;
  final Future<void> Function() onRefreshMenu;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          flex: 8,
          child: _FloorTab(
            tables: tables,
            onRefresh: onRefreshTables,
          ),
        ),
        const SizedBox(width: 16),
        SizedBox(
          width: 420,
          child: Column(
            children: [
              Expanded(
                flex: 5,
                child: _WorkspacePanel(
                  title: 'Live checks',
                  subtitle: 'See active tables and jump straight into service.',
                  child: _LiveChecksTab(
                    tables: tables,
                    onRefresh: onRefreshTables,
                  ),
                ),
              ),
              const SizedBox(height: 16),
              Expanded(
                flex: 4,
                child: _WorkspacePanel(
                  title: 'Menu cues',
                  subtitle:
                      'Keep top sellers, images, and modifier prompts visible.',
                  child: _MenuCuesTab(
                    future: menuFuture,
                    onRefresh: onRefreshMenu,
                  ),
                ),
              ),
              const SizedBox(height: 16),
              const Expanded(
                flex: 3,
                child: _WorkspacePanel(
                  title: 'Service rhythm',
                  subtitle: 'Short operator guidance for a cleaner floor flow.',
                  child: _ServicePlaybookTab(),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _WorkspacePanel extends StatelessWidget {
  const _WorkspacePanel({
    required this.title,
    required this.subtitle,
    required this.child,
  });

  final String title;
  final String subtitle;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.78),
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: const Color(0xFFE8DDD2)),
      ),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(18, 18, 18, 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: Theme.of(context)
                  .textTheme
                  .titleLarge
                  ?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 6),
            Text(
              subtitle,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                    color: const Color(0xFF6B7280),
                  ),
            ),
            const SizedBox(height: 12),
            Expanded(child: child),
          ],
        ),
      ),
    );
  }
}

class _FloorTab extends StatelessWidget {
  const _FloorTab({
    required this.tables,
    required this.onRefresh,
  });

  final List<TableOverview> tables;
  final Future<void> Function() onRefresh;

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.of(context).size.width;
    final crossAxisCount = width > 1300 ? 4 : (width > 800 ? 3 : 2);

    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView(
        children: [
          const _QuickActionStrip(),
          const SizedBox(height: 16),
          GridView.builder(
            itemCount: tables.length,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: crossAxisCount,
              crossAxisSpacing: 14,
              mainAxisSpacing: 14,
              childAspectRatio: 1.18,
            ),
            itemBuilder: (context, index) {
              final table = tables[index];
              return _TableTile(table: table);
            },
          ),
        ],
      ),
    );
  }
}

class _LiveChecksTab extends StatelessWidget {
  const _LiveChecksTab({
    required this.tables,
    required this.onRefresh,
  });

  final List<TableOverview> tables;
  final Future<void> Function() onRefresh;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');
    final openChecks = tables.where((table) => table.isOccupied).toList();

    return RefreshIndicator(
      onRefresh: onRefresh,
      child: openChecks.isEmpty
          ? const EmptyView(
              title: 'No open checks',
              description:
                  'The waiter queue is clear. New dine-in activity will appear here as soon as a table is opened.',
              icon: Icons.receipt_long_outlined,
            )
          : ListView.separated(
              itemCount: openChecks.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final table = openChecks[index];
                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(18),
                    child: Row(
                      children: [
                        Container(
                          width: 56,
                          height: 56,
                          decoration: BoxDecoration(
                            color: const Color(0xFFFFE7D4),
                            borderRadius: BorderRadius.circular(18),
                          ),
                          child: const Icon(
                            Icons.table_restaurant_outlined,
                            color: Color(0xFFE86C2F),
                          ),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                table.name,
                                style: Theme.of(context)
                                    .textTheme
                                    .titleMedium
                                    ?.copyWith(fontWeight: FontWeight.w800),
                              ),
                              const SizedBox(height: 6),
                              Text(
                                '${table.seats} seats • ${table.itemCount} line items${table.customerName == null ? '' : ' • ${table.customerName}'}',
                              ),
                              const SizedBox(height: 10),
                              const Wrap(
                                spacing: 8,
                                runSpacing: 8,
                                children: [
                                  _ServiceBadge(label: 'Seat split ready'),
                                  _ServiceBadge(label: 'Modifier flow'),
                                  _ServiceBadge(label: 'Send to KDS'),
                                ],
                              ),
                            ],
                          ),
                        ),
                        const SizedBox(width: 12),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(
                              currency.format(table.orderTotal),
                              style: Theme.of(context)
                                  .textTheme
                                  .titleLarge
                                  ?.copyWith(fontWeight: FontWeight.w900),
                            ),
                            const SizedBox(height: 10),
                            FilledButton.tonalIcon(
                              onPressed: () => Navigator.of(context).push(
                                MaterialPageRoute<void>(
                                  builder: (_) => WaiterOrderPage(table: table),
                                ),
                              ),
                              icon: const Icon(Icons.touch_app_outlined),
                              label: const Text('Open check'),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
    );
  }
}

class _MenuCuesTab extends StatelessWidget {
  const _MenuCuesTab({
    required this.future,
    required this.onRefresh,
  });

  final Future<List<MenuCategoryData>> future;
  final Future<void> Function() onRefresh;

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<MenuCategoryData>>(
      future: future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const LoadingView(label: 'Loading menu cues...');
        }
        if (snapshot.hasError) {
          return ErrorView(
            message: snapshot.error.toString(),
            onRetry: onRefresh,
          );
        }

        final categories = snapshot.data!;
        return RefreshIndicator(
          onRefresh: onRefresh,
          child: ListView.separated(
            itemCount: categories.length,
            separatorBuilder: (_, __) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final category = categories[index];
              return SectionCard(
                title: category.name,
                trailing: Text('${category.products.length} products'),
                child: Column(
                  children: [
                    for (final product in category.products.take(5))
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: ClipRRect(
                          borderRadius: BorderRadius.circular(14),
                          child: SizedBox(
                            width: 52,
                            height: 52,
                            child: BrandedImage(
                              label: product.name,
                              imageUrl: product.imageUrl,
                              kind: BrandedImageKind.dish,
                            ),
                          ),
                        ),
                        title: Text(product.name),
                        subtitle: Text(
                          category.questions.isEmpty
                              ? 'Fast add item'
                              : '${category.questions.length} modifier prompts',
                        ),
                        trailing:
                            Text('EGP ${product.price.toStringAsFixed(2)}'),
                      ),
                  ],
                ),
              );
            },
          ),
        );
      },
    );
  }
}

class _ServicePlaybookTab extends StatelessWidget {
  const _ServicePlaybookTab();

  @override
  Widget build(BuildContext context) {
    return ListView(
      children: const [
        SectionCard(
          title: 'Workflow benchmark',
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                  'Table map first for faster tap targets and less navigation.'),
              SizedBox(height: 10),
              Text(
                  'Live checks keep table value visible before moving to cashier.'),
              SizedBox(height: 10),
              Text(
                  'Order detail supports modifiers, change quantity, refunds, move table, and send-to-kitchen.'),
            ],
          ),
        ),
        SizedBox(height: 12),
        SectionCard(
          title: 'Where this should go next',
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Seat-by-seat item tagging and split checks.'),
              SizedBox(height: 10),
              Text('Course firing and pacing per table.'),
              SizedBox(height: 10),
              Text('Shortcuts for common upsells from the order screen.'),
            ],
          ),
        ),
      ],
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

    return InkWell(
      borderRadius: BorderRadius.circular(26),
      onTap: () => Navigator.of(context).push(
        MaterialPageRoute<void>(
          builder: (_) => WaiterOrderPage(table: table),
        ),
      ),
      child: Card(
        color: table.isOccupied ? const Color(0xFFFFF3E9) : Colors.white,
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
                          ? const Color(0xFFFFDFC8)
                          : const Color(0xFFE6F7F4),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Icon(
                      Icons.table_restaurant_outlined,
                      color: table.isOccupied
                          ? const Color(0xFFE86C2F)
                          : const Color(0xFF0F766E),
                    ),
                  ),
                  const Spacer(),
                  _MiniStatus(
                    label: table.isOccupied ? 'Busy' : 'Ready',
                    color: table.isOccupied
                        ? const Color(0xFFE86C2F)
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

class _HeroChip extends StatelessWidget {
  const _HeroChip({
    required this.label,
    required this.icon,
  });

  final String label;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: Colors.white, size: 18),
          const SizedBox(width: 8),
          Text(
            label,
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _ServiceBadge extends StatelessWidget {
  const _ServiceBadge({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Text(
        label,
        style: const TextStyle(fontWeight: FontWeight.w700),
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
