import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:printing/printing.dart';

import '../../../core/models/app_models.dart';
import '../../../core/widgets/manual_refresh_header.dart';
import '../../../core/widgets/state_views.dart';
import '../../auth/providers/auth_providers.dart';
import '../../suite/data/realtime_service.dart';
import '../../suite/data/suite_repository.dart';

class CashierWorkspacePage extends ConsumerStatefulWidget {
  const CashierWorkspacePage({super.key});

  @override
  ConsumerState<CashierWorkspacePage> createState() =>
      _CashierWorkspacePageState();
}

class _CashierWorkspacePageState extends ConsumerState<CashierWorkspacePage> {
  late Future<List<StaffOrderSnapshot>> _future;
  DateTime? _lastUpdatedAt;
  bool _hasUpdates = false;
  RealtimeSubscription? _realtimeSubscription;
  StaffOrderSnapshot? _selectedOrder;
  List<_PaymentDraft> _drafts = [];
  int _selectedDraftIndex = 0;
  final Set<int> _selectedItemIds = <int>{};

  @override
  void initState() {
    super.initState();
    _future = _loadOrders();
    _connectRealtime();
  }

  Future<void> _connectRealtime() async {
    final subscription =
        await ref.read(realtimeServiceProvider).subscribeToBranch(
              surface: 'cashier',
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

  Future<void> _refreshOrders() async {
    final future = _loadOrders();
    setState(() {
      _future = future;
    });
    await _future;
  }

  Future<List<StaffOrderSnapshot>> _loadOrders() async {
    final orders = await ref.read(suiteRepositoryProvider).fetchCashierOrders();
    final cashierOrders = orders
        .where(
          (order) => order.status == 'cashier' || order.outstandingAmount > 0,
        )
        .toList();

    if (_selectedOrder != null) {
      final selectedId = _selectedOrder!.id;
      StaffOrderSnapshot? refreshed;
      for (final order in cashierOrders) {
        if (order.id == selectedId) {
          refreshed = order;
          break;
        }
      }
      if (refreshed != null) {
        _selectedOrder = refreshed;
      } else {
        _selectedOrder = null;
        _selectedItemIds.clear();
      }
    }

    if (_selectedOrder == null && cashierOrders.isNotEmpty) {
      _selectedOrder = cashierOrders.first;
      _selectedItemIds.clear();
      _resetDrafts();
    }
    if (mounted) {
      setState(() {
        _lastUpdatedAt = DateTime.now();
        _hasUpdates = false;
      });
    }
    return cashierOrders;
  }

  void _selectOrder(StaffOrderSnapshot order) {
    setState(() {
      _selectedOrder = order;
      _selectedItemIds.clear();
      _resetDrafts();
    });
  }

  double _paymentTargetAmount(StaffOrderSnapshot? order) {
    if (order == null) return 0;
    final activeUnpaidItems = order.items
        .where((item) => !item.isVoided)
        .fold<double>(0, (sum, item) => sum + item.unpaidAmount);

    if (_selectedItemIds.isEmpty) {
      if (order.outstandingAmount > 0) return order.outstandingAmount;
      if (order.status == 'cashier' && order.paymentStatus != 'paid') {
        return activeUnpaidItems > 0 ? activeUnpaidItems : order.total;
      }
      return 0;
    }

    final selectedUnpaid = order.items
        .where((item) => _selectedItemIds.contains(item.id))
        .fold<double>(0, (sum, item) => sum + item.unpaidAmount);

    if (selectedUnpaid > 0) return selectedUnpaid;

    return order.items
        .where((item) =>
            _selectedItemIds.contains(item.id) &&
            !item.isVoided &&
            !item.isPaid)
        .fold<double>(0, (sum, item) => sum + item.total);
  }

  void _resetDrafts() {
    for (final draft in _drafts) {
      draft.amountController.dispose();
    }

    final outstanding = _paymentTargetAmount(_selectedOrder);
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
    _realtimeSubscription?.close();
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

  void _toggleItemSelection(OrderItemLine item, bool selected) {
    setState(() {
      if (selected) {
        _selectedItemIds.add(item.id);
      } else {
        _selectedItemIds.remove(item.id);
      }
      _resetDrafts();
    });
  }

  Future<void> _pullReceiptForSelection() async {
    final order = _selectedOrder;
    if (order == null) return;

    try {
      final document = await ref.read(suiteRepositoryProvider).generateReceipt(
            orderId: order.id,
            itemIds: _selectedItemIds.toList(growable: false),
            scope: _selectedItemIds.isEmpty ? 'full' : 'paid',
          );
      await Printing.layoutPdf(
        name: document.filename,
        onLayout: (_) async => document.bytes,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(_selectedItemIds.isEmpty
              ? 'Print dialog opened for the full receipt'
              : 'Print dialog opened for the selected items'),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  Future<void> _reprintLastReceipt() async {
    final order = _selectedOrder;
    if (order == null) return;

    try {
      final document = await ref.read(suiteRepositoryProvider).generateReceipt(
            orderId: order.id,
            scope: 'last',
            reprint: true,
          );
      await Printing.layoutPdf(
        name: document.filename,
        onLayout: (_) async => document.bytes,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Print dialog opened for last receipt')),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
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
      await ref.read(suiteRepositoryProvider).payOrder(
            orderId: order.id,
            payments: payments,
            itemIds: _selectedItemIds.toList(growable: false),
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Payment recorded')),
      );
      setState(() {
        _selectedOrder = null;
        _selectedItemIds.clear();
        _resetDrafts();
      });
      await _refreshOrders();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  Future<void> _openCounterSale() async {
    final branchId = ref.read(currentSessionProvider)?.branchId;
    if (branchId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Select a branch before counter sale.')),
      );
      return;
    }

    try {
      final menu = await ref.read(suiteRepositoryProvider).fetchMenu();
      if (!mounted) return;

      final draft = await showModalBottomSheet<_CounterSaleDraft>(
        context: context,
        isScrollControlled: true,
        builder: (context) => _CounterSaleSheet(menu: menu),
      );

      if (draft == null || !mounted) return;

      final order = await ref.read(suiteRepositoryProvider).createOrder(
            branchId: branchId,
            orderType: 'takeaway',
            sendToCashier: true,
            customerName: draft.customerName,
            customerPhone: draft.customerPhone,
            items: draft.items,
          );

      if (!mounted) return;
      setState(() {
        _selectedOrder = order;
        _selectedItemIds.clear();
        _resetDrafts();
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Counter order #${order.id} ready to settle')),
      );
      await _refreshOrders();
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
            onRetry: _refreshOrders,
          );
        }

        final orders = snapshot.data!;
        final refreshHeader = ManualRefreshHeader(
          title: 'Cashier queue',
          subtitle: 'Manual refresh keeps settlement work stable.',
          icon: Icons.point_of_sale_outlined,
          lastUpdatedAt: _lastUpdatedAt,
          hasUpdates: _hasUpdates,
          onRefresh: _refreshOrders,
        );

        if (orders.isEmpty) {
          return RefreshIndicator(
            onRefresh: _refreshOrders,
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              children: [
                refreshHeader,
                const SizedBox(height: 16),
                _CashierHeader(
                  pendingCount: 0,
                  dueNow: 0,
                  averageTicket: 0,
                  onCounterSale: _openCounterSale,
                ),
                const SizedBox(height: 16),
                const EmptyView(
                  title: 'No cashier queue',
                  description:
                      'Orders sent from waiter and fully prepared by kitchen will appear here for settlement.',
                  icon: Icons.point_of_sale_outlined,
                ),
              ],
            ),
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
          onRefresh: _refreshOrders,
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: [
              refreshHeader,
              const SizedBox(height: 16),
	              _CashierHeader(
	                pendingCount: orders.length,
	                dueNow: orders.fold<double>(
                  0,
                  (sum, order) => sum + order.outstandingAmount,
                ),
                averageTicket: orders.isEmpty
                    ? 0
                    : orders.fold<double>(
                          0,
                          (sum, order) => sum + order.total,
	                        ) /
	                        orders.length,
	                onCounterSale: _openCounterSale,
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
                      child: _TicketPanel(
                        order: selected,
                        selectedItemIds: _selectedItemIds,
                        onItemSelectionChanged: _toggleItemSelection,
                        onReceipt: _pullReceiptForSelection,
                        onReprint: _reprintLastReceipt,
                      ),
                    ),
                    const SizedBox(width: 16),
                    SizedBox(
                      width: 360,
                      child: _PaymentPanel(
                        order: selected,
                        targetAmount: _paymentTargetAmount(selected),
                        usingItemScope: _selectedItemIds.isNotEmpty,
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
                        Expanded(
                          child: _TicketPanel(
                            order: selected,
                            selectedItemIds: _selectedItemIds,
                            onItemSelectionChanged: _toggleItemSelection,
                            onReceipt: _pullReceiptForSelection,
                            onReprint: _reprintLastReceipt,
                          ),
                        ),
                        const SizedBox(width: 16),
                        SizedBox(
                          width: 340,
                          child: _PaymentPanel(
                            order: selected,
                            targetAmount: _paymentTargetAmount(selected),
                            usingItemScope: _selectedItemIds.isNotEmpty,
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
                _TicketPanel(
                  order: selected,
                  selectedItemIds: _selectedItemIds,
                  onItemSelectionChanged: _toggleItemSelection,
                  onReceipt: _pullReceiptForSelection,
                  onReprint: _reprintLastReceipt,
                ),
                const SizedBox(height: 16),
                _PaymentPanel(
                  order: selected,
                  targetAmount: _paymentTargetAmount(selected),
                  usingItemScope: _selectedItemIds.isNotEmpty,
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
    required this.averageTicket,
    required this.onCounterSale,
  });

  final int pendingCount;
  final double dueNow;
  final double averageTicket;
  final VoidCallback onCounterSale;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'USD ');

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
      child: Wrap(
        spacing: 14,
        runSpacing: 14,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          _HeaderMetric(
            label: 'Queue',
            value: '$pendingCount',
          ),
          _HeaderMetric(
            label: 'Due now',
            value: currency.format(dueNow),
          ),
	          _HeaderMetric(
	            label: 'Avg ticket',
	            value: currency.format(averageTicket),
	          ),
	          FilledButton.icon(
	            onPressed: onCounterSale,
	            icon: const Icon(Icons.add_shopping_cart_outlined),
	            label: const Text('Counter sale'),
	          ),
	        ],
	      ),
    );
  }
}

class _CounterSaleSheet extends StatefulWidget {
  const _CounterSaleSheet({required this.menu});

  final List<MenuCategoryData> menu;

  @override
  State<_CounterSaleSheet> createState() => _CounterSaleSheetState();
}

class _CounterSaleSheetState extends State<_CounterSaleSheet> {
  final _customerNameController = TextEditingController();
  final _customerPhoneController = TextEditingController();
  final _searchController = TextEditingController();
  final Map<int, _CounterCartLine> _cart = <int, _CounterCartLine>{};
  String _search = '';

  @override
  void dispose() {
    _customerNameController.dispose();
    _customerPhoneController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  List<(MenuCategoryData, MenuProduct)> get _products {
    final query = _search.trim().toLowerCase();
    return [
      for (final category in widget.menu)
        for (final product in category.products)
          if (query.isEmpty ||
              product.name.toLowerCase().contains(query) ||
              category.name.toLowerCase().contains(query))
            (category, product),
    ];
  }

  double get _cartTotal => _cart.values.fold<double>(
        0,
        (sum, line) => sum + (line.product.price * line.quantity),
      );

  void _addProduct(MenuCategoryData category, MenuProduct product) {
    setState(() {
      final existing = _cart[product.id];
      if (existing == null) {
        _cart[product.id] = _CounterCartLine(
          category: category,
          product: product,
          quantity: 1,
        );
      } else {
        existing.quantity += 1;
      }
    });
  }

  void _changeQuantity(MenuProduct product, int delta) {
    setState(() {
      final line = _cart[product.id];
      if (line == null) return;
      line.quantity += delta;
      if (line.quantity <= 0) {
        _cart.remove(product.id);
      }
    });
  }

  void _submit() {
    if (_cart.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Add at least one item.')),
      );
      return;
    }

    Navigator.of(context).pop(
      _CounterSaleDraft(
        customerName: _customerNameController.text.trim(),
        customerPhone: _customerPhoneController.text.trim(),
        items: _cart.values
            .map((line) => {
                  'product_id': line.product.id,
                  'quantity': line.quantity,
                })
            .toList(growable: false),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'USD ');
    final products = _products;

    return SafeArea(
      child: DraggableScrollableSheet(
        expand: false,
        initialChildSize: 0.88,
        minChildSize: 0.45,
        maxChildSize: 0.96,
        builder: (context, scrollController) {
          return Padding(
            padding: const EdgeInsets.fromLTRB(18, 12, 18, 18),
            child: ListView(
              controller: scrollController,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        'Counter sale',
                        style: Theme.of(context)
                            .textTheme
                            .headlineSmall
                            ?.copyWith(fontWeight: FontWeight.w900),
                      ),
                    ),
                    IconButton(
                      tooltip: 'Close',
                      onPressed: () => Navigator.of(context).pop(),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _customerNameController,
                        decoration: const InputDecoration(
                          labelText: 'Customer name',
                          prefixIcon: Icon(Icons.person_outline),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextField(
                        controller: _customerPhoneController,
                        keyboardType: TextInputType.phone,
                        decoration: const InputDecoration(
                          labelText: 'Phone',
                          prefixIcon: Icon(Icons.phone_outlined),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _searchController,
                  onChanged: (value) => setState(() => _search = value),
                  decoration: const InputDecoration(
                    labelText: 'Search menu',
                    prefixIcon: Icon(Icons.search),
                  ),
                ),
                const SizedBox(height: 16),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    for (final entry in products)
                      _CounterProductButton(
                        category: entry.$1,
                        product: entry.$2,
                        currency: currency,
                        onTap: () => _addProduct(entry.$1, entry.$2),
                      ),
                  ],
                ),
                const SizedBox(height: 20),
                Text(
                  'Cart',
                  style: Theme.of(context)
                      .textTheme
                      .titleLarge
                      ?.copyWith(fontWeight: FontWeight.w900),
                ),
                const SizedBox(height: 10),
                if (_cart.isEmpty)
                  const Text('No items selected.')
                else
                  for (final line in _cart.values)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(line.product.name),
                      subtitle: Text(line.category.name),
                      trailing: Wrap(
                        crossAxisAlignment: WrapCrossAlignment.center,
                        spacing: 8,
                        children: [
                          IconButton.filledTonal(
                            tooltip: 'Decrease',
                            onPressed: () => _changeQuantity(line.product, -1),
                            icon: const Icon(Icons.remove),
                          ),
                          Text(
                            '${line.quantity}',
                            style: const TextStyle(fontWeight: FontWeight.w800),
                          ),
                          IconButton.filledTonal(
                            tooltip: 'Increase',
                            onPressed: () => _changeQuantity(line.product, 1),
                            icon: const Icon(Icons.add),
                          ),
                          SizedBox(
                            width: 86,
                            child: Text(
                              currency.format(line.product.price * line.quantity),
                              textAlign: TextAlign.end,
                              overflow: TextOverflow.ellipsis,
                              style:
                                  const TextStyle(fontWeight: FontWeight.w800),
                            ),
                          ),
                        ],
                      ),
                    ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        currency.format(_cartTotal),
                        style: Theme.of(context)
                            .textTheme
                            .headlineSmall
                            ?.copyWith(fontWeight: FontWeight.w900),
                      ),
                    ),
                    FilledButton.icon(
                      onPressed: _cart.isEmpty ? null : _submit,
                      icon: const Icon(Icons.point_of_sale_outlined),
                      label: const Text('Send to cashier'),
                    ),
                  ],
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _CounterProductButton extends StatelessWidget {
  const _CounterProductButton({
    required this.category,
    required this.product,
    required this.currency,
    required this.onTap,
  });

  final MenuCategoryData category;
  final MenuProduct product;
  final NumberFormat currency;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 220,
      child: OutlinedButton(
        onPressed: onTap,
        style: OutlinedButton.styleFrom(
          alignment: Alignment.centerLeft,
          padding: const EdgeInsets.all(12),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
        child: Row(
          children: [
            const Icon(Icons.add_circle_outline),
            const SizedBox(width: 10),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    product.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                  Text(
                    '${category.name} • ${currency.format(product.price)}',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CounterCartLine {
  _CounterCartLine({
    required this.category,
    required this.product,
    required this.quantity,
  });

  final MenuCategoryData category;
  final MenuProduct product;
  int quantity;
}

class _CounterSaleDraft {
  const _CounterSaleDraft({
    required this.customerName,
    required this.customerPhone,
    required this.items,
  });

  final String customerName;
  final String customerPhone;
  final List<Map<String, dynamic>> items;
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
    final currency = NumberFormat.currency(symbol: 'USD ');

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
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  order.tableName ?? 'Walk-in',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ),
                              _InfoPill(label: order.orderType.toUpperCase()),
                            ],
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
  const _TicketPanel({
    required this.order,
    required this.selectedItemIds,
    required this.onItemSelectionChanged,
    required this.onReceipt,
    required this.onReprint,
  });

  final StaffOrderSnapshot order;
  final Set<int> selectedItemIds;
  final void Function(OrderItemLine item, bool selected) onItemSelectionChanged;
  final Future<void> Function() onReceipt;
  final Future<void> Function() onReprint;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'USD ');

    return LayoutBuilder(
      builder: (context, constraints) {
        final compact = constraints.maxWidth < 560;
        final titleBlock = Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              order.tableName ?? 'Walk-in order',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: Theme.of(context)
                  .textTheme
                  .headlineSmall
                  ?.copyWith(fontWeight: FontWeight.w900),
            ),
            const SizedBox(height: 4),
            Text(
              '${order.customerName ?? 'Guest'}${order.branchName == null ? '' : ' • ${order.branchName}'}',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        );
        final actions = Wrap(
          spacing: 8,
          runSpacing: 8,
          alignment: compact ? WrapAlignment.start : WrapAlignment.end,
          children: [
            FilledButton.tonalIcon(
              onPressed: onReceipt,
              icon: const Icon(Icons.print_outlined),
              label: Text(
                  selectedItemIds.isEmpty ? 'Print receipt' : 'Print selected'),
            ),
            OutlinedButton.icon(
              onPressed: onReprint,
              icon: const Icon(Icons.replay_outlined),
              label: const Text('Reprint last'),
            ),
          ],
        );

        return Card(
          child: Padding(
            padding: EdgeInsets.all(compact ? 14 : 18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (compact)
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      titleBlock,
                      const SizedBox(height: 12),
                      _TicketStatusBadge(
                          label: order.paymentStatus.toUpperCase()),
                      const SizedBox(height: 10),
                      actions,
                    ],
                  )
                else
                  Row(
                    children: [
                      Expanded(child: titleBlock),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          _TicketStatusBadge(
                              label: order.paymentStatus.toUpperCase()),
                          const SizedBox(height: 8),
                          actions,
                        ],
                      ),
                    ],
                  ),
                const SizedBox(height: 18),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    _InfoPill(label: 'Type: ${order.orderType}'),
                    _InfoPill(
                        label: 'Paid: ${currency.format(order.paidAmount)}'),
                    _InfoPill(
                        label:
                            'Due: ${currency.format(order.outstandingAmount)}'),
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
                        Checkbox(
                          value: selectedItemIds.contains(item.id),
                          onChanged: item.isVoided
                              ? null
                              : (value) =>
                                  onItemSelectionChanged(item, value ?? false),
                        ),
                        const SizedBox(width: 6),
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
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                                style: Theme.of(context)
                                    .textTheme
                                    .titleSmall
                                    ?.copyWith(fontWeight: FontWeight.w800),
                              ),
                              if (item.modifiers.isNotEmpty) ...[
                                const SizedBox(height: 4),
                                Text(
                                  item.modifiers.join(' • '),
                                  style:
                                      const TextStyle(color: Color(0xFF6B7280)),
                                ),
                              ],
                              if ((item.itemNote ?? '').isNotEmpty) ...[
                                const SizedBox(height: 4),
                                Text(
                                  item.itemNote!,
                                  style:
                                      const TextStyle(color: Color(0xFF8B5E34)),
                                ),
                              ],
                              const SizedBox(height: 6),
                              Wrap(
                                spacing: 8,
                                runSpacing: 8,
                                children: [
                                  _InfoPill(label: item.kitchenStatusLabel),
                                  _InfoPill(
                                    label:
                                        'Payment ${(item.paymentStatus ?? 'unpaid').toUpperCase()}',
                                  ),
                                  if (item.paidAmount > 0)
                                    _InfoPill(
                                      label:
                                          'Paid ${currency.format(item.paidAmount)}',
                                    ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        Text(
                          currency.format(item.total),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
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
                      style:
                          Theme.of(context).textTheme.headlineSmall?.copyWith(
                                fontWeight: FontWeight.w900,
                              ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}

class _PaymentPanel extends StatelessWidget {
  const _PaymentPanel({
    required this.order,
    required this.targetAmount,
    required this.usingItemScope,
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
  final double targetAmount;
  final bool usingItemScope;
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
    final currency = NumberFormat.currency(symbol: 'USD ');
    final draftedTotal = drafts.fold<double>(
      0,
      (sum, draft) =>
          sum + (double.tryParse(draft.amountController.text.trim()) ?? 0),
    );
    final remaining = (targetAmount - draftedTotal).clamp(0, double.infinity);
    final changeDue = (draftedTotal - targetAmount).clamp(0, double.infinity);

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
              '${usingItemScope ? 'Selected items' : 'Outstanding'} ${currency.format(targetAmount)}',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                    color: const Color(0xFF8A4316),
                  ),
            ),
            const SizedBox(height: 14),
            LayoutBuilder(
              builder: (context, constraints) {
                final maxWidth = constraints.maxWidth;
                final columns = maxWidth >= 520 ? 3 : (maxWidth >= 320 ? 2 : 1);
                const spacing = 10.0;
                final cardWidth =
                    (maxWidth - spacing * (columns - 1)) / columns;
                final cards = [
                  _PaymentSummaryCard(
                    label: 'Drafted',
                    value: currency.format(draftedTotal),
                    tone: const Color(0xFF0F766E),
                  ),
                  _PaymentSummaryCard(
                    label: 'Remaining',
                    value: currency.format(remaining),
                    tone: const Color(0xFFE86C2F),
                  ),
                  _PaymentSummaryCard(
                    label: 'Change',
                    value: currency.format(changeDue),
                    tone: const Color(0xFF1D4ED8),
                  ),
                ];

                return Wrap(
                  spacing: spacing,
                  runSpacing: spacing,
                  children: [
                    for (final card in cards)
                      SizedBox(width: cardWidth, child: card),
                  ],
                );
              },
            ),
            const SizedBox(height: 16),
            Text(
              'Tender stack',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w800,
                  ),
            ),
            const SizedBox(height: 10),
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
                  onTap: () => onQuickAmount(targetAmount),
                ),
                _QuickPayButton(
                  label: '50%',
                  onTap: () => onQuickAmount(targetAmount / 2),
                ),
                _QuickPayButton(
                  label: '100',
                  onTap: () => onQuickAmount(100),
                ),
                _QuickPayButton(
                  label: '200',
                  onTap: () => onQuickAmount(200),
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
                label: Text(
                  remaining > 0 ? 'Record payment' : 'Settle order',
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PaymentSummaryCard extends StatelessWidget {
  const _PaymentSummaryCard({
    required this.label,
    required this.value,
    required this.tone,
  });

  final String label;
  final String value;
  final Color tone;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: tone.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: tone.withValues(alpha: 0.18)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: const Color(0xFF6B7280),
                ),
          ),
          const SizedBox(height: 6),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
                  fontWeight: FontWeight.w800,
                  color: tone,
                ),
          ),
        ],
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
            color: selected ? const Color(0xFFE86C2F) : Colors.transparent,
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
                      DropdownMenuItem(value: 'wallet', child: Text('Wallet')),
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
