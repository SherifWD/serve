import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:printing/printing.dart';

import '../../../core/models/app_models.dart';
import '../../../core/platform/file_download.dart';
import '../../../core/widgets/state_views.dart';
import '../../suite/data/suite_repository.dart';

class OwnerWorkspacePage extends ConsumerStatefulWidget {
  const OwnerWorkspacePage({super.key});

  @override
  ConsumerState<OwnerWorkspacePage> createState() => _OwnerWorkspacePageState();
}

class _OwnerWorkspacePageState extends ConsumerState<OwnerWorkspacePage> {
  late Future<OwnerSummary> _future;
  _OwnerDatePreset _datePreset = _OwnerDatePreset.today;
  int? _selectedBranchId;
  late DateTime _startDate;
  late DateTime _endDate;

  @override
  void initState() {
    super.initState();
    final today = DateUtils.dateOnly(DateTime.now());
    _startDate = today;
    _endDate = today;
    _future = _loadSummary();
  }

  Future<OwnerSummary> _loadSummary() {
    return ref.read(suiteRepositoryProvider).fetchOwnerSummary(
          branchId: _selectedBranchId,
          preset: _datePreset.apiValue,
          startDate: _datePreset == _OwnerDatePreset.custom
              ? _apiDate(_startDate)
              : null,
          endDate: _datePreset == _OwnerDatePreset.custom
              ? _apiDate(_endDate)
              : null,
        );
  }

  String _apiDate(DateTime date) => DateFormat('yyyy-MM-dd').format(date);

  void _setPreset(_OwnerDatePreset preset) {
    final today = DateUtils.dateOnly(DateTime.now());
    setState(() {
      _datePreset = preset;
      switch (preset) {
        case _OwnerDatePreset.today:
          _startDate = today;
          _endDate = today;
        case _OwnerDatePreset.week:
          _startDate = today.subtract(const Duration(days: 6));
          _endDate = today;
        case _OwnerDatePreset.month:
          _startDate = DateTime(today.year, today.month);
          _endDate = today;
        case _OwnerDatePreset.custom:
          break;
      }
      _future = _loadSummary();
    });
  }

  void _setBranch(int? branchId) {
    setState(() {
      _selectedBranchId = branchId;
      _future = _loadSummary();
    });
  }

