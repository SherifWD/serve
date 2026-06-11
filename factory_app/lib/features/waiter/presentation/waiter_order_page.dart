import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/models/app_models.dart';
import '../../../core/widgets/branded_image.dart';
import '../../../core/widgets/state_views.dart';
import '../../suite/data/suite_repository.dart';

class WaiterOrderPage extends ConsumerStatefulWidget {
  const WaiterOrderPage({
    required this.table,
    super.key,
  });

  final TableOverview table;

  @override
  ConsumerState<WaiterOrderPage> createState() => _WaiterOrderPageState();
}

class _WaiterOrderPageState extends ConsumerState<WaiterOrderPage> {
  final _customerNameController = TextEditingController();
  final _customerPhoneController = TextEditingController();
  late Future<_WaiterOrderBundle> _future;
  final List<int> _recentProductIds = <int>[];

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  @override
  void dispose() {
    _customerNameController.dispose();
    _customerPhoneController.dispose();
    super.dispose();
  }

  Future<_WaiterOrderBundle> _load() async {
    final repo = ref.read(suiteRepositoryProvider);
    final results = await Future.wait<dynamic>([
      repo.fetchTableDetails(widget.table.id),
      repo.fetchMenu(),
      repo.fetchModifiers(),
      repo.fetchTables(),
    ]);

    final bundle = _WaiterOrderBundle(
      details: results[0] as TableDetails,
      menu: results[1] as List<MenuCategoryData>,
      modifiers: results[2] as List<ModifierData>,
      tables: results[3] as List<TableOverview>,
    );

    _customerNameController.text =
        bundle.details.customerName ?? _customerNameController.text;
    _customerPhoneController.text =
        bundle.details.customerPhone ?? _customerPhoneController.text;
    return bundle;
  }

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'USD ');

    return Scaffold(
      appBar: AppBar(
        title: Text('Table ${widget.table.name}'),
      ),
      body: FutureBuilder<_WaiterOrderBundle>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const LoadingView(label: 'Loading table...');
          }
          if (snapshot.hasError) {
            return ErrorView(
              message: snapshot.error.toString(),
              onRetry: () => setState(() {
                _future = _load();
              }),
            );
          }

          final bundle = snapshot.data!;
          final canSendToKitchen = bundle.details.orderId != null &&
              bundle.details.items.any((item) => item.canSendToKitchen);
          final duplicateCandidate = _lastDuplicableItem(bundle.details.items);
          final wide = MediaQuery.of(context).size.width > 1120;

          return RefreshIndicator(
            onRefresh: () async {
              setState(() {
                _future = _load();
              });
              await _future;
            },
            child: ListView(
              padding: const EdgeInsets.all(20),
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(18),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                'Active order',
                                style: Theme.of(context)
                                    .textTheme
                                    .titleLarge
                                    ?.copyWith(fontWeight: FontWeight.w800),
                              ),
                            ),
                            Text(
                              currency.format(bundle.details.orderTotal),
                              style: Theme.of(context)
                                  .textTheme
                                  .titleLarge
                                  ?.copyWith(fontWeight: FontWeight.w800),
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
                                  hintText: 'Walk-in guest',
                                ),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: TextField(
                                controller: _customerPhoneController,
                                keyboardType: TextInputType.phone,
                                decoration: const InputDecoration(
                                  labelText: 'Customer phone',
                                  hintText: '01xxxxxxxxx',
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        Wrap(
                          spacing: 12,
                          runSpacing: 12,
                          children: [
                            FilledButton.icon(
                              onPressed: !canSendToKitchen
                                  ? null
                                  : () => _runAction(
                                        () => ref
                                            .read(suiteRepositoryProvider)
                                            .sendToKds(bundle.details.orderId!),
                                        'Order sent to kitchen',
                                      ),
                              icon: const Icon(Icons.send_outlined),
                              label: const Text('Send to kitchen'),
                            ),
                            OutlinedButton.icon(
                              onPressed: bundle.details.orderId == null
                                  ? null
                                  : () => _runAction(
                                        () => ref
                                            .read(suiteRepositoryProvider)
                                            .sendToCashier(
                                                bundle.details.orderId!),
                                        'Order sent to cashier',
                                      ),
                              icon: const Icon(Icons.point_of_sale_outlined),
                              label: const Text('Send to cashier'),
                            ),
                            OutlinedButton.icon(
                              onPressed: bundle.details.orderId == null
                                  ? null
                                  : () => _showMoveTableDialog(bundle.tables),
                              icon: const Icon(Icons.swap_horiz),
                              label: const Text('Move table'),
                            ),
                            OutlinedButton.icon(
                              onPressed: duplicateCandidate == null
                                  ? null
                                  : () => _duplicateLastItem(
                                        duplicateCandidate,
                                      ),
                              icon: const Icon(Icons.content_copy_outlined),
                              label: const Text('Duplicate last'),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                if (wide)
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: _OrderItemsCard(
                          items: bundle.details.items,
                          onDecrease: (item) => _handleDecrease(item),
                          onRemove: (item) => _handleRemove(item),
                          onRefund: (item) => _handleRefund(item),
                          onReturnToKitchen: (item) => _handleReturn(item),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: _MenuComposerCard(
                          menu: bundle.menu,
                          modifiers: bundle.modifiers,
                          recentProductIds: _recentProductIds,
                          onAdd: (product, category) => _showAddProductSheet(
                              product, category, bundle.modifiers),
                        ),
                      ),
                    ],
                  )
                else ...[
                  _OrderItemsCard(
                    items: bundle.details.items,
                    onDecrease: (item) => _handleDecrease(item),
                    onRemove: (item) => _handleRemove(item),
                    onRefund: (item) => _handleRefund(item),
                    onReturnToKitchen: (item) => _handleReturn(item),
                  ),
                  const SizedBox(height: 16),
                  _MenuComposerCard(
                    menu: bundle.menu,
                    modifiers: bundle.modifiers,
                    recentProductIds: _recentProductIds,
                    onAdd: (product, category) => _showAddProductSheet(
                        product, category, bundle.modifiers),
                  ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }

  Future<void> _showAddProductSheet(
    MenuProduct product,
    MenuCategoryData category,
    List<ModifierData> modifiers,
  ) async {
    final draft = await showModalBottomSheet<_AddProductDraft>(
      context: context,
      isScrollControlled: true,
      builder: (context) => _AddProductSheet(
        product: product,
        category: category,
        modifiers: modifiers,
      ),
    );

    if (draft == null || !mounted) return;

    try {
      await ref.read(suiteRepositoryProvider).createOrder(
        tableId: widget.table.id,
        customerName: _customerNameController.text.trim(),
        customerPhone: _customerPhoneController.text.trim(),
        items: [draft.toPayload(product.id)],
      );

      if (!mounted) return;
      _rememberRecentProduct(product.id);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('${product.name} added to the order')),
      );
      setState(() {
        _future = _load();
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  OrderItemLine? _lastDuplicableItem(List<OrderItemLine> items) {
    for (final item in items.reversed) {
      if (!_isHistoricalOrderItem(item) && item.productId != null) {
        return item;
      }
    }
    return null;
  }

  void _rememberRecentProduct(int productId) {
    _recentProductIds.remove(productId);
    _recentProductIds.insert(0, productId);
    if (_recentProductIds.length > 12) {
      _recentProductIds.removeRange(12, _recentProductIds.length);
    }
  }

  Future<void> _duplicateLastItem(OrderItemLine item) async {
    final productId = item.productId;
    if (productId == null) {
      return;
    }

    await _runAction(
      () async {
        await ref.read(suiteRepositoryProvider).createOrder(
          tableId: widget.table.id,
          customerName: _customerNameController.text.trim(),
          customerPhone: _customerPhoneController.text.trim(),
          items: [
            {
              'product_id': productId,
              'quantity': 1,
              if ((item.itemNote ?? '').isNotEmpty) 'note': item.itemNote,
            }
          ],
        );
        _rememberRecentProduct(productId);
      },
      '${item.name} duplicated',
    );
  }

  Future<void> _handleDecrease(OrderItemLine item) async {
    if (!item.canReduceQuantity) {
      return;
    }

    await _runAction(
      () => ref.read(suiteRepositoryProvider).changeOrderItem(
            orderItemId: item.id,
            quantity: item.quantity - 1,
            note: 'Quantity reduced by floor staff',
          ),
      '${item.name} quantity updated',
    );
  }

  Future<void> _handleRemove(OrderItemLine item) async {
    if (!item.canRemoveBeforeKitchen) {
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Remove ${item.name}?'),
        content: const Text(
          'This only removes an item that has not been sent to kitchen.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Remove'),
          ),
        ],
      ),
    );

    if (confirmed != true) {
      return;
    }

    await _runAction(
      () => ref.read(suiteRepositoryProvider).removeUnsentOrderItem(
            orderItemId: item.id,
          ),
      '${item.name} removed',
    );
  }

  Future<void> _handleRefund(OrderItemLine item) async {
    final reason = await _promptRefundReason(item);
    if (reason == null || reason.isEmpty) {
      return;
    }

    await _runAction(
      () => ref.read(suiteRepositoryProvider).refundOrderItem(
            orderItemId: item.id,
            note: reason,
          ),
      '${item.name} refunded',
    );
  }

  Future<void> _handleReturn(OrderItemLine item) async {
    final reason = await _promptReturnReason(item);
    if (reason == null || reason.isEmpty) {
      return;
    }

    await _runAction(
      () => ref.read(suiteRepositoryProvider).returnOrderItemToKitchen(
            orderItemId: item.id,
            note: reason,
          ),
      '${item.name} returned to kitchen',
    );
  }

  Future<String?> _promptReturnReason(OrderItemLine item) async {
    return showDialog<String>(
      context: context,
      builder: (_) => _ItemReasonDialog(
        title: 'Return ${item.name}',
        label: 'Reason',
        hint: 'Example: customer requested a remake',
        confirmLabel: 'Return',
      ),
    );
  }

  Future<String?> _promptRefundReason(OrderItemLine item) async {
    return showDialog<String>(
      context: context,
      builder: (_) => _ItemReasonDialog(
        title: 'Refund ${item.name}',
        label: 'Refund reason',
        hint: 'Example: wrong item, customer complaint, manager approved',
        confirmLabel: 'Refund',
      ),
    );
  }

  Future<void> _showMoveTableDialog(List<TableOverview> tables) async {
    final candidates = tables
        .where((table) => table.id != widget.table.id && !table.isOccupied)
        .toList();
    int? selectedId = candidates.isNotEmpty ? candidates.first.id : null;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Move table'),
          content: candidates.isEmpty
              ? const Text('No empty tables are available right now.')
              : StatefulBuilder(
                  builder: (context, setDialogState) {
                    return DropdownButtonFormField<int>(
                      value: selectedId,
                      items: [
                        for (final table in candidates)
                          DropdownMenuItem<int>(
                            value: table.id,
                            child: Text('${table.name} • ${table.seats} seats'),
                          ),
                      ],
                      onChanged: (value) =>
                          setDialogState(() => selectedId = value),
                      decoration:
                          const InputDecoration(labelText: 'Target table'),
                    );
                  },
                ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: candidates.isEmpty
                  ? null
                  : () => Navigator.of(context).pop(true),
              child: const Text('Move'),
            ),
          ],
        );
      },
    );

    if (confirmed == true && selectedId != null) {
      await _runAction(
        () => ref.read(suiteRepositoryProvider).moveTable(
              fromTableId: widget.table.id,
              toTableId: selectedId!,
            ),
        'Table moved successfully',
      );
    }
  }

  Future<void> _runAction(
      Future<void> Function() action, String successMessage) async {
    try {
      await action();
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(successMessage)));
      setState(() {
        _future = _load();
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }
}

