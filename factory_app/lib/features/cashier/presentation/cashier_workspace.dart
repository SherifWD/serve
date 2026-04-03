import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/models/app_models.dart';
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
  int _selectedDraftIndex = 0;

  @override
  void initState() {
    super.initState();
    _future = _loadOrders();
  }

  Future<List<StaffOrderSnapshot>> _loadOrders() async {
    final orders = await ref.read(suiteRepositoryProvider).fetchCashierOrders();
    final cashierOrders = orders
        .where(
          (order) => order.status == 'cashier' || order.outstandingAmount > 0,
        )
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
    for (final draft in _drafts) {
      draft.amountController.dispose();
    }

    final outstanding = _selectedOrder?.outstandingAmount ?? 0;
    _drafts = [
      _PaymentDraft(
        method: 'card',
        amountController: TextEditingController(
          text: outstanding == 0 ? '0' : outstanding.toStringAsFixed(2),
        ),
      ),
    ];
    _selectedDraftIndex = 0;
  }

  @override
  void dispose() {
    for (final draft in _drafts) {
      draft.amountController.dispose();
    }
    super.dispose();
  }

  _PaymentDraft? get _selectedDraft {
    if (_drafts.isEmpty) return null;
    if (_selectedDraftIndex < 0 || _selectedDraftIndex >= _drafts.length) {
      return _drafts.first;
    }
    return _drafts[_selectedDraftIndex];
  }

  void _addSplit() {
    setState(() {
      _drafts.add(
        _PaymentDraft(
          method: 'cash',
          amountController: TextEditingController(text: '0'),
        ),
      );
      _selectedDraftIndex = _drafts.length - 1;
    });
  }

  void _removeDraft(int index) {
    if (_drafts.length == 1) return;
    setState(() {
      final draft = _drafts.removeAt(index);
      draft.amountController.dispose();
      if (_selectedDraftIndex >= _drafts.length) {
        _selectedDraftIndex = _drafts.length - 1;
      }
    });
  }

  void _setDraftMethod(int index, String method) {
    setState(() {
      _drafts[index].method = method;
    });
  }

  void _selectDraft(int index) {
    setState(() {
      _selectedDraftIndex = index;
    });
  }

  void _appendKey(String value) {
    final draft = _selectedDraft;
    if (draft == null) return;

    setState(() {
      final controller = draft.amountController;
      final current = controller.text.trim();
      if (value == '.') {
        if (current.contains('.')) return;
        controller.text = current.isEmpty ? '0.' : '$current.';
        return;
      }

      if (current == '0') {
        controller.text = value;
      } else {
        controller.text = '$current$value';
      }
    });
  }

  void _backspaceKey() {
    final draft = _selectedDraft;
    if (draft == null) return;

    setState(() {
      final current = draft.amountController.text.trim();
      if (current.isEmpty || current == '0') return;
      final next = current.substring(0, current.length - 1);
      draft.amountController.text = next.isEmpty ? '0' : next;
    });
  }

  void _clearAmount() {
    final draft = _selectedDraft;
    if (draft == null) return;
    setState(() {
      draft.amountController.text = '0';
    });
  }

  void _applyQuickAmount(double amount) {
    final draft = _selectedDraft;
    if (draft == null) return;
    setState(() {
      draft.amountController.text = amount.toStringAsFixed(2);
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
          content: Text('Add at least one payment amount before settling.'),
        ),
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
        _selectedOrder = null;
        _resetDrafts();
        _future = _loadOrders();
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
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
        if (orders.isEmpty) {
          return const EmptyView(
            title: 'No cashier queue',
            description:
                'Orders sent from waiter and fully prepared by kitchen will appear here for settlement.',
            icon: Icons.point_of_sale_outlined,
          );
        }

        if (_selectedOrder == null) {
          _selectOrder(orders.first);
        }

        final selected = _selectedOrder!;
        final width = MediaQuery.of(context).size.width;
        final wide = width > 1240;
        final medium = width > 940;

        return RefreshIndicator(
          onRefresh: () async {
            setState(() {
              _future = _loadOrders();
            });
            await _future;
          },
          child: ListView(
            children: [
              _CashierHeader(
                pendingCount: orders.length,
                dueNow: orders.fold<double>(
                  0,
                  (sum, order) => sum + order.outstandingAmount,
                ),
              ),
              const SizedBox(height: 16),
              if (wide)
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    SizedBox(
                      width: 320,
                      height: 620,
                      child: _QueuePanel(
                        orders: orders,
                        selectedOrderId: selected.id,
                        onTap: _selectOrder,
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: _TicketPanel(order: selected),
                    ),
                    const SizedBox(width: 16),
                    SizedBox(
                      width: 360,
                      child: _PaymentPanel(
                        order: selected,
                        drafts: _drafts,
                        selectedDraftIndex: _selectedDraftIndex,
                        onSelectDraft: _selectDraft,
                        onRemoveDraft: _removeDraft,
                        onMethodChanged: _setDraftMethod,
                        onAddSplit: _addSplit,
                        onQuickAmount: _applyQuickAmount,
                        onKeyTap: _appendKey,
                        onBackspace: _backspaceKey,
                        onClear: _clearAmount,
                        onSubmit: _submitPayments,
                      ),
                    ),
                  ],
                )
              else if (medium)
                Column(
                  children: [
                    SizedBox(
                      height: 320,
                      child: _QueuePanel(
                        orders: orders,
                        selectedOrderId: selected.id,
                        onTap: _selectOrder,
                      ),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(child: _TicketPanel(order: selected)),
                        const SizedBox(width: 16),
                        SizedBox(
                          width: 340,
                          child: _PaymentPanel(
                            order: selected,
                            drafts: _drafts,
                            selectedDraftIndex: _selectedDraftIndex,
                            onSelectDraft: _selectDraft,
                            onRemoveDraft: _removeDraft,
                            onMethodChanged: _setDraftMethod,
                            onAddSplit: _addSplit,
                            onQuickAmount: _applyQuickAmount,
                            onKeyTap: _appendKey,
                            onBackspace: _backspaceKey,
                            onClear: _clearAmount,
                            onSubmit: _submitPayments,
                          ),
                        ),
                      ],
                    ),
                  ],
                )
              else ...[
                SizedBox(
                  height: 280,
                  child: _QueuePanel(
                    orders: orders,
                    selectedOrderId: selected.id,
                    onTap: _selectOrder,
                  ),
                ),
                const SizedBox(height: 16),
                _TicketPanel(order: selected),
                const SizedBox(height: 16),
                _PaymentPanel(
                  order: selected,
                  drafts: _drafts,
                  selectedDraftIndex: _selectedDraftIndex,
                  onSelectDraft: _selectDraft,
                  onRemoveDraft: _removeDraft,
                  onMethodChanged: _setDraftMethod,
                  onAddSplit: _addSplit,
                  onQuickAmount: _applyQuickAmount,
                  onKeyTap: _appendKey,
                  onBackspace: _backspaceKey,
                  onClear: _clearAmount,
                  onSubmit: _submitPayments,
                ),
              ],
            ],
          ),
        );
      },
    );
  }
}

