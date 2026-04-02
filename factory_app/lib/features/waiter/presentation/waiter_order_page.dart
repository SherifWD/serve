import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/models/app_models.dart';
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
    final currency = NumberFormat.currency(symbol: 'EGP ');

    return Scaffold(
      appBar: AppBar(
        title: Text('Table ${widget.table.name}'),
      ),
      body: FutureBuilder<_WaiterOrderBundle>(
        future: _future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return const LoadingView(label: 'Loading table workspace...');
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
                                  hintText: 'Walk-in / loyalty guest',
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
                              onPressed: bundle.details.orderId == null
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
                          onRefund: (item) => _handleRefund(item),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: _MenuComposerCard(
                          menu: bundle.menu,
                          modifiers: bundle.modifiers,
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
                    onRefund: (item) => _handleRefund(item),
                  ),
                  const SizedBox(height: 16),
                  _MenuComposerCard(
                    menu: bundle.menu,
                    modifiers: bundle.modifiers,
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
    int quantity = 1;
    final noteController = TextEditingController();
    final selectedChoices = <int, int>{};
    final selectedModifiers = <int>{};

    final added = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
            builder: (context) {
              final navigator = Navigator.of(context);
              return Padding(
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: StatefulBuilder(
            builder: (context, setSheetState) {
              return SingleChildScrollView(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      product.name,
                      style: Theme.of(context)
                          .textTheme
                          .titleLarge
                          ?.copyWith(fontWeight: FontWeight.w800),
                    ),
                    const SizedBox(height: 8),
                    Text('EGP ${product.price.toStringAsFixed(2)}'),
                    const SizedBox(height: 18),
                    Row(
                      children: [
                        const Text('Quantity'),
                        const Spacer(),
                        IconButton(
                          onPressed: quantity > 1
                              ? () => setSheetState(() => quantity -= 1)
                              : null,
                          icon: const Icon(Icons.remove_circle_outline),
                        ),
                        Text('$quantity'),
                        IconButton(
                          onPressed: () => setSheetState(() => quantity += 1),
                          icon: const Icon(Icons.add_circle_outline),
                        ),
                      ],
                    ),
                    TextField(
                      controller: noteController,
                      decoration: const InputDecoration(
                        labelText: 'Order note',
                        hintText: 'No onions, VIP guest, extra napkins...',
                      ),
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
                                selected:
                                    selectedChoices[question.id] == choice.id,
                                onSelected: (_) {
                                  setSheetState(() {
                                    selectedChoices[question.id] = choice.id;
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
                                  '${modifier.name} (+${modifier.price.toStringAsFixed(0)})'),
                              selected: selectedModifiers.contains(modifier.id),
                              onSelected: (selected) {
                                setSheetState(() {
                                  if (selected) {
                                    selectedModifiers.add(modifier.id);
                                  } else {
                                    selectedModifiers.remove(modifier.id);
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
                        onPressed: () async {
                          await ref.read(suiteRepositoryProvider).createOrder(
                            tableId: widget.table.id,
                            customerName: _customerNameController.text.trim(),
                            customerPhone: _customerPhoneController.text.trim(),
                            items: [
                              {
                                'product_id': product.id,
                                'quantity': quantity,
                                if (noteController.text.trim().isNotEmpty)
                                  'note': noteController.text.trim(),
                                if (selectedChoices.isNotEmpty)
                                  'answers': selectedChoices.values
                                      .map(
                                          (choiceId) => {'choice_id': choiceId})
                                      .toList(growable: false),
                                if (selectedModifiers.isNotEmpty)
                                  'modifiers': selectedModifiers
                                      .map((modifierId) =>
                                          {'modifier_id': modifierId})
                                      .toList(growable: false),
                              },
                            ],
                          );

                          if (!mounted) return;
                          navigator.pop(true);
                        },
                        child: const Text('Add to order'),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),
        );
      },
    );

    noteController.dispose();

    if (added == true && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('${product.name} added to the order')),
      );
      setState(() {
        _future = _load();
      });
    }
  }

  Future<void> _handleDecrease(OrderItemLine item) async {
    if (item.quantity <= 1) {
      await _handleRefund(item);
      return;
    }

    await _runAction(
      () => ref.read(suiteRepositoryProvider).changeOrderItem(
            orderItemId: item.id,
            quantity: item.quantity - 1,
            note: 'Reduced quantity from waiter app',
          ),
      '${item.name} quantity updated',
    );
  }

  Future<void> _handleRefund(OrderItemLine item) async {
    await _runAction(
      () => ref.read(suiteRepositoryProvider).refundOrderItem(
            orderItemId: item.id,
            note: 'Refunded from waiter app',
          ),
      '${item.name} refunded',
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

class _OrderItemsCard extends StatelessWidget {
  const _OrderItemsCard({
    required this.items,
    required this.onDecrease,
    required this.onRefund,
  });

  final List<OrderItemLine> items;
  final ValueChanged<OrderItemLine> onDecrease;
  final ValueChanged<OrderItemLine> onRefund;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return const Card(
        child: SizedBox(
          height: 260,
          child: EmptyView(
            title: 'No items yet',
            description:
                'Add products from the right-side menu to open the order.',
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
            Text(
              'Order items',
              style: Theme.of(context)
                  .textTheme
                  .titleLarge
                  ?.copyWith(fontWeight: FontWeight.w800),
            ),
            const SizedBox(height: 14),
            for (final item in items)
              Padding(
                padding: const EdgeInsets.only(bottom: 14),
                child: Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Row(
                    children: [
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
                            if (item.itemNote != null &&
                                item.itemNote!.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(item.itemNote!),
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
                          ],
                        ),
                      ),
                      Column(
                        children: [
                          IconButton(
                            onPressed: () => onDecrease(item),
                            icon: const Icon(Icons.remove_circle_outline),
                          ),
                          IconButton(
                            onPressed: () => onRefund(item),
                            icon: const Icon(Icons.restart_alt_outlined),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _MenuComposerCard extends StatelessWidget {
  const _MenuComposerCard({
    required this.menu,
    required this.modifiers,
    required this.onAdd,
  });

  final List<MenuCategoryData> menu;
  final List<ModifierData> modifiers;
  final void Function(MenuProduct product, MenuCategoryData category) onAdd;

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
            const SizedBox(height: 14),
            for (final category in menu)
              ExpansionTile(
                tilePadding: EdgeInsets.zero,
                childrenPadding: EdgeInsets.zero,
                title: Text(category.name),
                subtitle: Text(
                  '${category.products.length} products • ${category.questions.length} category questions • ${modifiers.length} modifiers',
                ),
                children: [
                  for (final product in category.products)
                    ListTile(
                      contentPadding: EdgeInsets.zero,
                      title: Text(product.name),
                      subtitle: Text('EGP ${product.price.toStringAsFixed(2)}'),
                      trailing: FilledButton.tonal(
                        onPressed: () => onAdd(product, category),
                        child: const Text('Add'),
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
