import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/models/app_models.dart';
import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/state_views.dart';
import '../../suite/data/suite_repository.dart';

class CashierWorkspacePage extends ConsumerStatefulWidget {
  const CashierWorkspacePage({super.key});

  @override
  ConsumerState<CashierWorkspacePage> createState() =>
      _CashierWorkspacePageState();
}

class _CashierWorkspacePageState extends ConsumerState<CashierWorkspacePage> {
  late Future<List<StaffOrderSnapshot>> _future;
  StaffOrderSnapshot? _selectedOrder;
  List<_PaymentDraft> _drafts = [];

  @override
  void initState() {
    super.initState();
    _future = _loadOrders();
  }

  Future<List<StaffOrderSnapshot>> _loadOrders() async {
    final orders = await ref.read(suiteRepositoryProvider).fetchCashierOrders();
    final cashierOrders = orders
        .where(
            (order) => order.status == 'cashier' || order.outstandingAmount > 0)
        .toList();
    if (_selectedOrder == null && cashierOrders.isNotEmpty) {
      _selectedOrder = cashierOrders.first;
      _resetDrafts();
    }
    return cashierOrders;
  }

  void _selectOrder(StaffOrderSnapshot order) {
    setState(() {
      _selectedOrder = order;
      _resetDrafts();
    });
  }

  void _resetDrafts() {
    final outstanding = _selectedOrder?.outstandingAmount ?? 0;
    _drafts = [
      _PaymentDraft(
        method: 'card',
        amountController: TextEditingController(
          text: outstanding == 0 ? '0' : outstanding.toStringAsFixed(2),
        ),
      ),
    ];
  }

  @override
  void dispose() {
    for (final draft in _drafts) {
      draft.amountController.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<StaffOrderSnapshot>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const LoadingView(label: 'Loading cashier queue...');
        }
        if (snapshot.hasError) {
          return ErrorView(
            message: snapshot.error.toString(),
            onRetry: () => setState(() {
              _future = _loadOrders();
            }),
          );
        }

        final orders = snapshot.data!;
        final selected = _selectedOrder;
        final wide = MediaQuery.of(context).size.width > 1120;

        if (orders.isEmpty) {
          return const EmptyView(
            title: 'No cashier queue',
            description:
                'Orders sent from waiter and fully prepared by kitchen will appear here.',
            icon: Icons.point_of_sale_outlined,
          );
        }

        if (selected == null) {
          _selectOrder(orders.first);
        }

        return RefreshIndicator(
          onRefresh: () async {
            setState(() {
              _future = _loadOrders();
            });
            await _future;
          },
          child: wide
              ? Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: _QueueList(
                        orders: orders,
                        selectedOrderId: _selectedOrder?.id,
                        onTap: _selectOrder,
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: _CheckoutCard(
                        order: _selectedOrder!,
                        drafts: _drafts,
                        onAddSplit: _addSplit,
                        onPay: _submitPayments,
                      ),
                    ),
                  ],
                )
              : ListView(
                  children: [
                    _QueueList(
                      orders: orders,
                      selectedOrderId: _selectedOrder?.id,
                      onTap: _selectOrder,
                    ),
                    const SizedBox(height: 16),
                    _CheckoutCard(
                      order: _selectedOrder!,
                      drafts: _drafts,
                      onAddSplit: _addSplit,
                      onPay: _submitPayments,
                    ),
                  ],
                ),
        );
      },
    );
  }

  void _addSplit() {
    setState(() {
      _drafts.add(
        _PaymentDraft(
          method: 'cash',
          amountController: TextEditingController(text: '0'),
        ),
      );
    });
  }

  Future<void> _submitPayments() async {
    final order = _selectedOrder;
    if (order == null) return;

    final payments = <Map<String, dynamic>>[];
    for (final draft in _drafts) {
      final amount = double.tryParse(draft.amountController.text.trim()) ?? 0;
      if (amount <= 0) continue;
      payments.add({'method': draft.method, 'amount': amount});
    }

    if (payments.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Add at least one payment amount before settling.')),
      );
      return;
    }

    try {
      await ref
          .read(suiteRepositoryProvider)
          .payOrder(orderId: order.id, payments: payments);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Payment recorded')),
      );
      setState(() {
        for (final draft in _drafts) {
          draft.amountController.dispose();
        }
        _selectedOrder = null;
        _future = _loadOrders();
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }
}

