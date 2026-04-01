import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/models/app_models.dart';
import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/state_views.dart';
import '../../auth/providers/auth_providers.dart';
import '../../suite/data/suite_repository.dart';

class CustomerWorkspacePage extends ConsumerStatefulWidget {
  const CustomerWorkspacePage({super.key});

  @override
  ConsumerState<CustomerWorkspacePage> createState() =>
      _CustomerWorkspacePageState();
}

class _CustomerWorkspacePageState extends ConsumerState<CustomerWorkspacePage> {
  int _selectedIndex = 0;

  @override
  Widget build(BuildContext context) {
    final session = ref.watch(currentSessionProvider);
    final theme = Theme.of(context);
    final isWide = MediaQuery.of(context).size.width > 1024;
    final pages = [
      const _CustomerHomeTab(),
      const _CustomerRestaurantsTab(),
      const _CustomerOrdersTab(),
      const _CustomerRewardsTab(),
    ];

    final rail = NavigationRail(
      extended: isWide,
      selectedIndex: _selectedIndex,
      onDestinationSelected: (index) => setState(() => _selectedIndex = index),
      destinations: const [
        NavigationRailDestination(
          icon: Icon(Icons.home_outlined),
          selectedIcon: Icon(Icons.home),
          label: Text('Home'),
        ),
        NavigationRailDestination(
          icon: Icon(Icons.store_mall_directory_outlined),
          selectedIcon: Icon(Icons.storefront),
          label: Text('Restaurants'),
        ),
        NavigationRailDestination(
          icon: Icon(Icons.receipt_long_outlined),
          selectedIcon: Icon(Icons.receipt_long),
          label: Text('Orders'),
        ),
        NavigationRailDestination(
          icon: Icon(Icons.workspace_premium_outlined),
          selectedIcon: Icon(Icons.workspace_premium),
          label: Text('Rewards'),
        ),
      ],
    );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFFE86C2F), Color(0xFFF59E0B)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(28),
          ),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Hello, ${session?.name ?? 'Guest'}',
                      style: theme.textTheme.headlineMedium?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'See previous orders, track points, and browse every restaurant running on your platform.',
                      style: theme.textTheme.bodyLarge?.copyWith(
                        color: Colors.white.withValues(alpha: 0.88),
                      ),
                    ),
                  ],
                ),
              ),
              if (!isWide)
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.14),
                    borderRadius: BorderRadius.circular(22),
                  ),
                  padding:
                      const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.workspace_premium, color: Colors.white),
                      const SizedBox(width: 10),
                      Text(
                        '${session?.loyaltyPoints ?? 0} pts',
                        style: theme.textTheme.titleMedium?.copyWith(
                          color: Colors.white,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 20),
        Expanded(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (isWide)
                Card(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    child: rail,
                  ),
                ),
              if (isWide) const SizedBox(width: 16),
              Expanded(child: pages[_selectedIndex]),
            ],
          ),
        ),
        if (!isWide) ...[
          const SizedBox(height: 12),
          NavigationBar(
            selectedIndex: _selectedIndex,
            onDestinationSelected: (index) =>
                setState(() => _selectedIndex = index),
            destinations: const [
              NavigationDestination(
                  icon: Icon(Icons.home_outlined), label: 'Home'),
              NavigationDestination(
                  icon: Icon(Icons.storefront_outlined), label: 'Restaurants'),
              NavigationDestination(
                  icon: Icon(Icons.receipt_long_outlined), label: 'Orders'),
              NavigationDestination(
                  icon: Icon(Icons.workspace_premium_outlined),
                  label: 'Rewards'),
            ],
          ),
        ],
      ],
    );
  }
}

class _CustomerHomeTab extends ConsumerStatefulWidget {
  const _CustomerHomeTab();

  @override
  ConsumerState<_CustomerHomeTab> createState() => _CustomerHomeTabState();
}

class _CustomerHomeTabState extends ConsumerState<_CustomerHomeTab> {
  late Future<CustomerHomeData> _future;

  @override
  void initState() {
    super.initState();
    _future = ref.read(suiteRepositoryProvider).fetchCustomerHome();
  }

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');