class _ItemReasonDialog extends StatefulWidget {
  const _ItemReasonDialog({
    required this.title,
    required this.label,
    required this.hint,
    required this.confirmLabel,
  });

  final String title;
  final String label;
  final String hint;
  final String confirmLabel;

  @override
  State<_ItemReasonDialog> createState() => _ItemReasonDialogState();
}

class _ItemReasonDialogState extends State<_ItemReasonDialog> {
  final _formKey = GlobalKey<FormState>();
  final _controller = TextEditingController();

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _submit() {
    if (!(_formKey.currentState?.validate() ?? false)) {
      return;
    }

    Navigator.of(context).pop(_controller.text.trim());
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Text(widget.title),
      content: Form(
        key: _formKey,
        child: TextFormField(
          controller: _controller,
          autofocus: true,
          maxLines: 3,
          textInputAction: TextInputAction.done,
          decoration: InputDecoration(
            labelText: widget.label,
            hintText: widget.hint,
          ),
          validator: (value) => (value == null || value.trim().isEmpty)
              ? '${widget.label} is required'
              : null,
          onFieldSubmitted: (_) => _submit(),
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: _submit,
          child: Text(widget.confirmLabel),
        ),
      ],
    );
  }
}

class _AddProductSheet extends StatefulWidget {
  const _AddProductSheet({
    required this.product,
    required this.category,
    required this.modifiers,
  });

