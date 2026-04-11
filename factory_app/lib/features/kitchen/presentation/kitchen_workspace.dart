import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/models/app_models.dart';
import '../../../core/widgets/state_views.dart';
import '../../suite/data/suite_repository.dart';

class KitchenWorkspacePage extends ConsumerStatefulWidget {
  const KitchenWorkspacePage({super.key});

  @override
  ConsumerState<KitchenWorkspacePage> createState() =>
      _KitchenWorkspacePageState();
}

class _KitchenWorkspacePageState extends ConsumerState<KitchenWorkspacePage> {
  late Future<List<KdsTicket>> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(suiteRepositoryProvider).fetchKitchenBoard();
  }

  List<KdsTicket> _filterTickets(List<KdsTicket> tickets, String status) {
    return tickets
        .where((ticket) => ticket.items.any((item) => item.kdsStatus == status))
        .toList(growable: false);
  }

  int _countItems(List<KdsTicket> tickets, String status) {
    return tickets.fold<int>(
      0,
      (sum, ticket) =>
          sum + ticket.items.where((item) => item.kdsStatus == status).length,
    );
  }

  Future<void> _refreshBoard() async {
    setState(() {
      _future = ref.read(suiteRepositoryProvider).fetchKitchenBoard();
    });
    await _future;
  }

  Future<void> _advanceTicket(KdsTicket ticket, String laneStatus) async {
    final items = ticket.items
        .where((item) => item.kdsStatus == laneStatus)
        .toList(growable: false);

    if (items.isEmpty) return;

    final nextStatus = _nextStatus(laneStatus);
    final confirmed = await _showConfirmAdvance(
      title: _ticketPromptTitle(ticket, laneStatus),
      subtitle: _ticketPromptSubtitle(items.length, nextStatus),
      confirmIcon: _confirmIcon(nextStatus),
      accent: _statusColor(nextStatus),
    );

    if (!confirmed) return;

    await _runStatusUpdate(
      items: items,
      nextStatus: nextStatus,
      successMessage:
          'Order #${ticket.id} moved to ${_displayStatus(nextStatus)}',
    );
  }

  Future<void> _advanceItem(OrderItemLine item) async {
    final nextStatus = _nextStatus(item.kdsStatus);
    final requiresConfirmation = nextStatus == 'served';

    if (requiresConfirmation) {
      final confirmed = await _showConfirmAdvance(
        title: 'Send ${item.name} to service?',
        subtitle: 'This is the last kitchen step for this item.',
        confirmIcon: _confirmIcon(nextStatus),
        accent: _statusColor(nextStatus),
      );

      if (!confirmed) return;
    }

    await _runStatusUpdate(
      items: [item],
      nextStatus: nextStatus,
      successMessage: '${item.name} moved to ${_displayStatus(nextStatus)}',
    );
  }

  Future<void> _runStatusUpdate({
    required List<OrderItemLine> items,
    required String nextStatus,
    required String successMessage,
  }) async {
    try {
      for (final item in items) {
        await ref.read(suiteRepositoryProvider).updateKitchenItemStatus(
              itemId: item.id,
              status: nextStatus,
            );
      }

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(successMessage)),
      );
      await _refreshBoard();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString())),
      );
    }
  }

  Future<bool> _showConfirmAdvance({
    required String title,
    required String subtitle,
    required IconData confirmIcon,
    required Color accent,
  }) async {
    final result = await showDialog<bool>(
      context: context,
      builder: (context) {
        return Dialog(
          backgroundColor: const Color(0xFF08101C),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(30),
          ),
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  title,
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w900,
                      ),
                ),
                const SizedBox(height: 10),
                Text(
                  subtitle,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    color: Colors.white70,
                    height: 1.4,
                  ),
                ),
                const SizedBox(height: 26),
                Row(
                  children: [
                    Expanded(
                      child: _ActionConfirmButton(
                        icon: confirmIcon,
                        label: 'Confirm',
                        backgroundColor: accent,
                        foregroundColor: const Color(0xFF05121B),
                        onTap: () => Navigator.of(context).pop(true),
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: _ActionConfirmButton(
                        icon: Icons.reply_rounded,
                        label: 'Back',
                        backgroundColor: Colors.white.withValues(alpha: 0.10),
                        foregroundColor: Colors.white,
                        onTap: () => Navigator.of(context).pop(false),
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

    return result ?? false;
  }

  String _ticketPromptTitle(KdsTicket ticket, String laneStatus) {
    final action = switch (laneStatus) {
      'queued' => 'Start preparing',
      'preparing' => 'Mark ready',
      'returned' => 'Restart returned',
      _ => 'Send to service',
    };

    return '$action Order #${ticket.id}?';
  }

  String _ticketPromptSubtitle(int count, String nextStatus) {
    return 'Confirm moving $count item${count == 1 ? '' : 's'} to ${_displayStatus(nextStatus)}.';
  }

  String _nextStatus(String? status) {
    switch (status) {
      case 'queued':
        return 'preparing';
      case 'returned':
        return 'preparing';
      case 'preparing':
        return 'ready';
      default:
        return 'served';
    }
  }

  String _displayStatus(String status) {
    switch (status) {
      case 'preparing':
        return 'preparing';
      case 'ready':
        return 'ready';
      case 'served':
        return 'service';
      case 'returned':
        return 'returned';
      default:
        return status;
    }
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'queued':
        return const Color(0xFFF59E0B);
      case 'preparing':
        return const Color(0xFF38BDF8);
      case 'ready':
        return const Color(0xFF34D399);
      case 'served':
        return const Color(0xFFF4F7FB);
      case 'returned':
        return const Color(0xFFF87171);
      default:
        return const Color(0xFF9CA3AF);
    }
  }

  IconData _confirmIcon(String status) {
    switch (status) {
      case 'preparing':
        return Icons.play_arrow_rounded;
      case 'ready':
        return Icons.done_all_rounded;
      case 'served':
        return Icons.room_service_rounded;
      default:
        return Icons.check_circle_rounded;
    }
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<KdsTicket>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const LoadingView(label: 'Loading kitchen board...');
        }
        if (snapshot.hasError) {
          return ErrorView(
            message: snapshot.error.toString(),
            onRetry: () => _refreshBoard(),
          );
        }

        final tickets = snapshot.data!;
        final queuedTickets = _filterTickets(tickets, 'queued');
        final preparingTickets = _filterTickets(tickets, 'preparing');
        final readyTickets = _filterTickets(tickets, 'ready');
        final returnedTickets = _filterTickets(tickets, 'returned');
        final queuedItems = _countItems(tickets, 'queued');
        final preparingItems = _countItems(tickets, 'preparing');
        final readyItems = _countItems(tickets, 'ready');
        final servedItems = _countItems(tickets, 'served');
        final returnedItems = _countItems(tickets, 'returned');
        final wide = MediaQuery.of(context).size.width > 1180;

        return Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [Color(0xFF050A13), Color(0xFF0A111E), Color(0xFF0C1423)],
              begin: Alignment.topCenter,
              end: Alignment.bottomCenter,
            ),
          ),
          child: RefreshIndicator(
            onRefresh: _refreshBoard,
            child: ListView(
              padding: const EdgeInsets.all(16),
              physics: const AlwaysScrollableScrollPhysics(),
              children: [
                _KitchenHero(
                  queued: queuedTickets.length,
                  preparing: preparingTickets.length,
                  ready: readyTickets.length,
                  queuedItems: queuedItems,
                  preparingItems: preparingItems,
                  readyItems: readyItems,
                  servedItems: servedItems,
                  returnedItems: returnedItems,
                ),
                const SizedBox(height: 16),
                if (tickets.isEmpty)
                  const Padding(
                    padding: EdgeInsets.only(top: 52),
                    child: EmptyView(
                      title: 'Kitchen is clear',
                      description:
                          'Orders from the waiter app will appear here. This screen is intentionally minimal for speed.',
                      icon: Icons.soup_kitchen_outlined,
                    ),
                  )
                else if (wide)
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: _KitchenLane(
                          title: 'Queued',
                          subtitle: 'Tap ticket to start all items',
                          color: const Color(0xFFF59E0B),
                          tickets: queuedTickets,
                          itemCount: queuedItems,
                          laneStatus: 'queued',
                          onTicketTap: _advanceTicket,
                          onItemTap: _advanceItem,
                        ),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: _KitchenLane(
                          title: 'Preparing',
                          subtitle: 'Tap ticket to mark everything ready',
                          color: const Color(0xFF38BDF8),
                          tickets: preparingTickets,
                          itemCount: preparingItems,
                          laneStatus: 'preparing',
                          onTicketTap: _advanceTicket,
                          onItemTap: _advanceItem,
                        ),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: _KitchenLane(
                          title: 'Ready',
                          subtitle:
                              'Tap ticket to send the whole order to service',
                          color: const Color(0xFF34D399),
                          tickets: readyTickets,
                          itemCount: readyItems,
                          laneStatus: 'ready',
                          onTicketTap: _advanceTicket,
                          onItemTap: _advanceItem,
                        ),
                      ),
                      const SizedBox(width: 14),
                      Expanded(
                        child: _KitchenLane(
                          title: 'Returned',
                          subtitle: 'Tap ticket to restart returned items',
                          color: const Color(0xFFF87171),
                          tickets: returnedTickets,
                          itemCount: returnedItems,
                          laneStatus: 'returned',
                          onTicketTap: _advanceTicket,
                          onItemTap: _advanceItem,
                        ),
                      ),
                    ],
                  )
                else
                  Column(
                    children: [
                      _KitchenLane(
                        title: 'Queued',
                        subtitle: 'Tap ticket to start all items',
                        color: const Color(0xFFF59E0B),
                        tickets: queuedTickets,
                        itemCount: queuedItems,
                        laneStatus: 'queued',
                        onTicketTap: _advanceTicket,
                        onItemTap: _advanceItem,
                      ),
                      const SizedBox(height: 14),
                      _KitchenLane(
                        title: 'Preparing',
                        subtitle: 'Tap ticket to mark everything ready',
                        color: const Color(0xFF38BDF8),
                        tickets: preparingTickets,
                        itemCount: preparingItems,
                        laneStatus: 'preparing',
                        onTicketTap: _advanceTicket,
                        onItemTap: _advanceItem,
                      ),
                      const SizedBox(height: 14),
                      _KitchenLane(
                        title: 'Ready',
                        subtitle:
                            'Tap ticket to send the whole order to service',
                        color: const Color(0xFF34D399),
                        tickets: readyTickets,
                        itemCount: readyItems,
                        laneStatus: 'ready',
                        onTicketTap: _advanceTicket,
                        onItemTap: _advanceItem,
                      ),
                      const SizedBox(height: 14),
                      _KitchenLane(
                        title: 'Returned',
                        subtitle: 'Tap ticket to restart returned items',
                        color: const Color(0xFFF87171),
                        tickets: returnedTickets,
                        itemCount: returnedItems,
                        laneStatus: 'returned',
                        onTicketTap: _advanceTicket,
                        onItemTap: _advanceItem,
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

class _KitchenHero extends StatelessWidget {
  const _KitchenHero({
    required this.queued,
    required this.preparing,
    required this.ready,
    required this.queuedItems,
    required this.preparingItems,
    required this.readyItems,
    required this.servedItems,
    required this.returnedItems,
  });

  final int queued;
  final int preparing;
  final int ready;
  final int queuedItems;
  final int preparingItems;
  final int readyItems;
  final int servedItems;
  final int returnedItems;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFF0E1625),
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: Colors.white.withValues(alpha: 0.07)),
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
                      'Kitchen KDS',
                      style:
                          Theme.of(context).textTheme.headlineSmall?.copyWith(
                                color: Colors.white,
                                fontWeight: FontWeight.w900,
                              ),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Minimal, readable, and made for quick movement. Tap a full ticket to move the lane. Tap a single item to move it forward.',
                      style: TextStyle(color: Colors.white70, height: 1.35),
                    ),
                  ],
                ),
              ),
              const Wrap(
                spacing: 10,
                runSpacing: 10,
                children: [
                  _BoardHint(label: 'Tap ticket = move all'),
                  _BoardHint(label: 'Tap item = next step'),
                ],
              ),
            ],
          ),
          const SizedBox(height: 18),
          Wrap(
            spacing: 12,
            runSpacing: 12,
            children: [
              _KitchenMetric(
                title: 'Queued',
                value: '$queued',
                detail: '$queuedItems items',
                color: const Color(0xFFF59E0B),
              ),
              _KitchenMetric(
                title: 'Preparing',
                value: '$preparing',
                detail: '$preparingItems items',
                color: const Color(0xFF38BDF8),
              ),
              _KitchenMetric(
                title: 'Ready',
                value: '$ready',
                detail: '$readyItems items',
                color: const Color(0xFF34D399),
              ),
              _KitchenMetric(
                title: 'Served',
                value: '$servedItems',
                detail: 'items',
                color: const Color(0xFFF4F7FB),
              ),
              _KitchenMetric(
                title: 'Returned',
                value: '$returnedItems',
                detail: 'items',
                color: const Color(0xFFF87171),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _KitchenLane extends StatelessWidget {
  const _KitchenLane({
    required this.title,
    required this.subtitle,
    required this.color,
    required this.tickets,
    required this.itemCount,
    required this.laneStatus,
    required this.onTicketTap,
    required this.onItemTap,
  });

  final String title;
  final String subtitle;
  final Color color;
  final List<KdsTicket> tickets;
  final int itemCount;
  final String laneStatus;
  final Future<void> Function(KdsTicket ticket, String laneStatus) onTicketTap;
  final Future<void> Function(OrderItemLine item) onItemTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFF0E1625),
        borderRadius: BorderRadius.circular(28),
        border: Border.all(color: Colors.white.withValues(alpha: 0.06)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(14),
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
                        title,
                        style: Theme.of(context).textTheme.titleLarge?.copyWith(
                              color: Colors.white,
                              fontWeight: FontWeight.w900,
                            ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        subtitle,
                        style: const TextStyle(color: Colors.white54),
                      ),
                    ],
                  ),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.14),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Text(
                    '${tickets.length} / $itemCount',
                    style: TextStyle(
                      color: color,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            if (tickets.isEmpty)
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.04),
                  borderRadius: BorderRadius.circular(22),
                ),
                child: const Text(
                  'No tickets in this lane.',
                  style: TextStyle(color: Colors.white54),
                ),
              )
            else
              for (final ticket in tickets)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: _KitchenTicketCard(
                    ticket: ticket,
                    laneStatus: laneStatus,
                    accent: color,
                    onTicketTap: () => onTicketTap(ticket, laneStatus),
                    onItemTap: onItemTap,
                  ),
                ),
          ],
        ),
      ),
    );
  }
}