    return FutureBuilder<CustomerHomeData>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const LoadingView(label: 'Loading customer home...');
        }
        if (snapshot.hasError) {
          return ErrorView(
            message: snapshot.error.toString(),
            onRetry: () => setState(() {
              _future = ref.read(suiteRepositoryProvider).fetchCustomerHome();
            }),
          );
        }

        final data = snapshot.data!;

        return RefreshIndicator(
          onRefresh: () async {
            setState(() {
              _future = ref.read(suiteRepositoryProvider).fetchCustomerHome();
            });
            await _future;
          },
          child: ListView(
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(22),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Loyalty wallet',
                              style: Theme.of(context).textTheme.labelLarge,
                            ),
                            const SizedBox(height: 8),
                            Text(
                              '${data.loyaltyPoints} points',
                              style: Theme.of(context)
                                  .textTheme
                                  .headlineMedium
                                  ?.copyWith(
                                    fontWeight: FontWeight.w800,
                                  ),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              'Your next visit should already feel personal: branch history, fast re-order memory, and live rewards.',
                              style: Theme.of(context).textTheme.bodyMedium,
                            ),
                          ],
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.all(18),
                        decoration: BoxDecoration(
                          color:
                              const Color(0xFFE86C2F).withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(24),
                        ),
                        child: const Icon(Icons.workspace_premium,
                            size: 36, color: Color(0xFFE86C2F)),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              SectionCard(
                title: 'Registered restaurants',
                trailing: Text('${data.restaurants.length} shown'),
                child: SizedBox(
                  height: 220,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemCount: data.restaurants.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 14),
                    itemBuilder: (context, index) {
                      final restaurant = data.restaurants[index];
                      return SizedBox(
                        width: 260,
                        child: Card(
                          color: const Color(0xFFFFF7EF),
                          child: Padding(
                            padding: const EdgeInsets.all(18),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  restaurant.name,
                                  style: Theme.of(context)
                                      .textTheme
                                      .titleLarge
                                      ?.copyWith(
                                        fontWeight: FontWeight.w800,
                                      ),
                                ),
                                const SizedBox(height: 8),
                                Text('${restaurant.branchCount} branches'),
                                const SizedBox(height: 14),
                                for (final branch
                                    in restaurant.branches.take(3))
                                  Padding(
                                    padding: const EdgeInsets.only(bottom: 8),
                                    child: Row(
                                      children: [
                                        const Icon(Icons.location_on_outlined,
                                            size: 18),
                                        const SizedBox(width: 8),
                                        Expanded(
                                          child: Text(
                                            branch.location == null
                                                ? branch.name
                                                : '${branch.name} • ${branch.location}',
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
              ),
              const SizedBox(height: 16),
              SectionCard(
                title: 'Recent orders',
                child: data.recentOrders.isEmpty
                    ? const EmptyView(
                        title: 'No orders yet',
                        description:
                            'Once a branch attaches your phone to an order, your history will appear here.',
                      )
                    : Column(
                        children: [
                          for (final order in data.recentOrders)
                            ListTile(
                              contentPadding: EdgeInsets.zero,
                              leading: CircleAvatar(
                                backgroundColor: const Color(0xFFE86C2F)
                                    .withValues(alpha: 0.12),
                                child: const Icon(Icons.receipt_long,
                                    color: Color(0xFFE86C2F)),
                              ),
                              title: Text(
                                  '${order.restaurantName ?? 'Restaurant'} • ${order.branchName ?? 'Branch'}'),
                              subtitle: Text(
                                '${order.items.length} items • ${DateFormat('dd MMM, hh:mm a').format(order.createdAt ?? DateTime.now())}',
                              ),
                              trailing: Text(
                                currency.format(order.total),
                                style: Theme.of(context)
                                    .textTheme
                                    .titleMedium
                                    ?.copyWith(fontWeight: FontWeight.w700),
                              ),
                            ),
                        ],
                      ),
              ),
              const SizedBox(height: 16),
              SectionCard(
                title: 'Latest points activity',
                child: data.loyaltyPreview.isEmpty
                    ? const EmptyView(
                        title: 'No reward activity yet',
                        description:
                            'Points earned from paid orders will appear here.',
                        icon: Icons.workspace_premium_outlined,
                      )
                    : Column(
                        children: [
                          for (final entry in data.loyaltyPreview)
                            ListTile(
                              contentPadding: EdgeInsets.zero,
                              leading: CircleAvatar(
                                backgroundColor: entry.type == 'earn'
                                    ? const Color(0xFF0F766E)
                                        .withValues(alpha: 0.12)
                                    : Colors.red.withValues(alpha: 0.12),
                                child: Icon(
                                  entry.type == 'earn'
                                      ? Icons.arrow_upward
                                      : Icons.arrow_downward,
                                  color: entry.type == 'earn'
                                      ? const Color(0xFF0F766E)
                                      : Colors.red,
                                ),
                              ),
                              title: Text(
                                  '${entry.points} points • ${entry.type}'),
                              subtitle: Text(
                                '${entry.restaurantName ?? 'Restaurant'} ${entry.branchName == null ? '' : '• ${entry.branchName}'}',
                              ),
                              trailing: Text(
                                entry.createdAt == null
                                    ? '--'
                                    : DateFormat('dd MMM')
                                        .format(entry.createdAt!),
                              ),
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

class _CustomerRestaurantsTab extends ConsumerStatefulWidget {
  const _CustomerRestaurantsTab();

  @override
  ConsumerState<_CustomerRestaurantsTab> createState() =>
      _CustomerRestaurantsTabState();
}

class _CustomerRestaurantsTabState
    extends ConsumerState<_CustomerRestaurantsTab> {
  final _searchController = TextEditingController();
  final List<RestaurantListing> _items = [];
  PaginationMeta? _meta;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load(reset: true);
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _load({bool reset = false}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final response = await ref.read(suiteRepositoryProvider).fetchRestaurants(
            page: reset ? 1 : (_meta?.currentPage ?? 0) + 1,
            search: _searchController.text.trim(),
          );
      setState(() {
        if (reset) {
          _items
            ..clear()
            ..addAll(response.items);
        } else {
          _items.addAll(response.items);
        }
        _meta = response.meta;
        _loading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading && _items.isEmpty) {
      return const LoadingView(label: 'Loading restaurants...');
    }
    if (_error != null && _items.isEmpty) {
      return ErrorView(message: _error!, onRetry: () => _load(reset: true));
    }

    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _searchController,
                decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.search),
                  hintText: 'Search by restaurant or branch',
                ),
                onSubmitted: (_) => _load(reset: true),
              ),
            ),
            const SizedBox(width: 12),
            ElevatedButton(
              onPressed: () => _load(reset: true),
              child: const Text('Search'),
            ),
          ],
        ),
        const SizedBox(height: 16),
        Expanded(
          child: RefreshIndicator(
            onRefresh: () => _load(reset: true),
            child: ListView.separated(
              itemCount: _items.length + 1,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                if (index == _items.length) {
                  final hasMore = _meta?.hasMore ?? false;
                  if (!hasMore) {
                    return const SizedBox(height: 20);
                  }
                  return Center(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      child: OutlinedButton(
                        onPressed: _loading ? null : () => _load(),
                        child: Text(_loading ? 'Loading...' : 'Load more'),
                      ),
                    ),
                  );
                }
                final restaurant = _items[index];
                return Card(
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                restaurant.name,
                                style: Theme.of(context)
                                    .textTheme
                                    .titleLarge
                                    ?.copyWith(
                                      fontWeight: FontWeight.w800,
                                    ),
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 12, vertical: 8),
                              decoration: BoxDecoration(
                                color: const Color(0xFFE86C2F)
                                    .withValues(alpha: 0.1),
                                borderRadius: BorderRadius.circular(18),
                              ),
                              child: Text('${restaurant.branchCount} branches'),
                            ),
                          ],
                        ),
                        const SizedBox(height: 14),
                        for (final branch in restaurant.branches)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: Row(
                              children: [
                                const Icon(Icons.location_on_outlined,
                                    size: 18),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Text(
                                    branch.location == null
                                        ? branch.name
                                        : '${branch.name} • ${branch.location}',
                                  ),
                                ),
                              ],
                            ),
                          ),
                      ],
                    ),
                  ),
                );
              },
            ),
          ),
        ),
      ],
    );
  }
}