class _CashierHeader extends StatelessWidget {
  const _CashierHeader({
    required this.pendingCount,
    required this.dueNow,
  });

  final int pendingCount;
  final double dueNow;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF111827), Color(0xFF1F2937)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(30),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Front counter settlement',
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w900,
                      ),
                ),
                const SizedBox(height: 8),
                const Text(
                  'Queue left, ticket center, payment controls right. Built for faster settlement and split tenders.',
                  style: TextStyle(color: Colors.white70, height: 1.35),
                ),
              ],
            ),
          ),
          const SizedBox(width: 16),
          _HeaderMetric(
            label: 'Queue',
            value: '$pendingCount',
          ),
          const SizedBox(width: 12),
          _HeaderMetric(
            label: 'Due now',
            value: currency.format(dueNow),
          ),
        ],
      ),
    );
  }
}

class _QueuePanel extends StatelessWidget {
  const _QueuePanel({
    required this.orders,
    required this.selectedOrderId,
    required this.onTap,
  });

  final List<StaffOrderSnapshot> orders;
  final int selectedOrderId;
  final ValueChanged<StaffOrderSnapshot> onTap;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Cashier queue',
              style: Theme.of(context)
                  .textTheme
                  .titleLarge
                  ?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 14),
            Expanded(
              child: ListView.separated(
                itemCount: orders.length,
                separatorBuilder: (_, __) => const SizedBox(height: 10),
                itemBuilder: (context, index) {
                  final order = orders[index];
                  final selected = order.id == selectedOrderId;

                  return InkWell(
                    onTap: () => onTap(order),
                    borderRadius: BorderRadius.circular(22),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 160),
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: selected
                            ? const Color(0xFFFFEEDD)
                            : const Color(0xFFF7F3EE),
                        borderRadius: BorderRadius.circular(22),
                        border: Border.all(
                          color: selected
                              ? const Color(0xFFE86C2F)
                              : Colors.transparent,
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  'Order #${order.id}',
                                  style: Theme.of(context)
                                      .textTheme
                                      .titleMedium
                                      ?.copyWith(fontWeight: FontWeight.w800),
                                ),
                              ),
                              Text(
                                currency.format(order.outstandingAmount),
                                style: const TextStyle(
                                  fontWeight: FontWeight.w800,
                                  color: Color(0xFF111827),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Text(
                            order.tableName ?? 'Walk-in',
                            style: const TextStyle(fontWeight: FontWeight.w700),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            '${order.customerName ?? 'Guest'} • ${order.items.length} items',
                            style: const TextStyle(color: Color(0xFF6B7280)),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TicketPanel extends StatelessWidget {
  const _TicketPanel({required this.order});

  final StaffOrderSnapshot order;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
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
                        order.tableName ?? 'Walk-in order',
                        style: Theme.of(context)
                            .textTheme
                            .headlineSmall
                            ?.copyWith(fontWeight: FontWeight.w900),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${order.customerName ?? 'Guest'}${order.branchName == null ? '' : ' • ${order.branchName}'}',
                      ),
                    ],
                  ),
                ),
                _TicketStatusBadge(label: order.paymentStatus.toUpperCase()),
              ],
            ),
            const SizedBox(height: 18),
            Wrap(
              spacing: 10,
              runSpacing: 10,
              children: [
                _InfoPill(label: 'Type: ${order.orderType}'),
                _InfoPill(label: 'Paid: ${currency.format(order.paidAmount)}'),
                _InfoPill(label: 'Due: ${currency.format(order.outstandingAmount)}'),
              ],
            ),
            const SizedBox(height: 18),
            const Divider(height: 1),
            const SizedBox(height: 14),
            Text(
              'Ticket items',
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 12),
            for (final item in order.items)
              Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 34,
                      height: 34,
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFE4CF),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      alignment: Alignment.center,
                      child: Text(
                        '${item.quantity}',
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            item.name,
                            style: Theme.of(context)
                                .textTheme
                                .titleSmall
                                ?.copyWith(fontWeight: FontWeight.w800),
                          ),
                          if (item.modifiers.isNotEmpty) ...[
                            const SizedBox(height: 4),
                            Text(
                              item.modifiers.join(' • '),
                              style: const TextStyle(color: Color(0xFF6B7280)),
                            ),
                          ],
                          if ((item.itemNote ?? '').isNotEmpty) ...[
                            const SizedBox(height: 4),
                            Text(
                              item.itemNote!,
                              style: const TextStyle(color: Color(0xFF8B5E34)),
                            ),
                          ],
                        ],
                      ),
                    ),
                    Text(
                      currency.format(item.total),
                      style: const TextStyle(fontWeight: FontWeight.w800),
                    ),
                  ],
                ),
              ),
            const Divider(height: 28),
            Row(
              children: [
                const Text('Order total'),
                const Spacer(),
                Text(
                  currency.format(order.total),
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.w900,
                      ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _PaymentPanel extends StatelessWidget {
  const _PaymentPanel({
    required this.order,
    required this.drafts,
    required this.selectedDraftIndex,
    required this.onSelectDraft,
    required this.onRemoveDraft,
    required this.onMethodChanged,
    required this.onAddSplit,
    required this.onQuickAmount,
    required this.onKeyTap,
    required this.onBackspace,
    required this.onClear,
    required this.onSubmit,
  });

  final StaffOrderSnapshot order;
  final List<_PaymentDraft> drafts;
  final int selectedDraftIndex;
  final ValueChanged<int> onSelectDraft;
  final ValueChanged<int> onRemoveDraft;
  final void Function(int index, String method) onMethodChanged;
  final VoidCallback onAddSplit;
  final ValueChanged<double> onQuickAmount;
  final ValueChanged<String> onKeyTap;
  final VoidCallback onBackspace;
  final VoidCallback onClear;
  final Future<void> Function() onSubmit;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Payment panel',
                    style: Theme.of(context)
                        .textTheme
                        .titleLarge
                        ?.copyWith(fontWeight: FontWeight.w800),
                  ),
                ),
                FilledButton.tonalIcon(
                  onPressed: onAddSplit,
                  icon: const Icon(Icons.call_split),
                  label: const Text('Split'),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              'Outstanding ${currency.format(order.outstandingAmount)}',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: const Color(0xFF8A4316),
                  ),
            ),
            const SizedBox(height: 14),
            for (var i = 0; i < drafts.length; i++) ...[
              _TenderDraftCard(
                draft: drafts[i],
                selected: i == selectedDraftIndex,
                onTap: () => onSelectDraft(i),
                onMethodChanged: (method) => onMethodChanged(i, method),
                onRemove: drafts.length == 1 ? null : () => onRemoveDraft(i),
              ),
              const SizedBox(height: 10),
            ],
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _QuickPayButton(
                  label: 'Exact',
                  onTap: () => onQuickAmount(order.outstandingAmount),
                ),
                _QuickPayButton(
                  label: '50%',
                  onTap: () => onQuickAmount(order.outstandingAmount / 2),
                ),
                _QuickPayButton(
                  label: '100',
                  onTap: () => onQuickAmount(100),
                ),
              ],
            ),
            const SizedBox(height: 14),
            _Keypad(
              onKeyTap: onKeyTap,
              onBackspace: onBackspace,
              onClear: onClear,
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: onSubmit,
                icon: const Icon(Icons.check_circle_outline),
                label: const Text('Settle order'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TenderDraftCard extends StatelessWidget {
  const _TenderDraftCard({
    required this.draft,
    required this.selected,
    required this.onTap,
    required this.onMethodChanged,
    required this.onRemove,
  });

  final _PaymentDraft draft;
  final bool selected;
  final VoidCallback onTap;
  final ValueChanged<String> onMethodChanged;
  final VoidCallback? onRemove;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 160),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: selected ? const Color(0xFFFFF0E4) : const Color(0xFFF7F3EE),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color:
                selected ? const Color(0xFFE86C2F) : Colors.transparent,
          ),
        ),
        child: Column(
          children: [
            Row(
              children: [
                Expanded(
                  child: DropdownButtonFormField<String>(
                    value: draft.method,
                    decoration: const InputDecoration(
                      labelText: 'Tender',
                      isDense: true,
                    ),
                    items: const [
                      DropdownMenuItem(value: 'cash', child: Text('Cash')),
                      DropdownMenuItem(value: 'card', child: Text('Card')),
                      DropdownMenuItem(
                          value: 'wallet', child: Text('Wallet')),
                    ],
                    onChanged: (value) {
                      if (value != null) onMethodChanged(value);
                    },
                  ),
                ),
                if (onRemove != null) ...[
                  const SizedBox(width: 10),
                  IconButton(
                    onPressed: onRemove,
                    icon: const Icon(Icons.close),
                  ),
                ],
              ],
            ),
            const SizedBox(height: 12),
            TextField(
              controller: draft.amountController,
              keyboardType:
                  const TextInputType.numberWithOptions(decimal: true),
              decoration: const InputDecoration(labelText: 'Amount'),
            ),
          ],
        ),
      ),
    );
  }
}

