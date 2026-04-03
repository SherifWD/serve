import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/models/app_models.dart';
import '../../../core/widgets/state_views.dart';
import '../../suite/data/suite_repository.dart';

class OwnerWorkspacePage extends ConsumerStatefulWidget {
  const OwnerWorkspacePage({super.key});

  @override
  ConsumerState<OwnerWorkspacePage> createState() => _OwnerWorkspacePageState();
}

class _OwnerWorkspacePageState extends ConsumerState<OwnerWorkspacePage> {
  late Future<OwnerSummary> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(suiteRepositoryProvider).fetchOwnerSummary();
  }

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');

    return FutureBuilder<OwnerSummary>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const LoadingView(label: 'Loading ownership dashboard...');
        }
        if (snapshot.hasError) {
          return ErrorView(
            message: snapshot.error.toString(),
            onRetry: () => setState(() {
              _future = ref.read(suiteRepositoryProvider).fetchOwnerSummary();
            }),
          );
        }

        final summary = snapshot.data!;
        final width = MediaQuery.of(context).size.width;
        final wide = width > 1220;

        return Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [Color(0xFF0F172A), Color(0xFF111827)],
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
            ),
          ),
          child: RefreshIndicator(
            onRefresh: () async {
              setState(() {
                _future = ref.read(suiteRepositoryProvider).fetchOwnerSummary();
              });
              await _future;
            },
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                _OwnerHero(summary: summary, currency: currency),
                const SizedBox(height: 16),
                Wrap(
                  spacing: 14,
                  runSpacing: 14,
                  children: [
                    _OwnerKpiCard(
                      title: 'Revenue',
                      value: currency.format(summary.totalSales),
                      caption: 'Paid sales',
                      color: const Color(0xFFE86C2F),
                      icon: Icons.attach_money_outlined,
                    ),
                    _OwnerKpiCard(
                      title: 'Orders',
                      value: '${summary.ordersCount}',
                      caption: 'Paid count',
                      color: const Color(0xFF38BDF8),
                      icon: Icons.receipt_long_outlined,
                    ),
                    _OwnerKpiCard(
                      title: 'AOV',
                      value: currency.format(summary.avgOrderValue),
                      caption: 'Average ticket',
                      color: const Color(0xFF34D399),
                      icon: Icons.shopping_bag_outlined,
                    ),
                    _OwnerKpiCard(
                      title: 'Live floor',
                      value: '${summary.activeTables}',
                      caption: 'Active tables',
                      color: const Color(0xFFF59E0B),
                      icon: Icons.table_restaurant_outlined,
                    ),
                    _OwnerKpiCard(
                      title: 'Cashier queue',
                      value: '${summary.cashierQueue}',
                      caption: 'Waiting to settle',
                      color: const Color(0xFFF97316),
                      icon: Icons.point_of_sale_outlined,
                    ),
                    _OwnerKpiCard(
                      title: 'Loyalty',
                      value: '${summary.loyaltyMembers}',
                      caption: 'Tracked guests',
                      color: const Color(0xFFA78BFA),
                      icon: Icons.workspace_premium_outlined,
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                if (wide)
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        flex: 6,
                        child: _BranchPerformancePanel(
                          branches: summary.branchPerformance,
                          currency: currency,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        flex: 4,
                        child: _LiveOpsPanel(summary: summary),
                      ),
                    ],
                  )
                else ...[
                  _BranchPerformancePanel(
                    branches: summary.branchPerformance,
                    currency: currency,
                  ),
                  const SizedBox(height: 16),
                  _LiveOpsPanel(summary: summary),
                ],
                const SizedBox(height: 16),
                if (wide)
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: _PaymentMixPanel(
                          paymentMix: summary.paymentMix,
                          currency: currency,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: _TopProductsPanel(
                          products: summary.topProducts,
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: _StockAlertPanel(
                          items: summary.lowStockItems,
                        ),
                      ),
                    ],
                  )
                else ...[
                  _PaymentMixPanel(
                    paymentMix: summary.paymentMix,
                    currency: currency,
                  ),
                  const SizedBox(height: 16),
                  _TopProductsPanel(products: summary.topProducts),
                  const SizedBox(height: 16),
                  _StockAlertPanel(items: summary.lowStockItems),
                ],
              ],
            ),
          ),
        );
      },
    );
  }
}