class _CustomerOrdersTab extends ConsumerStatefulWidget {
  const _CustomerOrdersTab();

  @override
  ConsumerState<_CustomerOrdersTab> createState() => _CustomerOrdersTabState();
}

class _CustomerOrdersTabState extends ConsumerState<_CustomerOrdersTab> {
  final List<CustomerOrder> _items = [];
  PaginationMeta? _meta;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load(reset: true);
  }

  Future<void> _load({bool reset = false}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final response =
          await ref.read(suiteRepositoryProvider).fetchCustomerOrders(
                page: reset ? 1 : (_meta?.currentPage ?? 0) + 1,
              );
      setState(() {
        if (reset) {
          _items
            ..clear()
            ..addAll(response.items);
        } else {
          _items.addAll(response.items);
        }
        _meta = response.meta;
        _loading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');
    if (_loading && _items.isEmpty) {
      return const LoadingView(label: 'Loading orders...');
    }
    if (_error != null && _items.isEmpty) {
      return ErrorView(message: _error!, onRetry: () => _load(reset: true));
    }

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView.separated(
        itemCount: _items.length + 1,
        separatorBuilder: (_, __) => const SizedBox(height: 12),
        itemBuilder: (context, index) {
          if (index == _items.length) {
            final hasMore = _meta?.hasMore ?? false;
            if (!hasMore) return const SizedBox(height: 24);
            return Center(
              child: OutlinedButton(
                onPressed: _loading ? null : () => _load(),
                child: Text(_loading ? 'Loading...' : 'Load more'),
              ),
            );
          }

          final order = _items[index];
          return Card(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          '${order.restaurantName ?? 'Restaurant'} • ${order.branchName ?? 'Branch'}',
                          style: Theme.of(context)
                              .textTheme
                              .titleMedium
                              ?.copyWith(fontWeight: FontWeight.w800),
                        ),
                      ),
                      Text(
                        currency.format(order.total),
                        style: Theme.of(context)
                            .textTheme
                            .titleMedium
                            ?.copyWith(fontWeight: FontWeight.w700),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    '${order.status.toUpperCase()} • ${order.paymentStatus.toUpperCase()} • ${order.createdAt == null ? '--' : DateFormat('dd MMM yyyy, hh:mm a').format(order.createdAt!)}',
                  ),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      for (final item in order.items)
                        Chip(
                          label: Text('${item.quantity}x ${item.name}'),
                        ),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _CustomerRewardsTab extends ConsumerStatefulWidget {
  const _CustomerRewardsTab();

  @override
  ConsumerState<_CustomerRewardsTab> createState() =>
      _CustomerRewardsTabState();
}

class _CustomerRewardsTabState extends ConsumerState<_CustomerRewardsTab> {
  final List<LoyaltyEntry> _items = [];
  PaginationMeta? _meta;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load(reset: true);
  }

  Future<void> _load({bool reset = false}) async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final response =
          await ref.read(suiteRepositoryProvider).fetchCustomerLoyalty(
                page: reset ? 1 : (_meta?.currentPage ?? 0) + 1,
              );
      setState(() {
        if (reset) {
          _items
            ..clear()
            ..addAll(response.items);
        } else {
          _items.addAll(response.items);
        }
        _meta = response.meta;
        _loading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final session = ref.watch(currentSessionProvider);
    if (_loading && _items.isEmpty) {
      return const LoadingView(label: 'Loading rewards...');
    }
    if (_error != null && _items.isEmpty) {
      return ErrorView(message: _error!, onRetry: () => _load(reset: true));
    }

    return Column(
      children: [
        Card(
          color: const Color(0xFF0F766E),
          child: Padding(
            padding: const EdgeInsets.all(22),
            child: Row(
              children: [
                const CircleAvatar(
                  radius: 26,
                  backgroundColor: Colors.white24,
                  child: Icon(Icons.workspace_premium, color: Colors.white),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Current points',
                          style: TextStyle(color: Colors.white70)),
                      const SizedBox(height: 4),
                      Text(
                        '${session?.loyaltyPoints ?? 0}',
                        style: Theme.of(context)
                            .textTheme
                            .headlineMedium
                            ?.copyWith(
                              color: Colors.white,
                              fontWeight: FontWeight.w800,
                            ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        Expanded(
          child: RefreshIndicator(
            onRefresh: () => _load(reset: true),
            child: ListView.separated(
              itemCount: _items.length + 1,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                if (index == _items.length) {
                  final hasMore = _meta?.hasMore ?? false;
                  if (!hasMore) return const SizedBox(height: 20);
                  return Center(
                    child: OutlinedButton(
                      onPressed: _loading ? null : () => _load(),
                      child: Text(_loading ? 'Loading...' : 'Load more'),
                    ),
                  );
                }

                final entry = _items[index];
                final earned = entry.type == 'earn';
                return Card(
                  child: ListTile(
                    leading: CircleAvatar(
                      backgroundColor: earned
                          ? const Color(0xFF0F766E).withValues(alpha: 0.12)
                          : Colors.red.withValues(alpha: 0.12),
                      child: Icon(
                        earned ? Icons.north_east : Icons.south_east,
                        color: earned ? const Color(0xFF0F766E) : Colors.red,
                      ),
                    ),
                    title: Text('${earned ? '+' : '-'}${entry.points} points'),
                    subtitle: Text(
                      '${entry.restaurantName ?? 'Restaurant'} ${entry.branchName == null ? '' : '• ${entry.branchName}'}',
                    ),
                    trailing: Text(
                      entry.createdAt == null
                          ? '--'
                          : DateFormat('dd MMM').format(entry.createdAt!),
                    ),
                  ),
                );
              },
            ),
          ),
        ),
      ],
    );
  }
}