class _Keypad extends StatelessWidget {
  const _Keypad({
    required this.onKeyTap,
    required this.onBackspace,
    required this.onClear,
  });

  final ValueChanged<String> onKeyTap;
  final VoidCallback onBackspace;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    final rows = [
      ['1', '2', '3'],
      ['4', '5', '6'],
      ['7', '8', '9'],
      ['.', '0', '⌫'],
    ];

    return Column(
      children: [
        for (final row in rows) ...[
          Row(
            children: [
              for (final key in row)
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.all(4),
                    child: _KeypadButton(
                      label: key,
                      onTap: () {
                        if (key == '⌫') {
                          onBackspace();
                        } else {
                          onKeyTap(key);
                        }
                      },
                    ),
                  ),
                ),
            ],
          ),
        ],
        const SizedBox(height: 4),
        SizedBox(
          width: double.infinity,
          child: OutlinedButton.icon(
            onPressed: onClear,
            icon: const Icon(Icons.clear),
            label: const Text('Clear amount'),
          ),
        ),
      ],
    );
  }
}

class _KeypadButton extends StatelessWidget {
  const _KeypadButton({
    required this.label,
    required this.onTap,
  });

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 68,
      child: ElevatedButton(
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFFF7F3EE),
          foregroundColor: const Color(0xFF111827),
        ),
        onPressed: onTap,
        child: Text(
          label,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w900,
              ),
        ),
      ),
    );
  }
}

class _TicketStatusBadge extends StatelessWidget {
  const _TicketStatusBadge({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: const Color(0xFFEAF7F2),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Color(0xFF0F766E),
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}

class _InfoPill extends StatelessWidget {
  const _InfoPill({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
      decoration: BoxDecoration(
        color: const Color(0xFFF7F3EE),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Text(
        label,
        style: const TextStyle(fontWeight: FontWeight.w700),
      ),
    );
  }
}

class _QuickPayButton extends StatelessWidget {
  const _QuickPayButton({
    required this.label,
    required this.onTap,
  });

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return OutlinedButton(
      onPressed: onTap,
      child: Text(label),
    );
  }
}

class _HeaderMetric extends StatelessWidget {
  const _HeaderMetric({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: Colors.white60)),
          const SizedBox(height: 6),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w900,
            ),
          ),
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
