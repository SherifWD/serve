import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/models/app_models.dart';
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

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 3,
      child: Column(
        children: [
          TabBar(
            isScrollable: true,
            tabs: const [
              Tab(text: 'Tables'),
              Tab(text: 'Menu'),
              Tab(text: 'Service'),
            ],
          ),
          const SizedBox(height: 16),
          Expanded(
            child: TabBarView(
              children: [
                _TablesTab(
                  future: _tablesFuture,
                  onRefresh: () async {
                    setState(() {
                      _tablesFuture =
                          ref.read(suiteRepositoryProvider).fetchTables();
                    });
                    await _tablesFuture;
                  },
                ),
                _MenuPreviewTab(
                  future: _menuFuture,
                  onRefresh: () async {
                    setState(() {
                      _menuFuture =
                          ref.read(suiteRepositoryProvider).fetchMenu();
                    });
                    await _menuFuture;
                  },
                ),
                const _ServiceChecklistTab(),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _TablesTab extends StatelessWidget {
  const _TablesTab({
    required this.future,
    required this.onRefresh,
  });

  final Future<List<TableOverview>> future;
  final Future<void> Function() onRefresh;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');

    return FutureBuilder<List<TableOverview>>(
      future: future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const LoadingView(label: 'Loading tables...');
        }
        if (snapshot.hasError) {
          return ErrorView(
              message: snapshot.error.toString(), onRetry: onRefresh);
        }

        final tables = snapshot.data!;
        final occupied = tables.where((table) => table.isOccupied).toList();

        return RefreshIndicator(
          onRefresh: onRefresh,
          child: ListView(
            children: [
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  _StatusChip(
                      label:
                          'Open ${tables.where((t) => !t.isOccupied).length}',
                      color: const Color(0xFF0F766E)),
                  _StatusChip(
                      label: 'Occupied ${occupied.length}',
                      color: const Color(0xFFE86C2F)),
                  _StatusChip(
                    label:
                        'Live value ${currency.format(occupied.fold<double>(0, (sum, table) => sum + table.orderTotal))}',
                    color: const Color(0xFF2563EB),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  crossAxisSpacing: 14,
                  mainAxisSpacing: 14,
                  childAspectRatio: 1.2,
                ),
                itemCount: tables.length,
                itemBuilder: (context, index) {
                  final table = tables[index];
                  return InkWell(
                    borderRadius: BorderRadius.circular(24),
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute<void>(
                        builder: (_) => WaiterOrderPage(table: table),
                      ),
                    ),
                    child: Card(
                      color: table.isOccupied
                          ? const Color(0xFFFFF7EF)
                          : Colors.white,
                      child: Padding(
                        padding: const EdgeInsets.all(18),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                CircleAvatar(
                                  backgroundColor: table.isOccupied
                                      ? const Color(0xFFE86C2F)
                                          .withValues(alpha: 0.12)
                                      : const Color(0xFF0F766E)
                                          .withValues(alpha: 0.12),
                                  child: Icon(
                                    Icons.table_restaurant_outlined,
                                    color: table.isOccupied
                                        ? const Color(0xFFE86C2F)
                                        : const Color(0xFF0F766E),
                                  ),
                                ),
                                const Spacer(),
                                Text(
                                  table.isOccupied ? 'Occupied' : 'Ready',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w700,
                                    color: table.isOccupied
                                        ? const Color(0xFFE86C2F)
                                        : const Color(0xFF0F766E),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 18),
                            Text(
                              table.name,
                              style: Theme.of(context)
                                  .textTheme
                                  .titleLarge
                                  ?.copyWith(fontWeight: FontWeight.w800),
                            ),
                            const SizedBox(height: 6),
                            Text('${table.seats} seats'),
                            const Spacer(),
                            if (table.isOccupied) ...[
                              Text(
                                  '${table.itemCount} items • ${currency.format(table.orderTotal)}'),
                              if (table.customerName != null) ...[
                                const SizedBox(height: 4),
                                Text('Guest: ${table.customerName}'),
                              ],
                            ] else
                              const Text('Tap to start a fresh order'),
                          ],
                        ),
                      ),
                    ),
                  );
                },
              ),
            ],
          ),
        );
      },
    );
  }
}

class _MenuPreviewTab extends StatelessWidget {
  const _MenuPreviewTab({
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
          return const LoadingView(label: 'Loading menu...');
        }
        if (snapshot.hasError) {
          return ErrorView(
              message: snapshot.error.toString(), onRetry: onRefresh);
        }

        final categories = snapshot.data!;
        return RefreshIndicator(
          onRefresh: onRefresh,
          child: ListView(
            children: [
              for (final category in categories)
                SectionCard(
                  title: category.name,
                  trailing: Text('${category.products.length} items'),
                  child: Column(
                    children: [
                      for (final product in category.products)
                        ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(product.name),
                          subtitle: Text(
                            category.questions.isEmpty
                                ? 'No category questions'
                                : '${category.questions.length} question groups',
                          ),
                          trailing:
                              Text('EGP ${product.price.toStringAsFixed(2)}'),
                        ),
                    ],
                  ),
                ),
            ],
          ),
        );
      },
    );
  }
}

class _ServiceChecklistTab extends StatelessWidget {
  const _ServiceChecklistTab();

  @override
  Widget build(BuildContext context) {
    return ListView(
      children: const [
        SectionCard(
          title: 'Operational coverage',
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                  'Customer attachment by phone for loyalty and order history'),
              SizedBox(height: 10),
              Text(
                  'Product notes, category answers, and modifier selection on add-to-order'),
              SizedBox(height: 10),
              Text(
                  'KDS handoff, cashier handoff, quantity change, refund, and table move'),
            ],
          ),
        ),
        SectionCard(
          title: 'Foodics gap closures in this waiter build',
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Cleaner table-first workflow with live table values'),
              SizedBox(height: 10),
              Text(
                  'Loyalty-aware order capture instead of anonymous dine-in only'),
              SizedBox(height: 10),
              Text(
                  'Order composer works directly on the live mobile API rather than on mock data'),
            ],
          ),
        ),
      ],
    );
  }
}

class _StatusChip extends StatelessWidget {
  const _StatusChip({required this.label, required this.color});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Text(
        label,
        style: TextStyle(fontWeight: FontWeight.w700, color: color),
      ),
    );
  }
}
