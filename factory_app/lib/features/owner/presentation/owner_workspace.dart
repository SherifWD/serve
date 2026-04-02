import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/widgets/kpi_card.dart';
import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/state_views.dart';
import '../../suite/data/suite_repository.dart';
import '../../../core/models/app_models.dart';

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
            onRetry: () => setState(() => _future =
                ref.read(suiteRepositoryProvider).fetchOwnerSummary()),
          );
        }

        final summary = snapshot.data!;

        return RefreshIndicator(
          onRefresh: () async {
            setState(() => _future =
                ref.read(suiteRepositoryProvider).fetchOwnerSummary());
            await _future;
          },
          child: ListView(
            children: [
              Wrap(
                spacing: 14,
                runSpacing: 14,
                children: [
                  KpiCard(
                    title: 'Revenue',
                    value: currency.format(summary.totalSales),
                    trendLabel: 'Paid orders',
                    icon: Icons.attach_money_outlined,
                    color: const Color(0xFFE86C2F),
                  ),
                  KpiCard(
                    title: 'Orders',
                    value: '${summary.ordersCount}',
                    trendLabel: 'Paid count',
                    icon: Icons.receipt_long_outlined,
                    color: const Color(0xFF2563EB),
                  ),
                  KpiCard(
                    title: 'AOV',
                    value: currency.format(summary.avgOrderValue),
                    trendLabel: 'Average ticket',
                    icon: Icons.shopping_bag_outlined,
                    color: const Color(0xFF0F766E),
                  ),
                  KpiCard(
                    title: 'Active Tables',
                    value: '${summary.activeTables}',
                    trendLabel: 'Live floor',
                    icon: Icons.table_restaurant_outlined,
                    color: const Color(0xFF7C3AED),
                  ),
                  KpiCard(
                    title: 'Cashier Queue',
                    value: '${summary.cashierQueue}',
                    trendLabel: 'Need payment',
                    icon: Icons.point_of_sale_outlined,
                    color: const Color(0xFFF59E0B),
                  ),
                  KpiCard(
                    title: 'Loyalty Members',
                    value: '${summary.loyaltyMembers}',
                    trendLabel: 'Tracked guests',
                    icon: Icons.workspace_premium_outlined,
                    color: const Color(0xFF0F766E),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              SectionCard(
                title: 'Branch performance',
                child: summary.branchPerformance.isEmpty
                    ? const Text('No branch performance data yet.')
                    : Column(
                        children: [
                          for (final branch in summary.branchPerformance)
                            ListTile(
                              contentPadding: EdgeInsets.zero,
                              leading: CircleAvatar(
                                backgroundColor: const Color(0xFFE86C2F)
                                    .withValues(alpha: 0.12),
                                child: const Icon(
                                    Icons.store_mall_directory_outlined,
                                    color: Color(0xFFE86C2F)),
                              ),
                              title: Text(branch.name),
                              subtitle:
                                  Text(branch.location ?? 'No location set'),
                              trailing: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Text(
                                    currency.format(branch.sales),
                                    style: Theme.of(context)
                                        .textTheme
                                        .titleMedium
                                        ?.copyWith(fontWeight: FontWeight.w700),
                                  ),
                                  Text('${branch.ordersCount} orders'),
                                ],
                              ),
                            ),
                        ],
                      ),
              ),
              const SizedBox(height: 16),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: SectionCard(
                      title: 'Payment mix',
                      child: summary.paymentMix.isEmpty
                          ? const Text('No payment data yet.')
                          : Column(
                              children: [
                                for (final payment in summary.paymentMix)
                                  ListTile(
                                    contentPadding: EdgeInsets.zero,
                                    title: Text(payment.method.toUpperCase()),
                                    trailing: Text(
                                      currency.format(payment.total),
                                      style: Theme.of(context)
                                          .textTheme
                                          .titleMedium
                                          ?.copyWith(
                                              fontWeight: FontWeight.w700),
                                    ),
                                  ),
                              ],
                            ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: SectionCard(
                      title: 'Top products',
                      child: summary.topProducts.isEmpty
                          ? const Text('No top products yet.')
                          : Column(
                              children: [
                                for (final product in summary.topProducts)
                                  ListTile(
                                    contentPadding: EdgeInsets.zero,
                                    title: Text(product['name']?.toString() ??
                                        'Product'),
                                    trailing:
                                        Text('${product['quantity']} sold'),
                                  ),
                              ],
                            ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: SectionCard(
                      title: 'Low stock alerts',
                      child: summary.lowStockItems.isEmpty
                          ? const Text('Stock levels look safe.')
                          : Column(
                              children: [
                                for (final item
                                    in summary.lowStockItems.take(6))
                                  ListTile(
                                    contentPadding: EdgeInsets.zero,
                                    leading: const Icon(
                                        Icons.warning_amber_outlined,
                                        color: Colors.red),
                                    title: Text(item['name']?.toString() ??
                                        'Ingredient'),
                                    trailing: Text(
                                        '${item['stock']} ${item['unit'] ?? ''}'),
                                  ),
                              ],
                            ),
                    ),
                  ),
                  const SizedBox(width: 16),
                  const Expanded(
                    child: SectionCard(
                      title: 'Competitive gap closure',
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                              'Customer loyalty and order history are now surfaced as a real product flow.'),
                          SizedBox(height: 10),
                          Text(
                              'Cashier supports split tenders, closing a visible Foodics dine-in gap.'),
                          SizedBox(height: 10),
                          Text(
                              'Dashboard now shows payment mix, branch ranking, KDS backlog, cashier queue, and loyalty member count.'),
                        ],
                      ),
                    ),
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