  Future<void> _pickDate({required bool start}) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: start ? _startDate : _endDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now().add(const Duration(days: 1)),
    );

    if (picked == null) return;

    setState(() {
      _datePreset = _OwnerDatePreset.custom;
      if (start) {
        _startDate = DateUtils.dateOnly(picked);
        if (_startDate.isAfter(_endDate)) _endDate = _startDate;
      } else {
        _endDate = DateUtils.dateOnly(picked);
        if (_endDate.isBefore(_startDate)) _startDate = _endDate;
      }
      _future = _loadSummary();
    });
  }

  Future<void> _printOwnerReceipt() async {
    try {
      final document =
          await ref.read(suiteRepositoryProvider).generateOwnerReceipt(
                preset: _datePreset.apiValue,
                branchId: _selectedBranchId,
                startDate: _datePreset == _OwnerDatePreset.custom
                    ? _apiDate(_startDate)
                    : null,
                endDate: _datePreset == _OwnerDatePreset.custom
                    ? _apiDate(_endDate)
                    : null,
              );
      await Printing.layoutPdf(
        name: document.filename,
        onLayout: (_) async => document.bytes,
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  Future<void> _exportData() async {
    final dataset = await showDialog<_OwnerExportDataset>(
      context: context,
      builder: (context) => const _ExportDatasetDialog(),
    );
    if (dataset == null) return;

    try {
      final document = await ref.read(suiteRepositoryProvider).generateDataExport(
            dataset: dataset.apiValue,
            branchId: _selectedBranchId,
            startDate: dataset.dateScoped ? _apiDate(_startDate) : null,
            endDate: dataset.dateScoped ? _apiDate(_endDate) : null,
          );
      final downloaded = await downloadBytes(
        bytes: document.bytes,
        filename: document.filename,
        mimeType: document.mimeType,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(downloaded
              ? 'Export downloaded: ${document.filename}'
              : 'Export ready: ${document.filename}'),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(e.toString())));
    }
  }

  void _showBranchDetail(
    OwnerBranchDetail detail,
    NumberFormat currency,
  ) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: const Color(0xFF0F172A),
      builder: (context) {
        return DraggableScrollableSheet(
          expand: false,
          initialChildSize: 0.82,
          minChildSize: 0.45,
          maxChildSize: 0.95,
          builder: (context, scrollController) {
            return _BranchDetailSheet(
              detail: detail,
              currency: currency,
              scrollController: scrollController,
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'USD ');

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
              _future = _loadSummary();
            }),
          );
        }

        final summary = snapshot.data!;
        final width = MediaQuery.of(context).size.width;
        final compact = width < 560;
        final wide = width > 1220;
        final branchIds =
            summary.branchOptions.map((branch) => branch.id).toSet();
        final selectedBranchId =
            _selectedBranchId != null && branchIds.contains(_selectedBranchId)
                ? _selectedBranchId
                : summary.selectedBranchId;
        final branchDetailsById = {
          for (final detail in summary.branchDetails) detail.id: detail,
        };

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
                _future = _loadSummary();
              });
              await _future;
            },
            child: ListView(
              padding: EdgeInsets.all(compact ? 12 : 16),
              children: [
                _OwnerHero(summary: summary, currency: currency),
                const SizedBox(height: 16),
                _OwnerDateFilterBar(
                  preset: _datePreset,
                  startDate: _startDate,
                  endDate: _endDate,
                  branchOptions: summary.branchOptions,
                  selectedBranchId: selectedBranchId,
                  onPresetChanged: _setPreset,
                  onBranchChanged: _setBranch,
                  onPickStart: () => _pickDate(start: true),
                  onPickEnd: () => _pickDate(start: false),
                  onPrintReceipt: _printOwnerReceipt,
                  onExportData: _exportData,
                ),
                const SizedBox(height: 16),
                _OwnerKpiGrid(summary: summary, currency: currency),
                const SizedBox(height: 16),
                _FinanceSummaryPanel(summary: summary, currency: currency),
                const SizedBox(height: 16),
                _EmployeeRevenuePanel(
                  entries: summary.employeeRevenue,
                  currency: currency,
                ),
                const SizedBox(height: 16),
                _BranchPerformancePanel(
                  branches: summary.branchPerformance,
                  currency: currency,
                  onBranchTap: (branch) {
                    final detail = branchDetailsById[branch.id];
                    if (detail != null) _showBranchDetail(detail, currency);
                  },
                ),
                const SizedBox(height: 16),
                _ActiveEmployeesPanel(employees: summary.activeEmployees),
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

enum _OwnerDatePreset {
  today,
  week,
  month,
  custom;

  String get apiValue {
    switch (this) {
      case _OwnerDatePreset.today:
        return 'today';
      case _OwnerDatePreset.week:
        return 'week';
      case _OwnerDatePreset.month:
        return 'month';
      case _OwnerDatePreset.custom:
        return 'custom';
    }
  }

  String get label {
    switch (this) {
      case _OwnerDatePreset.today:
        return 'Today';
      case _OwnerDatePreset.week:
        return 'Week';
      case _OwnerDatePreset.month:
        return 'Month';
      case _OwnerDatePreset.custom:
        return 'From-to';
    }
  }
}

enum _OwnerExportDataset {
  products('products', 'Products', false),
  customers('customers', 'Customers', false),
  orders('orders', 'Orders', true),
  payments('payments', 'Payments', true),
  receipts('receipts', 'Receipts', true),
  inventoryItems('inventory-items', 'Inventory items', false);

  const _OwnerExportDataset(this.apiValue, this.label, this.dateScoped);

  final String apiValue;
  final String label;
  final bool dateScoped;
}

class _ExportDatasetDialog extends StatefulWidget {
  const _ExportDatasetDialog();

  @override
  State<_ExportDatasetDialog> createState() => _ExportDatasetDialogState();
}

class _ExportDatasetDialogState extends State<_ExportDatasetDialog> {
  _OwnerExportDataset _dataset = _OwnerExportDataset.orders;

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Export CSV'),
      content: DropdownButtonFormField<_OwnerExportDataset>(
        value: _dataset,
        decoration: const InputDecoration(
          labelText: 'Dataset',
          prefixIcon: Icon(Icons.table_view_outlined),
        ),
        items: [
          for (final dataset in _OwnerExportDataset.values)
            DropdownMenuItem(
              value: dataset,
              child: Text(dataset.label),
            ),
        ],
        onChanged: (value) {
          if (value != null) {
            setState(() => _dataset = value);
          }
        },
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Cancel'),
        ),
        FilledButton.icon(
          onPressed: () => Navigator.of(context).pop(_dataset),
          icon: const Icon(Icons.download_outlined),
          label: const Text('Export'),
        ),
      ],
    );
  }
}