  final MenuProduct product;
  final MenuCategoryData category;
  final List<ModifierData> modifiers;

  @override
  State<_AddProductSheet> createState() => _AddProductSheetState();
}

class _AddProductSheetState extends State<_AddProductSheet> {
  final _noteController = TextEditingController();
  final _selectedChoices = <int, int>{};
  final _selectedModifiers = <int>{};
  static const _commonNotes = [
    'No salt',
    'Extra sauce',
    'Allergy',
    'No onions',
    'Well done',
    'Serve first',
  ];
  int _quantity = 1;

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  void _submit() {
    Navigator.of(context).pop(
      _AddProductDraft(
        quantity: _quantity,
        note: _noteController.text.trim(),
        choiceIds: _selectedChoices.values.toList(growable: false),
        modifierIds: _selectedModifiers.toList(growable: false),
      ),
    );
  }

  void _appendCommonNote(String note) {
    final current = _noteController.text.trim();
    _noteController.text = current.isEmpty ? note : '$current, $note';
    _noteController.selection = TextSelection.fromPosition(
      TextPosition(offset: _noteController.text.length),
    );
  }

  @override
  Widget build(BuildContext context) {
    final product = widget.product;
    final category = widget.category;
    final modifiers = widget.modifiers
        .where((modifier) => modifier.appliesToCategory(category.id))
        .toList(growable: false);

    return Padding(
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(24),
              child: SizedBox(
                height: 190,
                width: double.infinity,
                child: BrandedImage(
                  label: product.name,
                  imageUrl: product.imageUrl,
                  kind: BrandedImageKind.dish,
                  overlay: const LinearGradient(
                    colors: [Color(0x00000000), Color(0x44000000)],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  ),
                ),
              ),
            ),
            const SizedBox(height: 18),
            Text(
              product.name,
              style: Theme.of(context)
                  .textTheme
                  .titleLarge
                  ?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 8),
            Text(
              'USD ${product.price.toStringAsFixed(2)}',
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 18),
            Row(
              children: [
                const Text('Quantity'),
                const Spacer(),
                IconButton(
                  onPressed: _quantity > 1
                      ? () => setState(() => _quantity -= 1)
                      : null,
                  icon: const Icon(Icons.remove_circle_outline),
                ),
                Text('$_quantity'),
                IconButton(
                  onPressed: () => setState(() => _quantity += 1),
                  icon: const Icon(Icons.add_circle_outline),
                ),
              ],
            ),
            TextField(
              controller: _noteController,
              decoration: const InputDecoration(
                labelText: 'Order note',
                hintText: 'No onions, extra napkins, serve first...',
              ),
            ),
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final note in _commonNotes)
                  ActionChip(
                    avatar: const Icon(Icons.add, size: 16),
                    label: Text(note),
                    onPressed: () => _appendCommonNote(note),
                  ),
              ],
            ),
            if (category.questions.isNotEmpty) ...[
              const SizedBox(height: 20),
              for (final question in category.questions) ...[
                Text(
                  question.question,
                  style: Theme.of(context)
                      .textTheme
                      .titleMedium
                      ?.copyWith(fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    for (final choice in question.choices)
                      ChoiceChip(
                        label: Text(choice.label),
                        selected: _selectedChoices[question.id] == choice.id,
                        onSelected: (_) {
                          setState(() {
                            _selectedChoices[question.id] = choice.id;
                          });
                        },
                      ),
                  ],
                ),
                const SizedBox(height: 16),
              ],
            ],
            if (modifiers.isNotEmpty) ...[
              Text(
                'Modifiers',
                style: Theme.of(context)
                    .textTheme
                    .titleMedium
                    ?.copyWith(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 10),
              Wrap(
                spacing: 10,
                runSpacing: 10,
                children: [
                  for (final modifier in modifiers)
                    FilterChip(
                      label: Text(
                        '${modifier.name} (+${modifier.price.toStringAsFixed(0)})',
                      ),
                      selected: _selectedModifiers.contains(modifier.id),
                      onSelected: (selected) {
                        setState(() {
                          if (selected) {
                            _selectedModifiers.add(modifier.id);
                          } else {
                            _selectedModifiers.remove(modifier.id);
                          }
                        });
                      },
                    ),
                ],
              ),
            ],
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: _submit,
                child: const Text('Add to order'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _AddProductDraft {
  const _AddProductDraft({
    required this.quantity,
    required this.note,
    required this.choiceIds,
    required this.modifierIds,
  });

  final int quantity;
  final String note;
  final List<int> choiceIds;
  final List<int> modifierIds;

  Map<String, dynamic> toPayload(int productId) {
    return {
      'product_id': productId,
      'quantity': quantity,
      if (note.isNotEmpty) 'note': note,
      if (choiceIds.isNotEmpty)
        'answers': choiceIds
            .map((choiceId) => {'choice_id': choiceId})
            .toList(growable: false),
      if (modifierIds.isNotEmpty)
        'modifiers': modifierIds
            .map((modifierId) => {'modifier_id': modifierId})
            .toList(growable: false),
    };
  }
}

class _OrderItemsCard extends StatelessWidget {
  const _OrderItemsCard({
    required this.items,
    required this.onDecrease,
    required this.onRemove,
    required this.onRefund,
    required this.onReturnToKitchen,
  });

  final List<OrderItemLine> items;
  final ValueChanged<OrderItemLine> onDecrease;
  final ValueChanged<OrderItemLine> onRemove;
  final ValueChanged<OrderItemLine> onRefund;
  final ValueChanged<OrderItemLine> onReturnToKitchen;

  @override
  Widget build(BuildContext context) {
    final historicalItems =
        items.where(_isHistoricalOrderItem).toList(growable: false);
    final activeItems = items
        .where((item) => !_isHistoricalOrderItem(item))
        .toList(growable: false);

    if (items.isEmpty) {
      return const Card(
        child: SizedBox(
          height: 260,
          child: EmptyView(
            title: 'No items yet',
            description: 'Add menu items to open the order.',
            icon: Icons.table_restaurant_outlined,
          ),
        ),
      );
    }

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            LayoutBuilder(
              builder: (context, constraints) {
                final title = Text(
                  'Order items',
                  style: Theme.of(context)
                      .textTheme
                      .titleLarge
                      ?.copyWith(fontWeight: FontWeight.w800),
                );
                final historyButton = historicalItems.isEmpty
                    ? null
                    : OutlinedButton.icon(
                        onPressed: () =>
                            _showHistoricalItems(context, historicalItems),
                        icon: const Icon(Icons.history_outlined),
                        label: Text(
                          'Returned/canceled (${historicalItems.length})',
                        ),
                      );

                if (historyButton == null) return title;
                if (constraints.maxWidth < 430) {
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      title,
                      const SizedBox(height: 10),
                      historyButton,
                    ],
                  );
                }

                return Row(
                  children: [
                    Expanded(child: title),
                    historyButton,
                  ],
                );
              },
            ),
            const SizedBox(height: 14),
            if (activeItems.isEmpty)
              const SizedBox(
                height: 220,
                child: EmptyView(
                  title: 'No active items',
                  description:
                      'Returned or canceled items are available from the history button.',
                  icon: Icons.receipt_long_outlined,
                ),
              )
            else
              for (final item in activeItems)
                Padding(
                  padding: const EdgeInsets.only(bottom: 14),
                  child: _OrderItemTile(
                    item: item,
                    onDecrease: onDecrease,
                    onRemove: onRemove,
                    onRefund: onRefund,
                    onReturnToKitchen: onReturnToKitchen,
                  ),
                ),
          ],
        ),
      ),
    );
  }

  void _showHistoricalItems(
    BuildContext context,
    List<OrderItemLine> historicalItems,
  ) {
    showDialog<void>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Returned or canceled items'),
          content: ConstrainedBox(
            constraints: const BoxConstraints(
              maxWidth: 560,
              maxHeight: 520,
            ),
            child: ListView.separated(
              shrinkWrap: true,
              itemCount: historicalItems.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                return _OrderItemTile(
                  item: historicalItems[index],
                  onDecrease: onDecrease,
                  onRemove: onRemove,
                  onRefund: onRefund,
                  onReturnToKitchen: onReturnToKitchen,
                  closeBeforeAction: true,
                );
              },
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Close'),
            ),
          ],
        );
      },
    );
  }
}