class _QueueList extends StatelessWidget {
  const _QueueList({
    required this.orders,
    required this.selectedOrderId,
    required this.onTap,
  });

  final List<StaffOrderSnapshot> orders;
  final int? selectedOrderId;
  final ValueChanged<StaffOrderSnapshot> onTap;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                'Cashier queue',
                style: Theme.of(context)
                    .textTheme
                    .headlineSmall
                    ?.copyWith(fontWeight: FontWeight.w800),
              ),
            ),
            Text('${orders.length} orders'),
          ],
        ),
        const SizedBox(height: 16),
        for (final order in orders)
          Card(
            color: selectedOrderId == order.id
                ? const Color(0xFFFFF7EF)
                : Colors.white,
            child: ListTile(
              onTap: () => onTap(order),
              leading: CircleAvatar(
                backgroundColor:
                    const Color(0xFFE86C2F).withValues(alpha: 0.12),
                child: const Icon(Icons.receipt_long, color: Color(0xFFE86C2F)),
              ),
              title:
                  Text('Order #${order.id} • ${order.tableName ?? 'Walk-in'}'),
              subtitle: Text(
                '${order.customerName ?? 'Guest'} • ${order.items.length} items • ${order.branchName ?? 'Branch'}',
              ),
              trailing: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    currency.format(order.outstandingAmount),
                    style: Theme.of(context)
                        .textTheme
                        .titleMedium
                        ?.copyWith(fontWeight: FontWeight.w700),
                  ),
                  Text(order.paymentStatus.toUpperCase()),
                ],
              ),
            ),
          ),
      ],
    );
  }
}

class _CheckoutCard extends StatelessWidget {
  const _CheckoutCard({
    required this.order,
    required this.drafts,
    required this.onAddSplit,
    required this.onPay,
  });

  final StaffOrderSnapshot order;
  final List<_PaymentDraft> drafts;
  final VoidCallback onAddSplit;
  final Future<void> Function() onPay;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');
    return SectionCard(
      title: 'Checkout',
      trailing: Text(
        currency.format(order.outstandingAmount),
        style: Theme.of(context)
            .textTheme
            .titleMedium
            ?.copyWith(fontWeight: FontWeight.w700),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Table: ${order.tableName ?? 'Walk-in'}'),
          const SizedBox(height: 6),
          Text('Guest: ${order.customerName ?? 'Anonymous'}'),
          const SizedBox(height: 6),
          Text('Already paid: ${currency.format(order.paidAmount)}'),
          const SizedBox(height: 18),
          Text(
            'Tenders',
            style: Theme.of(context)
                .textTheme
                .titleMedium
                ?.copyWith(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 12),
          for (final draft in drafts)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Row(
                children: [
                  Expanded(
                    child: DropdownButtonFormField<String>(
                      value: draft.method,
                      items: const [
                        DropdownMenuItem(value: 'cash', child: Text('Cash')),
                        DropdownMenuItem(value: 'card', child: Text('Card')),
                        DropdownMenuItem(
                            value: 'wallet', child: Text('Wallet')),
                      ],
                      onChanged: (value) {
                        if (value != null) {
                          draft.method = value;
                        }
                      },
                      decoration: const InputDecoration(labelText: 'Method'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: TextField(
                      controller: draft.amountController,
                      keyboardType:
                          const TextInputType.numberWithOptions(decimal: true),
                      decoration: const InputDecoration(labelText: 'Amount'),
                    ),
                  ),
                ],
              ),
            ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              OutlinedButton.icon(
                onPressed: onAddSplit,
                icon: const Icon(Icons.call_split),
                label: const Text('Add split'),
              ),
              ElevatedButton.icon(
                onPressed: onPay,
                icon: const Icon(Icons.payments_outlined),
                label: const Text('Record payment'),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Text(
            'Gap closure',
            style: Theme.of(context)
                .textTheme
                .titleMedium
                ?.copyWith(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 8),
          const Text(
              'This cashier app supports mixed payments through multiple tenders, which closes one of the main visible gaps against Foodics dine-in bundles.'),
        ],
      ),
    );
  }
}

class _PaymentDraft {
  _PaymentDraft({
    required this.method,
    required this.amountController,
  });

  String method;
  final TextEditingController amountController;
}