class _OwnerDateFilterBar extends StatelessWidget {
  const _OwnerDateFilterBar({
    required this.preset,
    required this.startDate,
    required this.endDate,
    required this.branchOptions,
    required this.selectedBranchId,
    required this.onPresetChanged,
    required this.onBranchChanged,
    required this.onPickStart,
    required this.onPickEnd,
    required this.onPrintReceipt,
    required this.onExportData,
  });

  final _OwnerDatePreset preset;
  final DateTime startDate;
  final DateTime endDate;
  final List<BranchInfo> branchOptions;
  final int? selectedBranchId;
  final ValueChanged<_OwnerDatePreset> onPresetChanged;
  final ValueChanged<int?> onBranchChanged;
  final VoidCallback onPickStart;
  final VoidCallback onPickEnd;
  final Future<void> Function() onPrintReceipt;
  final Future<void> Function() onExportData;

  @override
  Widget build(BuildContext context) {
    final formatter = DateFormat('MMM d, yyyy');
    final branchIds = branchOptions.map((branch) => branch.id).toSet();
    final branchValue =
        selectedBranchId != null && branchIds.contains(selectedBranchId)
            ? selectedBranchId
            : (branchOptions.length == 1 ? branchOptions.first.id : null);
    final controlBorderColor = Colors.white.withValues(alpha: 0.28);
    const activeBorderColor = Color(0xFFE86C2F);
    final controlFillColor = Colors.white.withValues(alpha: 0.03);
    final controlRadius = BorderRadius.circular(8);
    final outlinedControlStyle = OutlinedButton.styleFrom(
      foregroundColor: Colors.white,
      side: BorderSide(color: controlBorderColor),
      shape: RoundedRectangleBorder(borderRadius: controlRadius),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      textStyle: const TextStyle(fontWeight: FontWeight.w700),
    );

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF111827),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.white.withValues(alpha: 0.06)),
      ),
      child: Wrap(
        spacing: 12,
        runSpacing: 12,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          if (branchOptions.isNotEmpty)
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 260),
              child: DropdownButtonFormField<int?>(
                value: branchValue,
                dropdownColor: const Color(0xFF111827),
                decoration: InputDecoration(
                  isDense: true,
                  filled: true,
                  fillColor: controlFillColor,
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 14,
                  ),
                  prefixIcon: const Icon(
                    Icons.store_mall_directory_outlined,
                    size: 18,
                  ),
                  prefixIconColor: Colors.white70,
                  enabledBorder: OutlineInputBorder(
                    borderRadius: controlRadius,
                    borderSide: BorderSide(color: controlBorderColor),
                  ),
                  disabledBorder: OutlineInputBorder(
                    borderRadius: controlRadius,
                    borderSide: BorderSide(color: controlBorderColor),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: controlRadius,
                    borderSide: const BorderSide(color: activeBorderColor),
                  ),
                ),
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w700,
                ),
                iconEnabledColor: Colors.white70,
                iconDisabledColor: Colors.white38,
                items: [
                  if (branchOptions.length > 1)
                    const DropdownMenuItem<int?>(
                      value: null,
                      child: Text('All branches'),
                    ),
                  for (final branch in branchOptions)
                    DropdownMenuItem<int?>(
                      value: branch.id,
                      child: Text(branch.name),
                    ),
                ],
                onChanged: onBranchChanged,
              ),
            ),
          for (final option in _OwnerDatePreset.values)
            ChoiceChip(
              label: Text(option.label),
              selected: preset == option,
              onSelected: (_) => onPresetChanged(option),
            ),
          OutlinedButton.icon(
            onPressed: onPickStart,
            style: outlinedControlStyle,
            icon: const Icon(Icons.calendar_today_outlined),
            label: Text(preset == _OwnerDatePreset.custom
                ? 'From ${formatter.format(startDate)}'
                : 'From date'),
          ),
          OutlinedButton.icon(
            onPressed: onPickEnd,
            style: outlinedControlStyle,
            icon: const Icon(Icons.event_outlined),
            label: Text(preset == _OwnerDatePreset.custom
                ? 'To ${formatter.format(endDate)}'
                : 'To date'),
          ),
          FilledButton.icon(
            onPressed: onPrintReceipt,
            icon: const Icon(Icons.print_outlined),
            label: const Text('Print date receipt'),
          ),
          OutlinedButton.icon(
            onPressed: onExportData,
            style: outlinedControlStyle,
            icon: const Icon(Icons.download_outlined),
            label: const Text('Export CSV'),
          ),
        ],
      ),
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
    return LayoutBuilder(
      builder: (context, constraints) {
        final compact = constraints.maxWidth < 620;
        final glance = Container(
          width: compact ? double.infinity : 220,
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(24),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Period at a glance',
                style: TextStyle(color: Colors.white70),
              ),
              const SizedBox(height: 10),
              Text(
                currency.format(summary.netRevenue),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                      color: Colors.white,
                      fontWeight: FontWeight.w900,
                    ),
              ),
              const SizedBox(height: 6),
              Text(
                '${currency.format(summary.totalSales)} revenue / ${currency.format(summary.totalExpenses)} expenses',
                style: const TextStyle(color: Colors.white70),
              ),
            ],
          ),
        );

        final lead = Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Owner dashboard',
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w900,
                  ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Live sales, branch health, staffing, stock, and settlement signals.',
              style: TextStyle(color: Colors.white70, height: 1.35),
            ),
            const SizedBox(height: 16),
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
              ],
            ),
          ],
        );

        return Container(
          padding: EdgeInsets.all(compact ? 16 : 22),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF111C44), Color(0xFF172554), Color(0xFF0F766E)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(compact ? 24 : 32),
          ),
          child: compact
              ? Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    lead,
                    const SizedBox(height: 16),
                    glance,
                  ],
                )
              : Row(
                  children: [
                    Expanded(child: lead),
                    const SizedBox(width: 18),
                    glance,
                  ],
                ),
        );
      },
    );
  }
}