class _OrderItemTile extends StatelessWidget {
  const _OrderItemTile({
    required this.item,
    required this.onDecrease,
    required this.onRemove,
    required this.onRefund,
    required this.onReturnToKitchen,
    this.closeBeforeAction = false,
  });

  final OrderItemLine item;
  final ValueChanged<OrderItemLine> onDecrease;
  final ValueChanged<OrderItemLine> onRemove;
  final ValueChanged<OrderItemLine> onRefund;
  final ValueChanged<OrderItemLine> onReturnToKitchen;
  final bool closeBeforeAction;

  void _runAction(
    BuildContext context,
    ValueChanged<OrderItemLine> action,
  ) {
    if (closeBeforeAction) {
      Navigator.of(context).pop();
    }
    action(item);
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        children: [
          if (item.imageUrl != null) ...[
            ClipRRect(
              borderRadius: BorderRadius.circular(18),
              child: SizedBox(
                width: 72,
                height: 72,
                child: BrandedImage(
                  label: item.name,
                  imageUrl: item.imageUrl,
                  kind: BrandedImageKind.dish,
                ),
              ),
            ),
            const SizedBox(width: 12),
          ],
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${item.quantity}x ${item.name}',
                  style: Theme.of(context)
                      .textTheme
                      .titleMedium
                      ?.copyWith(fontWeight: FontWeight.w700),
                ),
                if (item.itemNote != null && item.itemNote!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(item.itemNote!),
                ],
                if (item.changeNote != null && item.changeNote!.isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Text(item.changeNote!),
                ],
                if (item.modifiers.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final modifier in item.modifiers)
                        Chip(label: Text(modifier)),
                    ],
                  ),
                ],
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _OrderLineStatusChip(
                      label: item.kitchenStatusLabel,
                      color: _kitchenStatusColor(item.kdsStatus ?? item.status),
                    ),
                    _OrderLineStatusChip(
                      label:
                          'Payment ${(item.paymentStatus ?? 'unpaid').toUpperCase()}',
                      color: item.isPaid
                          ? const Color(0xFF059669)
                          : const Color(0xFF6B7280),
                    ),
                  ],
                ),
              ],
            ),
          ),
          Column(
            children: [
              IconButton(
                tooltip: 'Reduce quantity',
                onPressed: item.canReduceQuantity
                    ? () => _runAction(context, onDecrease)
                    : null,
                icon: const Icon(Icons.remove_circle_outline),
              ),
              IconButton(
                tooltip: 'Remove unsent item',
                onPressed: item.canRemoveBeforeKitchen
                    ? () => _runAction(context, onRemove)
                    : null,
                icon: const Icon(Icons.delete_outline),
              ),
              IconButton(
                tooltip: 'Refund item',
                onPressed:
                    item.canRefund ? () => _runAction(context, onRefund) : null,
                icon: const Icon(Icons.restart_alt_outlined),
              ),
              IconButton(
                tooltip: 'Return to kitchen',
                onPressed: item.canReturnToKitchen
                    ? () => _runAction(context, onReturnToKitchen)
                    : null,
                icon: const Icon(Icons.soup_kitchen_outlined),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

bool _isHistoricalOrderItem(OrderItemLine item) {
  final kitchenStatus = item.kdsStatus ?? item.status;
  return item.isVoided || kitchenStatus == 'returned';
}

class _OrderLineStatusChip extends StatelessWidget {
  const _OrderLineStatusChip({
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
        color: color.withValues(alpha: 0.10),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withValues(alpha: 0.20)),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w800,
          fontSize: 12,
        ),
      ),
    );
  }
}