class _OwnerHero extends StatelessWidget {
  const _OwnerHero({
    required this.summary,
    required this.currency,
  });

  final OwnerSummary summary;
  final NumberFormat currency;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF111C44), Color(0xFF172554), Color(0xFF0F766E)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(32),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Multi-branch command center',
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w900,
                      ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Foodics-style structure with broader operational visibility: branches, payments, stock risk, cashier pressure, and loyalty impact in one view.',
                  style: TextStyle(color: Colors.white70, height: 1.35),
                ),
                const SizedBox(height: 18),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    _HeroActionChip(
                      icon: Icons.store_mall_directory_outlined,
                      label: '${summary.branchPerformance.length} branches live',
                    ),
                    _HeroActionChip(
                      icon: Icons.inventory_2_outlined,
                      label: '${summary.lowStockItems.length} stock alerts',
                    ),
                    _HeroActionChip(
                      icon: Icons.sync_alt_outlined,
                      label: '${summary.kdsBacklog} KDS backlog',
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 18),
          Container(
            width: 220,
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(24),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Today at a glance',
                  style: TextStyle(color: Colors.white70),
                ),
                const SizedBox(height: 10),
                Text(
                  currency.format(summary.totalSales),
                  style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w900,
                      ),
                ),
                const SizedBox(height: 6),
                Text(
                  '${summary.ordersCount} paid orders',
                  style: const TextStyle(color: Colors.white70),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _OwnerKpiCard extends StatelessWidget {
  const _OwnerKpiCard({
    required this.title,
    required this.value,
    required this.caption,
    required this.color,
    required this.icon,
  });

  final String title;
  final String value;
  final String caption;
  final Color color;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 206,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFF111827),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.white.withValues(alpha: 0.06)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.16),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Icon(icon, color: color),
          ),
          const SizedBox(height: 14),
          Text(title, style: const TextStyle(color: Colors.white70)),
          const SizedBox(height: 8),
          Text(
            value,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w900,
                ),
          ),
          const SizedBox(height: 6),
          Text(
            caption,
            style: const TextStyle(color: Colors.white38),
          ),
        ],
      ),
    );
  }
}

class _BranchPerformancePanel extends StatelessWidget {
  const _BranchPerformancePanel({
    required this.branches,
    required this.currency,
  });

  final List<BranchPerformance> branches;
  final NumberFormat currency;