class _OwnerKpiGrid extends StatelessWidget {
  const _OwnerKpiGrid({
    required this.summary,
    required this.currency,
  });

  final OwnerSummary summary;
  final NumberFormat currency;

  @override
  Widget build(BuildContext context) {
    final cards = [
      _OwnerKpiCard(
        title: 'Revenue',
        value: currency.format(summary.totalSales),
        caption: 'Paid sales',
        color: const Color(0xFFE86C2F),
        icon: Icons.attach_money_outlined,
      ),
      _OwnerKpiCard(
        title: 'Expenses',
        value: currency.format(summary.totalExpenses),
        caption: 'Logged branch costs',
        color: const Color(0xFFF87171),
        icon: Icons.money_off_outlined,
      ),
      _OwnerKpiCard(
        title: 'Net',
        value: currency.format(summary.netRevenue),
        caption: 'Revenue minus expenses',
        color: const Color(0xFF34D399),
        icon: Icons.account_balance_wallet_outlined,
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
        title: 'KDS backlog',
        value: '${summary.kdsBacklog}',
        caption: 'Queued or cooking',
        color: const Color(0xFF22D3EE),
        icon: Icons.restaurant_menu_outlined,
      ),
      _OwnerKpiCard(
        title: 'Loyalty',
        value: '${summary.loyaltyMembers}',
        caption: 'Tracked guests',
        color: const Color(0xFFA78BFA),
        icon: Icons.workspace_premium_outlined,
      ),
    ];

    return LayoutBuilder(
      builder: (context, constraints) {
        final maxWidth = constraints.maxWidth;
        final columns = maxWidth >= 1100
            ? 4
            : maxWidth >= 720
                ? 3
                : maxWidth >= 420
                    ? 2
                    : 1;
        const spacing = 14.0;
        final cardWidth = (maxWidth - spacing * (columns - 1)) / columns;

        return Wrap(
          spacing: spacing,
          runSpacing: spacing,
          children: [
            for (final card in cards)
              SizedBox(
                width: cardWidth,
                child: card,
              ),
          ],
        );
      },
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
      width: double.infinity,
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
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w900,
                ),
          ),
          const SizedBox(height: 6),
          Text(
            caption,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
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
    required this.onBranchTap,
  });

  final List<BranchPerformance> branches;
  final NumberFormat currency;
  final ValueChanged<BranchPerformance> onBranchTap;

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
                    child: InkWell(
                      borderRadius: BorderRadius.circular(20),
                      onTap: () => onBranchTap(branches[i]),
                      child: Container(
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: Colors.white.withValues(alpha: 0.04),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: LayoutBuilder(
                          builder: (context, constraints) {
                            final compact = constraints.maxWidth < 520;
                            final rank = Container(
                              width: 38,
                              height: 38,
                              decoration: BoxDecoration(
                                color: const Color(0xFFE86C2F)
                                    .withValues(alpha: 0.2),
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
                            );
                            final branchText = Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  branches[i].name,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
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
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(color: Colors.white54),
                                ),
                              ],
                            );
                            final salesText = Column(
                              crossAxisAlignment: compact
                                  ? CrossAxisAlignment.start
                                  : CrossAxisAlignment.end,
                              children: [
                                Text(
                                  currency.format(branches[i].sales),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.w900,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '${branches[i].ordersCount} orders / ${currency.format(branches[i].netRevenue)} net',
                                  style: const TextStyle(color: Colors.white54),
                                ),
                              ],
                            );

                            if (compact) {
                              return Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      rank,
                                      const SizedBox(width: 12),
                                      Expanded(child: branchText),
                                      const Icon(
                                        Icons.chevron_right,
                                        color: Colors.white54,
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 12),
                                  salesText,
                                ],
                              );
                            }

                            return Row(
                              children: [
                                rank,
                                const SizedBox(width: 12),
                                Expanded(child: branchText),
                                salesText,
                                const SizedBox(width: 8),
                                const Icon(
                                  Icons.chevron_right,
                                  color: Colors.white54,
                                ),
                              ],
                            );
                          },
                        ),
                      ),
                    ),
                  ),
              ],
            ),
    );
  }
}