Color _kitchenStatusColor(String? status) {
  switch (status) {
    case 'queued':
      return const Color(0xFFF59E0B);
    case 'preparing':
      return const Color(0xFF0284C7);
    case 'ready':
      return const Color(0xFF059669);
    case 'served':
      return const Color(0xFF2563EB);
    case 'returned':
      return const Color(0xFFDC2626);
    case 'refunded':
    case 'canceled':
    case 'cancelled':
      return const Color(0xFF6B7280);
    default:
      return const Color(0xFF64748B);
  }
}

class _MenuComposerCard extends StatefulWidget {
  const _MenuComposerCard({
    required this.menu,
    required this.modifiers,
    required this.recentProductIds,
    required this.onAdd,
  });

  final List<MenuCategoryData> menu;
  final List<ModifierData> modifiers;
  final List<int> recentProductIds;
  final void Function(MenuProduct product, MenuCategoryData category) onAdd;

  @override
  State<_MenuComposerCard> createState() => _MenuComposerCardState();
}

class _MenuComposerCardState extends State<_MenuComposerCard> {
  final _searchController = TextEditingController();
  int? _openCategoryId;
  String _search = '';

  int _modifierCountFor(MenuCategoryData category) {
    return widget.modifiers
        .where((modifier) => modifier.appliesToCategory(category.id))
        .length;
  }