class _KitchenTicketCard extends StatelessWidget {
  const _KitchenTicketCard({
    required this.ticket,
    required this.laneStatus,
    required this.accent,
    required this.onTicketTap,
    required this.onItemTap,
  });

  final KdsTicket ticket;
  final String laneStatus;
  final Color accent;
  final VoidCallback onTicketTap;
  final Future<void> Function(OrderItemLine item) onItemTap;

  @override
  Widget build(BuildContext context) {
    final items = ticket.items
        .where((item) => item.kdsStatus == laneStatus)
        .toList(growable: false);

    return Material(
      color: const Color(0xFF151F30),
      borderRadius: BorderRadius.circular(24),
      child: InkWell(
        onTap: onTicketTap,
        borderRadius: BorderRadius.circular(24),
        child: Padding(
          padding: const EdgeInsets.all(16),
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
                          'Order #${ticket.id} • ${ticket.tableName}',
                          style:
                              Theme.of(context).textTheme.titleMedium?.copyWith(
                                    color: Colors.white,
                                    fontWeight: FontWeight.w900,
                                  ),
                        ),
                        if ((ticket.waiter ?? '').isNotEmpty) ...[
                          const SizedBox(height: 4),
                          Text(
                            'Waiter ${ticket.waiter}',
                            style: const TextStyle(color: Colors.white60),
                          ),
                        ],
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 8,
                    ),
                    decoration: BoxDecoration(
                      color: accent.withValues(alpha: 0.16),
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Text(
                      '${items.length} items',
                      style: TextStyle(
                        color: accent,
                        fontWeight: FontWeight.w800,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 14),
              for (final item in items)
                Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: _KitchenItemTile(
                    item: item,
                    accent: accent,
                    onTap: () => onItemTap(item),
                  ),
                ),
              const SizedBox(height: 4),
              const Text(
                'Tap card to move the full ticket. Tap any item to move only that line.',
                style: TextStyle(
                  color: Colors.white54,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _KitchenItemTile extends StatelessWidget {
  const _KitchenItemTile({
    required this.item,
    required this.accent,
    required this.onTap,
  });

  final OrderItemLine item;
  final Color accent;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white.withValues(alpha: 0.04),
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: accent.withValues(alpha: 0.14),
                  borderRadius: BorderRadius.circular(16),
                ),
                alignment: Alignment.center,
                child: Text(
                  '${item.quantity}x',
                  style: TextStyle(
                    color: accent,
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
                      item.name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            color: Colors.white,
                            fontWeight: FontWeight.w900,
                          ),
                    ),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        if (item.modifiers.isNotEmpty)
                          _KitchenSignalToken(
                            label: '+ ${item.modifiers.length} mods',
                          ),
                        if ((item.itemNote ?? '').isNotEmpty ||
                            (item.changeNote ?? '').isNotEmpty)
                          _KitchenSignalToken(
                            label: 'NOTE',
                            onTap: () => _showItemNotes(context),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              Icon(
                Icons.chevron_right_rounded,
                color: accent,
                size: 28,
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showItemNotes(BuildContext context) {
    final notes = <String>[
      if ((item.itemNote ?? '').isNotEmpty) 'Order note: ${item.itemNote}',
      if ((item.changeNote ?? '').isNotEmpty) 'Change note: ${item.changeNote}',
      if (item.modifiers.isNotEmpty) 'Modifiers: ${item.modifiers.join(', ')}',
    ];

    showDialog<void>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(item.name),
          content: Text(notes.join('\n')),
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

class _KitchenSignalToken extends StatelessWidget {
  const _KitchenSignalToken({
    required this.label,
    this.onTap,
  });

  final String label;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(999),
        ),
        child: Text(
          label,
          style: const TextStyle(
            color: Color(0xFF111827),
            fontWeight: FontWeight.w800,
            fontSize: 12,
          ),
        ),
      ),
    );
  }
}

class _ActionConfirmButton extends StatelessWidget {
  const _ActionConfirmButton({
    required this.icon,
    required this.label,
    required this.backgroundColor,
    required this.foregroundColor,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final Color backgroundColor;
  final Color foregroundColor;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: backgroundColor,
      borderRadius: BorderRadius.circular(26),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(26),
        child: SizedBox(
          height: 140,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 56, color: foregroundColor),
              const SizedBox(height: 10),
              Text(
                label,
                style: TextStyle(
                  color: foregroundColor,
                  fontWeight: FontWeight.w900,
                  fontSize: 16,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _KitchenMetric extends StatelessWidget {
  const _KitchenMetric({
    required this.title,
    required this.value,
    required this.detail,
    required this.color,
  });

  final String title;
  final String value;
  final String detail;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 180,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(color: Colors.white60)),
          const SizedBox(height: 8),
          Text(
            value,
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  color: color,
                  fontWeight: FontWeight.w900,
                ),
          ),
          const SizedBox(height: 4),
          Text(
            detail,
            style: const TextStyle(color: Colors.white54),
          ),
        ],
      ),
    );
  }
}

class _BoardHint extends StatelessWidget {
  const _BoardHint({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