class _ActiveEmployeesPanel extends StatelessWidget {
  const _ActiveEmployeesPanel({required this.employees});

  final List<OwnerEmployeeActivity> employees;

  @override
  Widget build(BuildContext context) {
    return _OwnerPanel(
      title: 'Active employees now',
      subtitle: 'Checked in or on an open shift for the current branch filter',
      child: employees.isEmpty
          ? const Text(
              'No active employees right now.',
              style: TextStyle(color: Colors.white70),
            )
          : Column(
              children: [
                for (final employee in employees)
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(
                      Icons.badge_outlined,
                      color: Color(0xFF34D399),
                    ),
                    title: Text(
                      employee.name,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    subtitle: Text(
                      [
                        employee.roleLabel,
                        if (employee.branchName != null) employee.branchName!,
                        if (employee.activityLabel != null)
                          employee.activityLabel!,
                      ].join(' - '),
                      style: const TextStyle(color: Colors.white54),
                    ),
                    trailing: _EmployeeStatusPill(employee: employee),
                  ),
              ],
            ),
    );
  }
}

class _BranchDetailSheet extends StatelessWidget {
  const _BranchDetailSheet({
    required this.detail,
    required this.currency,
    required this.scrollController,
  });

  final OwnerBranchDetail detail;
  final NumberFormat currency;
  final ScrollController scrollController;