  List<(MenuCategoryData, MenuProduct)> get _allProducts {
    return [
      for (final category in widget.menu)
        for (final product in category.products) (category, product),
    ];
  }

  List<(MenuCategoryData, MenuProduct)> get _quickProducts {
    final all = _allProducts;
    final byId = {
      for (final entry in all) entry.$2.id: entry,
    };
    final result = <(MenuCategoryData, MenuProduct)>[];
    final seen = <int>{};

    for (final productId in widget.recentProductIds) {
      final entry = byId[productId];
      if (entry == null || seen.contains(productId)) continue;
      result.add(entry);
      seen.add(productId);
    }

    for (final entry in all) {
      if (seen.contains(entry.$2.id)) continue;
      result.add(entry);
      seen.add(entry.$2.id);
      if (result.length >= 10) break;
    }

    return result;
  }

  List<MenuCategoryData> get _visibleMenu {
    final query = _search.trim().toLowerCase();
    if (query.isEmpty) return widget.menu;

    return widget.menu
        .map((category) {
          final categoryMatches = category.name.toLowerCase().contains(query);
          final products = category.products
              .where((product) =>
                  categoryMatches || product.name.toLowerCase().contains(query))
              .toList(growable: false);

          return MenuCategoryData(
            id: category.id,
            name: category.name,
            products: products,
            questions: category.questions,
          );
        })
        .where((category) => category.products.isNotEmpty)
        .toList(growable: false);
  }