  @override
  Widget build(BuildContext context) {
    return _OwnerPanel(
      title: 'Branch leaderboard',
      subtitle: 'Ranked by paid sales with orders count in view',
      child: branches.isEmpty
          ? const Text(
              'No branch performance data yet.',
              style: TextStyle(color: Colors.white70),
            )
          : Column(
              children: [
                for (var i = 0; i < branches.length; i++)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.04),
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 38,
                            height: 38,
                            decoration: BoxDecoration(
                              color: const Color(0xFFE86C2F).withValues(alpha: 0.2),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            alignment: Alignment.center,
                            child: Text(
                              '${i + 1}',
                              style: const TextStyle(
                                color: Color(0xFFFFC29C),
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  branches[i].name,
                                  style: Theme.of(context)
                                      .textTheme
                                      .titleMedium
                                      ?.copyWith(
                                        color: Colors.white,
                                        fontWeight: FontWeight.w800,
                                      ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  branches[i].location ?? 'No location set',
                                  style: const TextStyle(color: Colors.white54),
                                ),
                              ],
                            ),
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                currency.format(branches[i].sales),
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w900,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                '${branches[i].ordersCount} orders',
                                style: const TextStyle(color: Colors.white54),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
    );
  }
}

class _LiveOpsPanel extends StatelessWidget {
  const _LiveOpsPanel({required this.summary});

  final OwnerSummary summary;

  @override
  Widget build(BuildContext context) {
    return _OwnerPanel(
      title: 'Live operations',
      subtitle: 'The pressure points that need attention right now',
      child: Column(
        children: [
          _LiveOpsRow(
            title: 'Cashier queue',
            value: '${summary.cashierQueue}',
            color: const Color(0xFFF97316),
          ),
          const SizedBox(height: 12),
          _LiveOpsRow(
            title: 'KDS backlog',
            value: '${summary.kdsBacklog}',
            color: const Color(0xFF38BDF8),
          ),
          const SizedBox(height: 12),
          _LiveOpsRow(
            title: 'Active tables',
            value: '${summary.activeTables}',
            color: const Color(0xFF34D399),
          ),
        ],
      ),
    );
  }
}

class _PaymentMixPanel extends StatelessWidget {
  const _PaymentMixPanel({
    required this.paymentMix,
    required this.currency,
  });

  final List<PaymentMixEntry> paymentMix;
  final NumberFormat currency;

  @override
  Widget build(BuildContext context) {
    return _OwnerPanel(
      title: 'Payment mix',
      subtitle: 'How dine-in cash is being settled',
      child: paymentMix.isEmpty
          ? const Text(
              'No payment data yet.',
              style: TextStyle(color: Colors.white70),
            )
          : Column(
              children: [
                for (final payment in paymentMix)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: Text(
                      payment.method.toUpperCase(),
                      style: const TextStyle(color: Colors.white),
                    ),
                    trailing: Text(
                      currency.format(payment.total),
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
              ],
            ),
    );
  }
}

class _TopProductsPanel extends StatelessWidget {
  const _TopProductsPanel({required this.products});

  final List<Map<String, dynamic>> products;

  @override
  Widget build(BuildContext context) {
    return _OwnerPanel(
      title: 'Top products',
      subtitle: 'Current winning items across the estate',
      child: products.isEmpty
          ? const Text(
              'No top products yet.',
              style: TextStyle(color: Colors.white70),
            )
          : Column(
              children: [
                for (final product in products)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: Text(
                      product['name']?.toString() ?? 'Product',
                      style: const TextStyle(color: Colors.white),
                    ),
                    trailing: Text(
                      '${product['quantity']} sold',
                      style: const TextStyle(color: Colors.white70),
                    ),
                  ),
              ],
            ),
    );
  }
}

class _StockAlertPanel extends StatelessWidget {
  const _StockAlertPanel({required this.items});

  final List<Map<String, dynamic>> items;

  @override
  Widget build(BuildContext context) {
    return _OwnerPanel(
      title: 'Low stock alerts',
      subtitle: 'Ingredients that are approaching minimum stock',
      child: items.isEmpty
          ? const Text(
              'Stock levels look safe.',
              style: TextStyle(color: Colors.white70),
            )
          : Column(
              children: [
                for (final item in items.take(6))
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(
                      Icons.warning_amber_outlined,
                      color: Color(0xFFF97316),
                    ),
                    title: Text(
                      item['name']?.toString() ?? 'Ingredient',
                      style: const TextStyle(color: Colors.white),
                    ),
                    trailing: Text(
                      '${item['stock']} ${item['unit'] ?? ''}',
                      style: const TextStyle(color: Colors.white70),
                    ),
                  ),
              ],
            ),
    );
  }
}

class _OwnerPanel extends StatelessWidget {
  const _OwnerPanel({
    required this.title,
    required this.subtitle,
    required this.child,
  });

  final String title;
  final String subtitle;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFF111827),
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: Colors.white.withValues(alpha: 0.06)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w900,
                ),
          ),
          const SizedBox(height: 6),
          Text(
            subtitle,
            style: const TextStyle(color: Colors.white54),
          ),
          const SizedBox(height: 16),
          child,
        ],
      ),
    );
  }
}

class _HeroActionChip extends StatelessWidget {
  const _HeroActionChip({
    required this.icon,
    required this.label,
  });

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: Colors.white, size: 18),
          const SizedBox(width: 8),
          Text(
            label,
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}

class _LiveOpsRow extends StatelessWidget {
  const _LiveOpsRow({
    required this.title,
    required this.value,
    required this.color,
  });

  final String title;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.04),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        children: [
          Expanded(
            child: Text(
              title,
              style: const TextStyle(color: Colors.white70),
            ),
          ),
          Text(
            value,
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.w900,
              fontSize: 20,
            ),
          ),
        ],
      ),
    );
  }
}