  @override
  Widget build(BuildContext context) {
    return ListView(
      controller: scrollController,
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 28),
      children: [
        Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    detail.name,
                    style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                          color: Colors.white,
                          fontWeight: FontWeight.w900,
                        ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    detail.location ?? 'No location set',
                    style: const TextStyle(color: Colors.white54),
                  ),
                ],
              ),
            ),
            IconButton(
              onPressed: () => Navigator.of(context).pop(),
              icon: const Icon(Icons.close, color: Colors.white70),
            ),
          ],
        ),
        const SizedBox(height: 16),
        Wrap(
          spacing: 10,
          runSpacing: 10,
          children: [
            _BranchMetricChip(
              label: 'Sales',
              value: currency.format(detail.sales),
            ),
            _BranchMetricChip(
              label: 'Expenses',
              value: currency.format(detail.expenses),
            ),
            _BranchMetricChip(
              label: 'Net',
              value: currency.format(detail.netRevenue),
            ),
            _BranchMetricChip(
              label: 'Orders',
              value: '${detail.ordersCount}',
            ),
            _BranchMetricChip(
              label: 'Returned',
              value: '${detail.returnedOrdersCount}',
            ),
            _BranchMetricChip(
              label: 'Active',
              value: '${detail.activeEmployees.length}',
            ),
          ],
        ),
        const SizedBox(height: 22),
        _BranchDetailSection(
          title: 'Employees',
          child: detail.employees.isEmpty
              ? const Text(
                  'No employees assigned.',
                  style: TextStyle(color: Colors.white70),
                )
              : Column(
                  children: [
                    for (final employee in detail.employees)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(
                          employee.name,
                          style: const TextStyle(color: Colors.white),
                        ),
                        subtitle: Text(
                          [
                            employee.roleLabel,
                            if (employee.activityLabel != null)
                              employee.activityLabel!,
                          ].join(' - '),
                          style: const TextStyle(color: Colors.white54),
                        ),
                        trailing: _EmployeeStatusPill(employee: employee),
                      ),
                  ],
                ),
        ),
        _BranchDetailSection(
          title: 'Tables',
          child: detail.tables.isEmpty
              ? const Text(
                  'No tables assigned.',
                  style: TextStyle(color: Colors.white70),
                )
              : Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    for (final table in detail.tables)
                      _BranchMetricChip(
                        label: table.name,
                        value: '${table.status} - ${table.seats} seats',
                      ),
                  ],
                ),
        ),
        _BranchDetailSection(
          title: 'Kitchen shift',
          child: detail.kitchenShift.isEmpty
              ? const Text(
                  'No kitchen employees assigned.',
                  style: TextStyle(color: Colors.white70),
                )
              : Column(
                  children: [
                    for (final employee in detail.kitchenShift)
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(
                          employee.name,
                          style: const TextStyle(color: Colors.white),
                        ),
                        subtitle: Text(
                          employee.activityLabel ?? employee.roleLabel,
                          style: const TextStyle(color: Colors.white54),
                        ),
                        trailing: _EmployeeStatusPill(employee: employee),
                      ),
                  ],
                ),
        ),
        _BranchDetailSection(
          title: 'Orders',
          child: detail.orders.isEmpty
              ? const Text(
                  'No orders in this period.',
                  style: TextStyle(color: Colors.white70),
                )
              : Column(
                  children: [
                    for (final order in detail.orders)
                      _OrderDetailTile(order: order, currency: currency),
                  ],
                ),
        ),
      ],
    );
  }
}

class _OrderDetailTile extends StatelessWidget {
  const _OrderDetailTile({
    required this.order,
    required this.currency,
  });

  final OwnerOrderDetail order;
  final NumberFormat currency;

  @override
  Widget build(BuildContext context) {
    final returnedBy = order.returnedBy.isEmpty
        ? null
        : "Returned by ${order.returnedBy.join(', ')}";

    return Theme(
      data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
      child: ExpansionTile(
        tilePadding: EdgeInsets.zero,
        iconColor: Colors.white70,
        collapsedIconColor: Colors.white54,
        title: Text(
          '#${order.id} - ${currency.format(order.total)}',
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w800,
          ),
        ),
        subtitle: Text(
          [
            order.tableName ?? 'No table',
            "Waiter ${order.waiterName ?? 'not set'}",
            "Cashier ${order.cashierName ?? 'not set'}",
          ].join(' - '),
          style: const TextStyle(color: Colors.white54),
        ),
        children: [
          Align(
            alignment: Alignment.centerLeft,
            child: Text(
              [
                '${order.status}/${order.paymentStatus}',
                if (order.returnedItemsCount > 0)
                  '${order.returnedItemsCount} returned',
                if (returnedBy != null) returnedBy,
              ].join(' - '),
              style: const TextStyle(color: Colors.white70),
            ),
          ),
          const SizedBox(height: 8),
          for (final item in order.items)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                children: [
                  Expanded(
                    child: Text(
                      '${item.quantity}x ${item.name}',
                      style: const TextStyle(color: Colors.white70),
                    ),
                  ),
                  Text(
                    '${currency.format(item.total)} - ${item.status}',
                    style: const TextStyle(color: Colors.white54),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _BranchDetailSection extends StatelessWidget {
  const _BranchDetailSection({
    required this.title,
    required this.child,
  });

  final String title;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 18),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.04),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.white.withValues(alpha: 0.06)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w900,
                  ),
            ),
            const SizedBox(height: 10),
            child,
          ],
        ),
      ),
    );
  }
}