  @override
  void initState() {
    super.initState();
    _openCategoryId = widget.menu.isEmpty ? null : widget.menu.first.id;
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  void didUpdateWidget(covariant _MenuComposerCard oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.menu.any((category) => category.id == _openCategoryId)) {
      return;
    }
    _openCategoryId = widget.menu.isEmpty ? null : widget.menu.first.id;
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Menu',
              style: Theme.of(context)
                  .textTheme
                  .titleLarge
                  ?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 4),
            const Text(
              'Open one category at a time.',
            ),
            const SizedBox(height: 14),
            TextField(
              controller: _searchController,
              onChanged: (value) => setState(() => _search = value),
              decoration: const InputDecoration(
                labelText: 'Search products',
                prefixIcon: Icon(Icons.search_outlined),
              ),
            ),
            const SizedBox(height: 14),
            if (_quickProducts.isNotEmpty) ...[
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  for (final entry in _quickProducts)
                    ActionChip(
                      avatar: const Icon(Icons.add_circle_outline, size: 18),
                      label: Text(entry.$2.name),
                      onPressed: () => widget.onAdd(entry.$2, entry.$1),
                    ),
                ],
              ),
              const SizedBox(height: 14),
            ],
            if (_visibleMenu.isEmpty)
              const SizedBox(
                height: 180,
                child: EmptyView(
                  title: 'No products found',
                  description: 'Try another product or category name.',
                  icon: Icons.search_off_outlined,
                ),
              )
            else
              for (final category in _visibleMenu)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Material(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(12),
                        child: InkWell(
                          borderRadius: BorderRadius.circular(12),
                          onTap: () {
                            setState(() {
                              _openCategoryId = _openCategoryId == category.id
                                  ? null
                                  : category.id;
                            });
                          },
                          child: Padding(
                            padding: const EdgeInsets.all(14),
                            child: Row(
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        category.name,
                                        style: Theme.of(context)
                                            .textTheme
                                            .titleMedium
                                            ?.copyWith(
                                                fontWeight: FontWeight.w800),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        '${category.products.length} products • '
                                        '${category.questions.length} questions • '
                                        '${_modifierCountFor(category)} modifiers',
                                        style: const TextStyle(
                                          color: Color(0xFF64748B),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                                Icon(
                                  _openCategoryId == category.id
                                      ? Icons.keyboard_arrow_up
                                      : Icons.keyboard_arrow_down,
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      if (_openCategoryId == category.id) ...[
                        const SizedBox(height: 12),
                        LayoutBuilder(
                          builder: (context, constraints) {
                            final width = constraints.maxWidth;
                            final crossAxisCount = width > 760
                                ? 3
                                : width > 520
                                    ? 2
                                    : 1;

                            return GridView.builder(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              itemCount: category.products.length,
                              gridDelegate:
                                  SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: crossAxisCount,
                                crossAxisSpacing: 12,
                                mainAxisSpacing: 12,
                                childAspectRatio: 0.78,
                              ),
                              itemBuilder: (context, index) {
                                final product = category.products[index];
                                return _ProductChooserCard(
                                  product: product,
                                  onTap: () => widget.onAdd(product, category),
                                );
                              },
                            );
                          },
                        ),
                      ],
                    ],
                  ),
                ),
          ],
        ),
      ),
    );
  }
}

class _WaiterOrderBundle {
  const _WaiterOrderBundle({
    required this.details,
    required this.menu,
    required this.modifiers,
    required this.tables,
  });

  final TableDetails details;
  final List<MenuCategoryData> menu;
  final List<ModifierData> modifiers;
  final List<TableOverview> tables;
}

class _ProductChooserCard extends StatelessWidget {
  const _ProductChooserCard({
    required this.product,
    required this.onTap,
  });

  final MenuProduct product;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: const Color(0xFFF8FAFC),
      borderRadius: BorderRadius.circular(24),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: ClipRRect(
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(24),
                ),
                child: BrandedImage(
                  label: product.name,
                  imageUrl: product.imageUrl,
                  kind: BrandedImageKind.dish,
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    product.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          'USD ${product.price.toStringAsFixed(0)}',
                          style: Theme.of(context)
                              .textTheme
                              .titleSmall
                              ?.copyWith(fontWeight: FontWeight.w900),
                        ),
                      ),
                      FilledButton.tonal(
                        onPressed: onTap,
                        child: const Text('Add'),
                      ),
                    ],
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