class _BranchMetricChip extends StatelessWidget {
  const _BranchMetricChip({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: Colors.white54)),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

class _EmployeeStatusPill extends StatelessWidget {
  const _EmployeeStatusPill({required this.employee});

  final OwnerEmployeeActivity employee;

  @override
  Widget build(BuildContext context) {
    final label = employee.active
        ? (employee.activeSource == 'shift' ? 'On shift' : 'Checked in')
        : 'Inactive';
    final color = employee.active
        ? const Color(0xFF34D399)
        : Colors.white.withValues(alpha: 0.38);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}

class _FinanceSummaryPanel extends StatelessWidget {
  const _FinanceSummaryPanel({
    required this.summary,
    required this.currency,
  });

  final OwnerSummary summary;
  final NumberFormat currency;

  @override
  Widget build(BuildContext context) {
    return _OwnerPanel(
      title: 'Revenue and expenses',
      subtitle: 'Read-only finance view for your restaurant and branch filter',
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              _BranchMetricChip(
                label: 'Revenue',
                value: currency.format(summary.totalSales),
              ),
              _BranchMetricChip(
                label: 'Expenses',
                value: currency.format(summary.totalExpenses),
              ),
              _BranchMetricChip(
                label: 'Net',
                value: currency.format(summary.netRevenue),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Text(
            'Expense categories',
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w900,
                ),
          ),
          const SizedBox(height: 8),
          if (summary.expenseByCategory.isEmpty)
            const Text(
              'No expenses in this period.',
              style: TextStyle(color: Colors.white70),
            )
          else
            for (final category in summary.expenseByCategory.take(5))
              ListTile(
                contentPadding: EdgeInsets.zero,
                dense: true,
                title: Text(
                  category.category,
                  style: const TextStyle(color: Colors.white),
                ),
                trailing: Text(
                  currency.format(category.total),
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
          const SizedBox(height: 12),
          Text(
            'Recent expenses',
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w900,
                ),
          ),
          const SizedBox(height: 8),
          if (summary.recentExpenses.isEmpty)
            const Text(
              'No recent expenses.',
              style: TextStyle(color: Colors.white70),
            )
          else
            for (final expense in summary.recentExpenses.take(5))
              ListTile(
                contentPadding: EdgeInsets.zero,
                dense: true,
                leading: const Icon(
                  Icons.receipt_long_outlined,
                  color: Color(0xFFF87171),
                ),
                title: Text(
                  expense.category,
                  style: const TextStyle(color: Colors.white),
                ),
                subtitle: Text(
                  [
                    if (expense.branchName != null) expense.branchName!,
                    if (expense.expenseDate != null) expense.expenseDate!,
                  ].join(' - '),
                  style: const TextStyle(color: Colors.white54),
                ),
                trailing: Text(
                  currency.format(expense.amount),
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

class _EmployeeRevenuePanel extends StatelessWidget {
  const _EmployeeRevenuePanel({
    required this.entries,
    required this.currency,
  });

  final List<EmployeeRevenueEntry> entries;
  final NumberFormat currency;

  @override
  Widget build(BuildContext context) {
    return _OwnerPanel(
      title: 'Employee revenue',
      subtitle: 'Paid order revenue by employee and branch',
      child: entries.isEmpty
          ? const Text(
              'No employee revenue in this period.',
              style: TextStyle(color: Colors.white70),
            )
          : Column(
              children: [
                for (final entry in entries.take(12))
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    leading: const Icon(
                      Icons.badge_outlined,
                      color: Color(0xFF38BDF8),
                    ),
                    title: Text(
                      entry.employeeName,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    subtitle: Text(
                      [
                        if (entry.position != null) entry.position!,
                        if (entry.branchName != null) entry.branchName!,
                        '${entry.ordersCount} orders',
                      ].join(' - '),
                      style: const TextStyle(color: Colors.white54),
                    ),
                    trailing: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          currency.format(entry.revenue),
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w900,
                          ),
                        ),
                        Text(
                          '${currency.format(entry.averageOrder)} avg',
                          style: const TextStyle(
                            color: Colors.white54,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
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
                    title: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item['name']?.toString() ?? 'Ingredient',
                          style: const TextStyle(color: Colors.white),
                        ),
                        const SizedBox(height: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 4,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            item['branch_name']?.toString() ?? 'Branch not set',
                            style: const TextStyle(
                              color: Colors.white70,
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
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
            style: const TextStyle(
                color: Colors.white, fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}
