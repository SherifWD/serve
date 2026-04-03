import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/models/app_models.dart';
import '../../../core/widgets/branded_image.dart';
import '../../../core/widgets/state_views.dart';
import '../../auth/providers/auth_providers.dart';
import '../../suite/data/suite_repository.dart';

enum _CustomerBrowseFilter {
  all,
  restaurants,
  cafes;

  String? get apiValue {
    switch (this) {
      case _CustomerBrowseFilter.all:
        return null;
      case _CustomerBrowseFilter.restaurants:
        return 'restaurant';
      case _CustomerBrowseFilter.cafes:
        return 'cafe';
    }
  }
}

class CustomerWorkspacePage extends ConsumerStatefulWidget {
  const CustomerWorkspacePage({super.key});

  @override
  ConsumerState<CustomerWorkspacePage> createState() =>
      _CustomerWorkspacePageState();
}

class _CustomerWorkspacePageState extends ConsumerState<CustomerWorkspacePage> {
  int _selectedIndex = 0;
  String _browseSearchSeed = '';
  _CustomerBrowseFilter _browseFilter = _CustomerBrowseFilter.all;
  int _browseFocusSignal = 0;

  void _selectTab(int index) {
    setState(() => _selectedIndex = index);
  }

  void _openBrowse({
    String search = '',
    _CustomerBrowseFilter filter = _CustomerBrowseFilter.all,
    bool focusSearch = false,
  }) {
    setState(() {
      _selectedIndex = 1;
      _browseSearchSeed = search;
      _browseFilter = filter;
      if (focusSearch) {
        _browseFocusSignal++;
      }
    });
  }

  void _openRewards() {
    setState(() => _selectedIndex = 3);
  }

  void _openOrders() {
    setState(() => _selectedIndex = 2);
  }

  void _showCustomerAccount(AppSession? session) {
    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _CustomerAccountSheet(session: session),
    );
  }

  void _showNotifications() {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text('No new customer notifications right now.')),
    );
  }

  Future<void> _openRestaurant(
    RestaurantListing restaurant, {
    int? initialProductId,
  }) async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (context) => _RestaurantDetailPage(
          restaurant: restaurant,
          initialProductId: initialProductId,
        ),
      ),
    );
  }

  Future<void> _openOrder(CustomerOrder order) async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _CustomerOrderDetailSheet(orderId: order.id),
    );
  }

  void _openRewardEntry(LoyaltyEntry entry) {
    if (entry.orderId != null) {
      showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        showDragHandle: true,
        backgroundColor: Colors.transparent,
        builder: (context) => _CustomerOrderDetailSheet(orderId: entry.orderId!),
      );
      return;
    }

    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _RewardEntryDetailSheet(entry: entry),
    );
  }

  @override
  Widget build(BuildContext context) {
    final session = ref.watch(currentSessionProvider);
    final isWide = MediaQuery.of(context).size.width > 1080;
    final pages = [
      _CustomerHomeTab(
        onBrowseRequested: _openBrowse,
        onRewardsRequested: _openRewards,
        onOrdersRequested: _openOrders,
        onRestaurantSelected: _openRestaurant,
        onOrderSelected: _openOrder,
        onRewardSelected: _openRewardEntry,
      ),
      _CustomerRestaurantsTab(
        searchSeed: _browseSearchSeed,
        filterSeed: _browseFilter,
        focusSignal: _browseFocusSignal,
        onRestaurantSelected: _openRestaurant,
      ),
      _CustomerOrdersTab(onOrderSelected: _openOrder),
      _CustomerRewardsTab(
        onOrdersRequested: _openOrders,
        onRewardSelected: _openRewardEntry,
      ),
    ];

    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFFFFFAF5), Color(0xFFFFF2E7), Color(0xFFFFFCF7)],
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
        ),
      ),
      child: SafeArea(
        child: Column(
          children: [
            _CustomerTopChrome(
              session: session,
              selectedIndex: _selectedIndex,
              onSearchTap: () => _openBrowse(focusSearch: true),
              onNotificationsTap: _showNotifications,
              onBrowseRequested: _openBrowse,
              onRewardsRequested: _openRewards,
              onAccountRequested: () => _showCustomerAccount(session),
            ),
            Expanded(
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (isWide)
                    Padding(
                      padding: const EdgeInsets.fromLTRB(18, 0, 0, 18),
                      child: _CustomerRail(
                        selectedIndex: _selectedIndex,
                        onSelected: _selectTab,
                      ),
                    ),
                  Expanded(
                    child: Padding(
                      padding: EdgeInsets.fromLTRB(isWide ? 16 : 14, 0, 14, 14),
                      child: AnimatedSwitcher(
                        duration: const Duration(milliseconds: 220),
                        child: KeyedSubtree(
                          key: ValueKey(_selectedIndex),
                          child: pages[_selectedIndex],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            if (!isWide)
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
                child: NavigationBar(
                  selectedIndex: _selectedIndex,
                  onDestinationSelected: _selectTab,
                  destinations: const [
                    NavigationDestination(
                      icon: Icon(Icons.home_outlined),
                      selectedIcon: Icon(Icons.home_rounded),
                      label: 'Home',
                    ),
                    NavigationDestination(
                      icon: Icon(Icons.search_outlined),
                      selectedIcon: Icon(Icons.search_rounded),
                      label: 'Browse',
                    ),
                    NavigationDestination(
                      icon: Icon(Icons.receipt_long_outlined),
                      selectedIcon: Icon(Icons.receipt_long_rounded),
                      label: 'Orders',
                    ),
                    NavigationDestination(
                      icon: Icon(Icons.workspace_premium_outlined),
                      selectedIcon: Icon(Icons.workspace_premium_rounded),
                      label: 'Rewards',
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

class _CustomerTopChrome extends ConsumerWidget {
  const _CustomerTopChrome({
    required this.session,
    required this.selectedIndex,
    required this.onSearchTap,
    required this.onNotificationsTap,
    required this.onBrowseRequested,
    required this.onRewardsRequested,
    required this.onAccountRequested,
  });

  final AppSession? session;
  final int selectedIndex;
  final VoidCallback onSearchTap;
  final VoidCallback onNotificationsTap;
  final VoidCallback onRewardsRequested;
  final VoidCallback onAccountRequested;
  final void Function({
    String search,
    _CustomerBrowseFilter filter,
    bool focusSearch,
  }) onBrowseRequested;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Delivering the restaurant suite memory to',
                      style: theme.textTheme.labelLarge?.copyWith(
                        color: const Color(0xFF8B6B4C),
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      session?.name ?? 'Guest',
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w900,
                        color: const Color(0xFF23150E),
                      ),
                    ),
                  ],
                ),
              ),
              _RoundIconButton(
                icon: Icons.notifications_none_rounded,
                onPressed: onNotificationsTap,
              ),
              const SizedBox(width: 10),
              PopupMenuButton<String>(
                onSelected: (value) {
                  if (value == 'account') {
                    onAccountRequested();
                  } else if (value == 'logout') {
                    ref.read(authProvider.notifier).logout();
                  }
                },
                itemBuilder: (context) => const [
                  PopupMenuItem<String>(
                    value: 'account',
                    child: Text('Account'),
                  ),
                  PopupMenuItem<String>(
                    value: 'logout',
                    child: Text('Logout'),
                  ),
                ],
                child: Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  decoration: BoxDecoration(
                    color: const Color(0xFF20140E),
                    borderRadius: BorderRadius.circular(22),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      CircleAvatar(
                        radius: 12,
                        backgroundColor: const Color(0xFFFFB474),
                        child: Text(
                          ((session?.name.isNotEmpty ?? false)
                                  ? session!.name[0]
                                  : 'G')
                              .toUpperCase(),
                          style: const TextStyle(
                            color: Color(0xFF1B110A),
                            fontWeight: FontWeight.w800,
                            fontSize: 12,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      const Icon(
                        Icons.keyboard_arrow_down_rounded,
                        color: Colors.white,
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          DecoratedBox(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(22),
              boxShadow: const [
                BoxShadow(
                  blurRadius: 22,
                  offset: Offset(0, 14),
                  color: Color(0x12000000),
                ),
              ],
            ),
            child: TextField(
              readOnly: true,
              onTap: onSearchTap,
              decoration: InputDecoration(
                prefixIcon: const Icon(Icons.search_rounded),
                suffixIcon: Icon(
                  selectedIndex == 0
                      ? Icons.local_offer_outlined
                      : Icons.tune_rounded,
                ),
                hintText: selectedIndex == 0
                    ? 'Search for restaurant, dish or branch'
                    : 'Search restaurants, cafes or dishes',
                border: InputBorder.none,
              ),
            ),
          ),
          const SizedBox(height: 14),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _QuickServiceChip(
                  icon: Icons.ramen_dining_rounded,
                  label: 'Food',
                  onTap: () => onBrowseRequested(
                    filter: _CustomerBrowseFilter.restaurants,
                    focusSearch: true,
                  ),
                ),
                const SizedBox(width: 10),
                _QuickServiceChip(
                  icon: Icons.local_cafe_rounded,
                  label: 'Cafe',
                  onTap: () => onBrowseRequested(
                    filter: _CustomerBrowseFilter.cafes,
                    focusSearch: true,
                  ),
                ),
                const SizedBox(width: 10),
                _QuickServiceChip(
                  icon: Icons.icecream_rounded,
                  label: 'Desserts',
                  onTap: () => onBrowseRequested(
                    filter: _CustomerBrowseFilter.cafes,
                    focusSearch: true,
                  ),
                ),
                const SizedBox(width: 10),
                _QuickServiceChip(
                  icon: Icons.breakfast_dining_rounded,
                  label: 'Breakfast',
                  onTap: () => onBrowseRequested(focusSearch: true),
                ),
                const SizedBox(width: 10),
                _QuickServiceChip(
                  icon: Icons.workspace_premium_rounded,
                  label: 'Rewards',
                  onTap: onRewardsRequested,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _CustomerRail extends StatelessWidget {
  const _CustomerRail({
    required this.selectedIndex,
    required this.onSelected,
  });

  final int selectedIndex;
  final ValueChanged<int> onSelected;

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
      child: SizedBox(
        width: 112,
        child: NavigationRail(
          selectedIndex: selectedIndex,
          onDestinationSelected: onSelected,
          labelType: NavigationRailLabelType.all,
          destinations: const [
            NavigationRailDestination(
              icon: Icon(Icons.home_outlined),
              selectedIcon: Icon(Icons.home_rounded),
              label: Text('Home'),
            ),
            NavigationRailDestination(
              icon: Icon(Icons.storefront_outlined),
              selectedIcon: Icon(Icons.storefront_rounded),
              label: Text('Browse'),
            ),
            NavigationRailDestination(
              icon: Icon(Icons.receipt_long_outlined),
              selectedIcon: Icon(Icons.receipt_long_rounded),
              label: Text('Orders'),
            ),
            NavigationRailDestination(
              icon: Icon(Icons.workspace_premium_outlined),
              selectedIcon: Icon(Icons.workspace_premium_rounded),
              label: Text('Rewards'),
            ),
          ],
        ),
      ),
    );
  }
}

class _CustomerHomeTab extends ConsumerStatefulWidget {
  const _CustomerHomeTab({
    required this.onBrowseRequested,
    required this.onRewardsRequested,
    required this.onOrdersRequested,
    required this.onRestaurantSelected,
    required this.onOrderSelected,
    required this.onRewardSelected,
  });

  final void Function({
    String search,
    _CustomerBrowseFilter filter,
    bool focusSearch,
  }) onBrowseRequested;
  final VoidCallback onRewardsRequested;
  final VoidCallback onOrdersRequested;
  final Future<void> Function(
    RestaurantListing restaurant, {
    int? initialProductId,
  }) onRestaurantSelected;
  final Future<void> Function(CustomerOrder order) onOrderSelected;
  final ValueChanged<LoyaltyEntry> onRewardSelected;

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
        final restaurants = data.restaurants
            .where((restaurant) => restaurant.kind != 'cafe')
            .toList(growable: false);
        final cafes = data.restaurants
            .where((restaurant) => restaurant.kind == 'cafe')
            .toList(growable: false);
        final featuredDishes = data.restaurants
            .expand(
              (restaurant) => restaurant.featuredItems.map(
                (item) => _FeaturedDishEntry(
                  restaurant: restaurant,
                  item: item,
                ),
              ),
            )
            .take(10)
            .toList(growable: false);

        return RefreshIndicator(
          onRefresh: () async {
            setState(() {
              _future = ref.read(suiteRepositoryProvider).fetchCustomerHome();
            });
            await _future;
          },
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            children: [
              _HeroSlider(
                slides: const [
                  _HeroSlideData(
                    title: 'Your favorite restaurants, remembered by branch.',
                    subtitle:
                        'Talabat-inspired discovery for dine-in history, reorder speed, and offers.',
                    eyebrow: 'Fast reorder',
                    action: 'See popular brands',
                  ),
                  _HeroSlideData(
                    title: 'Coffee runs, desserts, and brunch spots in one flow.',
                    subtitle:
                        'Browse restaurants and cafes the same way a modern marketplace app should feel.',
                    eyebrow: 'Cafe picks',
                    action: 'Open cafe section',
                  ),
                  _HeroSlideData(
                    title: 'Rewards stay visible while you browse.',
                    subtitle:
                        'Your points, recent orders, and new restaurants stay on the same home surface.',
                    eyebrow: 'Loyalty',
                    action: 'Use your points',
                  ),
                ],
                onActionTap: (index) {
                  switch (index) {
                    case 0:
                      widget.onBrowseRequested(
                        filter: _CustomerBrowseFilter.restaurants,
                      );
                      break;
                    case 1:
                      widget.onBrowseRequested(
                        filter: _CustomerBrowseFilter.cafes,
                      );
                      break;
                    case 2:
                      widget.onRewardsRequested();
                      break;
                  }
                },
              ),
              const SizedBox(height: 18),
              _WalletSnapshotCard(
                customerName: data.name,
                loyaltyPoints: data.loyaltyPoints,
                ordersCount: data.recentOrders.length,
                onTap: widget.onRewardsRequested,
              ),
              const SizedBox(height: 22),
              const _SectionTitle(
                title: 'Order again',
                subtitle: 'The last meals you had, with branch memory and one-tap style cards.',
              ),
              const SizedBox(height: 12),
              if (data.recentOrders.isEmpty)
                const _SoftEmptyCard(
                  title: 'No previous orders yet',
                  description:
                      'When a restaurant attaches your phone to a paid order, it will appear here with branch and item memory.',
                )
              else
                SizedBox(
                  height: 254,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemCount: data.recentOrders.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 12),
                    itemBuilder: (context, index) => _OrderAgainCard(
                      order: data.recentOrders[index],
                      currency: currency,
                      onTap: () => widget.onOrderSelected(data.recentOrders[index]),
                      onPrimaryAction: () async {
                        final order = data.recentOrders[index];
                        if (order.restaurantId != null) {
                          RestaurantListing? restaurant;
                          for (final item in data.restaurants) {
                            if (item.id == order.restaurantId) {
                              restaurant = item;
                              break;
                            }
                          }
                          if (restaurant != null) {
                            await widget.onRestaurantSelected(restaurant);
                            return;
                          }
                        }
                        await widget.onOrderSelected(order);
                      },
                    ),
                  ),
                ),
              const SizedBox(height: 22),
              const _SectionTitle(
                title: 'Popular restaurants',
                subtitle: 'Image-first brand cards with multi-branch visibility.',
              ),
              const SizedBox(height: 12),
              if (restaurants.isEmpty)
                const _SoftEmptyCard(
                  title: 'No restaurants available',
                  description:
                      'Restaurants seeded into the suite will appear here automatically.',
                )
              else
                SizedBox(
                  height: 286,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemCount: restaurants.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 12),
                    itemBuilder: (context, index) => _RestaurantShowcaseCard(
                      restaurant: restaurants[index],
                      onTap: () => widget.onRestaurantSelected(restaurants[index]),
                    ),
                  ),
                ),
              const SizedBox(height: 22),
              const _SectionTitle(
                title: 'Cafe and dessert picks',
                subtitle: 'Separate cafe visibility, like a marketplace should provide.',
              ),
              const SizedBox(height: 12),
              if (cafes.isEmpty)
                const _SoftEmptyCard(
                  title: 'No cafes seeded yet',
                  description:
                      'Cafes and bakeries will appear here once they exist in the suite.',
                )
              else
                SizedBox(
                  height: 230,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemCount: cafes.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 12),
                    itemBuilder: (context, index) => _CompactVenueCard(
                      restaurant: cafes[index],
                      onTap: () => widget.onRestaurantSelected(cafes[index]),
                    ),
                  ),
                ),
              const SizedBox(height: 22),
              const _SectionTitle(
                title: 'Featured dishes on the platform',
                subtitle: 'Menu items with image surfaces, ready for visual browsing.',
              ),
              const SizedBox(height: 12),
              if (featuredDishes.isEmpty)
                const _SoftEmptyCard(
                  title: 'No featured dishes yet',
                  description:
                      'Once product images are uploaded they will appear here automatically. Until then the app shows rich default artwork.',
                )
              else
                SizedBox(
                  height: 232,
                  child: ListView.separated(
                    scrollDirection: Axis.horizontal,
                    itemCount: featuredDishes.length,
                    separatorBuilder: (_, __) => const SizedBox(width: 12),
                    itemBuilder: (context, index) => _FeaturedDishCard(
                      entry: featuredDishes[index],
                      currency: currency,
                      onTap: () => widget.onRestaurantSelected(
                        featuredDishes[index].restaurant,
                        initialProductId: featuredDishes[index].item.id,
                      ),
                    ),
                  ),
                ),
              const SizedBox(height: 22),
              const _SectionTitle(
                title: 'Latest rewards activity',
                subtitle: 'Points earned or redeemed across restaurants and branches.',
              ),
              const SizedBox(height: 12),
              Card(
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(28),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(18),
                  child: Column(
                    children: [
                      for (final entry in data.loyaltyPreview.take(4))
                        _RewardEntryRow(
                          entry: entry,
                          onTap: () => widget.onRewardSelected(entry),
                        ),
                    ],
                  ),
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
  const _CustomerRestaurantsTab({
    required this.searchSeed,
    required this.filterSeed,
    required this.focusSignal,
    required this.onRestaurantSelected,
  });

  final String searchSeed;
  final _CustomerBrowseFilter filterSeed;
  final int focusSignal;
  final Future<void> Function(
    RestaurantListing restaurant, {
    int? initialProductId,
  }) onRestaurantSelected;

  @override
  ConsumerState<_CustomerRestaurantsTab> createState() =>
      _CustomerRestaurantsTabState();
}

class _CustomerRestaurantsTabState
    extends ConsumerState<_CustomerRestaurantsTab> {
  final _searchController = TextEditingController();
  final _searchFocusNode = FocusNode();
  final _scrollController = ScrollController();
  final List<RestaurantListing> _items = [];
  PaginationMeta? _meta;
  bool _loading = true;
  String? _error;
  _CustomerBrowseFilter _activeFilter = _CustomerBrowseFilter.all;

  @override
  void initState() {
    super.initState();
    _searchController.text = widget.searchSeed;
    _activeFilter = widget.filterSeed;
    _scrollController.addListener(_maybeLoadMore);
    _load(reset: true);
  }

  @override
  void didUpdateWidget(covariant _CustomerRestaurantsTab oldWidget) {
    super.didUpdateWidget(oldWidget);

    final shouldReload =
        oldWidget.searchSeed != widget.searchSeed ||
            oldWidget.filterSeed != widget.filterSeed;

    if (oldWidget.searchSeed != widget.searchSeed) {
      _searchController.text = widget.searchSeed;
      _searchController.selection = TextSelection.collapsed(
        offset: _searchController.text.length,
      );
    }

    if (oldWidget.filterSeed != widget.filterSeed) {
      _activeFilter = widget.filterSeed;
    }

    if (shouldReload) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        _load(reset: true);
      });
    }

    if (oldWidget.focusSignal != widget.focusSignal) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        _searchFocusNode.requestFocus();
      });
    }
  }

  @override
  void dispose() {
    _scrollController
      ..removeListener(_maybeLoadMore)
      ..dispose();
    _searchFocusNode.dispose();
    _searchController.dispose();
    super.dispose();
  }

  void _maybeLoadMore() {
    if (!_scrollController.hasClients || _loading) return;
    final position = _scrollController.position;
    if (position.pixels >= position.maxScrollExtent - 240 &&
        (_meta?.hasMore ?? false)) {
      _load();
    }
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading && !reset) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final response = await ref.read(suiteRepositoryProvider).fetchRestaurants(
            page: reset ? 1 : (_meta?.currentPage ?? 0) + 1,
            perPage: 12,
            search: _searchController.text.trim(),
            kind: _activeFilter.apiValue,
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
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const _SectionTitle(
          title: 'Restaurants and cafes',
          subtitle: 'Talabat-style browse sections with image-forward venue cards.',
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: TextField(
                controller: _searchController,
                focusNode: _searchFocusNode,
                decoration: const InputDecoration(
                  prefixIcon: Icon(Icons.search_rounded),
                  hintText: 'Search restaurant, cafe or branch area',
                ),
                onSubmitted: (_) => _load(reset: true),
              ),
            ),
            const SizedBox(width: 12),
            FilledButton.icon(
              onPressed: () => _load(reset: true),
              icon: const Icon(Icons.search_rounded),
              label: const Text('Search'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: Row(
            children: [
              _FilterPill(
                icon: Icons.apps_rounded,
                label: 'All',
                selected: _activeFilter == _CustomerBrowseFilter.all,
                onTap: () {
                  setState(() => _activeFilter = _CustomerBrowseFilter.all);
                  _load(reset: true);
                },
              ),
              const SizedBox(width: 8),
              _FilterPill(
                icon: Icons.ramen_dining_rounded,
                label: 'Food',
                selected: _activeFilter == _CustomerBrowseFilter.restaurants,
                onTap: () {
                  setState(() {
                    _activeFilter = _CustomerBrowseFilter.restaurants;
                  });
                  _load(reset: true);
                },
              ),
              const SizedBox(width: 8),
              _FilterPill(
                icon: Icons.local_cafe_rounded,
                label: 'Cafe',
                selected: _activeFilter == _CustomerBrowseFilter.cafes,
                onTap: () {
                  setState(() => _activeFilter = _CustomerBrowseFilter.cafes);
                  _load(reset: true);
                },
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        Expanded(
          child: RefreshIndicator(
            onRefresh: () => _load(reset: true),
            child: ListView.separated(
              controller: _scrollController,
              physics: const AlwaysScrollableScrollPhysics(),
              itemCount: _items.length + 1,
              separatorBuilder: (_, __) => const SizedBox(height: 14),
              itemBuilder: (context, index) {
                if (index == _items.length) {
                  final hasMore = _meta?.hasMore ?? false;
                  if (!hasMore) return const SizedBox(height: 28);
                  return Center(
                    child: OutlinedButton.icon(
                      onPressed: _loading ? null : () => _load(),
                      icon: const Icon(Icons.expand_more_rounded),
                      label: Text(_loading ? 'Loading...' : 'Load more'),
                    ),
                  );
                }

                return _RestaurantSearchCard(
                  restaurant: _items[index],
                  onTap: () => widget.onRestaurantSelected(_items[index]),
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
  const _CustomerOrdersTab({required this.onOrderSelected});

  final Future<void> Function(CustomerOrder order) onOrderSelected;

  @override
  ConsumerState<_CustomerOrdersTab> createState() => _CustomerOrdersTabState();
}

class _CustomerOrdersTabState extends ConsumerState<_CustomerOrdersTab> {
  final _scrollController = ScrollController();
  final List<CustomerOrder> _items = [];
  PaginationMeta? _meta;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_maybeLoadMore);
    _load(reset: true);
  }

  @override
  void dispose() {
    _scrollController
      ..removeListener(_maybeLoadMore)
      ..dispose();
    super.dispose();
  }

  void _maybeLoadMore() {
    if (!_scrollController.hasClients || _loading) return;
    final position = _scrollController.position;
    if (position.pixels >= position.maxScrollExtent - 220 &&
        (_meta?.hasMore ?? false)) {
      _load();
    }
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading && !reset) return;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final response = await ref.read(suiteRepositoryProvider).fetchCustomerOrders(
            page: reset ? 1 : (_meta?.currentPage ?? 0) + 1,
            perPage: 10,
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
        controller: _scrollController,
        physics: const AlwaysScrollableScrollPhysics(),
        itemCount: _items.length + 1,
        separatorBuilder: (_, __) => const SizedBox(height: 14),
        itemBuilder: (context, index) {
          if (index == _items.length) {
            final hasMore = _meta?.hasMore ?? false;
            if (!hasMore) return const SizedBox(height: 24);
            return Center(
              child: OutlinedButton.icon(
                onPressed: _loading ? null : () => _load(),
                icon: const Icon(Icons.expand_more_rounded),
                label: Text(_loading ? 'Loading...' : 'Load more orders'),
              ),
            );
          }

          return _CustomerOrderCard(
            order: _items[index],
            currency: currency,
            onTap: () => widget.onOrderSelected(_items[index]),
          );
        },
      ),
    );
  }
}

class _CustomerRewardsTab extends ConsumerStatefulWidget {
  const _CustomerRewardsTab({
    required this.onOrdersRequested,
    required this.onRewardSelected,
  });

  final VoidCallback onOrdersRequested;
  final ValueChanged<LoyaltyEntry> onRewardSelected;

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
      final response = await ref
          .read(suiteRepositoryProvider)
          .fetchCustomerLoyalty(page: reset ? 1 : (_meta?.currentPage ?? 0) + 1);
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

    return RefreshIndicator(
      onRefresh: () => _load(reset: true),
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
              _WalletSnapshotCard(
                customerName: session?.name ?? 'Guest',
                loyaltyPoints: session?.loyaltyPoints ?? 0,
                ordersCount: _items.length,
                onTap: () {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(
                      content: Text(
                        'Your current points are already reflected in this rewards wallet.',
                      ),
                    ),
                  );
                },
              ),
          const SizedBox(height: 18),
          const _SectionTitle(
            title: 'Reward perks',
            subtitle: 'A simple, visible wallet section just like a strong marketplace loyalty flow should feel.',
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _RewardPerkCard(
                  icon: Icons.local_offer_rounded,
                  title: 'Redeem points',
                  description: 'Use points on your next dine-in experience.',
                  onTap: () {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(
                        content: Text(
                          'Redemption is applied by the restaurant at checkout for now.',
                        ),
                      ),
                    );
                  },
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _RewardPerkCard(
                  icon: Icons.history_toggle_off_rounded,
                  title: 'Order memory',
                  description: 'Every paid visit helps your next browse feel faster.',
                  onTap: widget.onOrdersRequested,
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Card(
            elevation: 0,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(28),
            ),
            child: Padding(
              padding: const EdgeInsets.all(18),
                child: Column(
                  children: [
                  for (final entry in _items)
                    _RewardEntryRow(
                      entry: entry,
                      onTap: () => widget.onRewardSelected(entry),
                    ),
                  if (_meta?.hasMore ?? false) ...[
                    const SizedBox(height: 10),
                    OutlinedButton.icon(
                      onPressed: _loading ? null : () => _load(),
                      icon: const Icon(Icons.expand_more_rounded),
                      label: Text(_loading ? 'Loading...' : 'Load more'),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _HeroSlider extends StatefulWidget {
  const _HeroSlider({
    required this.slides,
    required this.onActionTap,
  });

  final List<_HeroSlideData> slides;
  final ValueChanged<int> onActionTap;

  @override
  State<_HeroSlider> createState() => _HeroSliderState();
}

class _HeroSliderState extends State<_HeroSlider> {
  late final PageController _controller;
  int _page = 0;

  @override
  void initState() {
    super.initState();
    _controller = PageController(viewportFraction: 0.92);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        SizedBox(
          height: 216,
          child: PageView.builder(
            controller: _controller,
            itemCount: widget.slides.length,
            onPageChanged: (value) => setState(() => _page = value),
            itemBuilder: (context, index) {
              final slide = widget.slides[index];
              return Padding(
                padding: const EdgeInsets.only(right: 12),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(30),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      BrandedImage(
                        label: slide.title,
                        kind: BrandedImageKind.hero,
                        overlay: const LinearGradient(
                          colors: [Color(0x22000000), Color(0xBB160B06)],
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                        ),
                      ),
                      Padding(
                        padding: const EdgeInsets.all(20),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 11,
                                vertical: 7,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.18),
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: Text(
                                slide.eyebrow,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                            const Spacer(),
                            Text(
                              slide.title,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: Theme.of(context)
                                  .textTheme
                                  .titleLarge
                                  ?.copyWith(
                                    color: Colors.white,
                                    fontWeight: FontWeight.w900,
                                  ),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              slide.subtitle,
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                color: Colors.white70,
                                height: 1.3,
                              ),
                            ),
                            const SizedBox(height: 10),
                            FilledButton.tonalIcon(
                              onPressed: () => widget.onActionTap(index),
                              style: FilledButton.styleFrom(
                                foregroundColor: const Color(0xFFFF7B2C),
                                backgroundColor: Colors.white,
                                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                                visualDensity: VisualDensity.compact,
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 14,
                                  vertical: 10,
                                ),
                              ),
                              icon: const Icon(Icons.arrow_forward_rounded),
                              label: Text(slide.action),
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
        const SizedBox(height: 10),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(
            widget.slides.length,
            (index) => AnimatedContainer(
              duration: const Duration(milliseconds: 220),
              margin: const EdgeInsets.symmetric(horizontal: 4),
              width: _page == index ? 24 : 8,
              height: 8,
              decoration: BoxDecoration(
                color: _page == index
                    ? const Color(0xFFFF7B2C)
                    : const Color(0xFFFFD3B3),
                borderRadius: BorderRadius.circular(999),
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _HeroSlideData {
  const _HeroSlideData({
    required this.title,
    required this.subtitle,
    required this.eyebrow,
    required this.action,
  });

  final String title;
  final String subtitle;
  final String eyebrow;
  final String action;
}

class _WalletSnapshotCard extends StatelessWidget {
  const _WalletSnapshotCard({
    required this.customerName,
    required this.loyaltyPoints,
    required this.ordersCount,
    required this.onTap,
  });

  final String customerName;
  final int loyaltyPoints;
  final int ordersCount;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(28),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(28),
          child: SizedBox(
            height: 208,
            child: Stack(
              fit: StackFit.expand,
              children: [
                const BrandedImage(
                  label: 'Rewards wallet',
                  kind: BrandedImageKind.hero,
                  overlay: LinearGradient(
                    colors: [Color(0x33000000), Color(0xCC1C0C06)],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.all(22),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Restaurant Suite Rewards',
                        style: TextStyle(
                          color: Colors.white70,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const Spacer(),
                      Text(
                        '$loyaltyPoints points',
                        style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                              color: Colors.white,
                              fontWeight: FontWeight.w900,
                            ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        '$customerName has $ordersCount recent orders remembered in the suite.',
                        style: const TextStyle(color: Colors.white70, height: 1.4),
                      ),
                      const SizedBox(height: 14),
                      const Wrap(
                        spacing: 10,
                        runSpacing: 10,
                        children: [
                          _MetricBadge(
                            label: 'Visible rewards',
                            icon: Icons.workspace_premium_rounded,
                            color: Color(0xFFFFB347),
                          ),
                          _MetricBadge(
                            label: 'Reorder ready',
                            icon: Icons.history_rounded,
                            color: Color(0xFF5DD39E),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _OrderAgainCard extends StatelessWidget {
  const _OrderAgainCard({
    required this.order,
    required this.currency,
    required this.onTap,
    required this.onPrimaryAction,
  });

  final CustomerOrder order;
  final NumberFormat currency;
  final VoidCallback onTap;
  final VoidCallback onPrimaryAction;

  @override
  Widget build(BuildContext context) {
    final firstItem = order.items.isNotEmpty ? order.items.first : null;

    return SizedBox(
      width: 258,
      child: Card(
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onTap,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(
                height: 82,
                child: ClipRRect(
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(28),
                  ),
                  child: BrandedImage(
                    label: firstItem?.name ?? order.restaurantName ?? 'Order',
                    imageUrl: firstItem?.imageUrl,
                    kind: BrandedImageKind.dish,
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      order.restaurantName ?? 'Restaurant',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: Theme.of(context).textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w900,
                          ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      '${order.branchName ?? 'Branch'} • ${order.items.length} items',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Color(0xFF8B6B4C),
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      firstItem?.name ?? 'Order memory',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            currency.format(order.total),
                            style: Theme.of(context)
                                .textTheme
                                .titleSmall
                                ?.copyWith(fontWeight: FontWeight.w900),
                          ),
                        ),
                        FilledButton.tonal(
                          onPressed: onPrimaryAction,
                          style: FilledButton.styleFrom(
                            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                            visualDensity: VisualDensity.compact,
                            padding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 8,
                            ),
                            textStyle: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          child: const Text('Again'),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _RestaurantShowcaseCard extends StatelessWidget {
  const _RestaurantShowcaseCard({
    required this.restaurant,
    required this.onTap,
  });

  final RestaurantListing restaurant;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final featuredText = restaurant.featuredItems
        .map((item) => item.name)
        .take(2)
        .join(' • ');

    return SizedBox(
      width: 286,
      child: Card(
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onTap,
          child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              height: 152,
              child: ClipRRect(
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(30),
                ),
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    BrandedImage(
                      label: restaurant.name,
                      imageUrl: restaurant.coverImageUrl,
                      kind: BrandedImageKind.venue,
                    ),
                    Padding(
                      padding: const EdgeInsets.all(14),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _MiniBadge(
                            label: restaurant.kind == 'cafe' ? 'Cafe' : 'Dining',
                            color: Colors.white.withValues(alpha: 0.90),
                            textColor: const Color(0xFF20110A),
                          ),
                          const Spacer(),
                          _MiniBadge(
                            label: '${restaurant.branchCount} br.',
                            color: const Color(0xDD20110A),
                            textColor: Colors.white,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    restaurant.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w900,
                        ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    restaurant.branches.isEmpty
                        ? 'Multi-branch venue'
                        : restaurant.branches
                            .take(2)
                            .map((branch) =>
                                branch.location ?? branch.name)
                            .join(' • '),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(color: Color(0xFF8B6B4C)),
                  ),
                  if (featuredText.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    Text(
                      featuredText,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                  ],
                ],
              ),
            ),
          ],
        )),
      ),
    );
  }
}

class _CompactVenueCard extends StatelessWidget {
  const _CompactVenueCard({
    required this.restaurant,
    required this.onTap,
  });

  final RestaurantListing restaurant;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 232,
      child: Card(
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(26)),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onTap,
          child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              height: 110,
              child: ClipRRect(
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(26),
                ),
                child: BrandedImage(
                  label: restaurant.name,
                  imageUrl: restaurant.coverImageUrl,
                  kind: BrandedImageKind.venue,
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    restaurant.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w900,
                        ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    (restaurant.branches.isNotEmpty
                            ? restaurant.branches.first.location
                            : null) ??
                        (restaurant.branches.isNotEmpty
                            ? restaurant.branches.first.name
                            : null) ??
                        'Cafe venue',
                    style: const TextStyle(color: Color(0xFF8B6B4C)),
                  ),
                ],
              ),
            ),
          ],
        )),
      ),
    );
  }
}

class _FeaturedDishEntry {
  const _FeaturedDishEntry({
    required this.restaurant,
    required this.item,
  });

  final RestaurantListing restaurant;
  final RestaurantFeaturedItem item;
}

class _FeaturedDishCard extends StatelessWidget {
  const _FeaturedDishCard({
    required this.entry,
    required this.currency,
    required this.onTap,
  });

  final _FeaturedDishEntry entry;
  final NumberFormat currency;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 208,
      child: Card(
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(26)),
        clipBehavior: Clip.antiAlias,
        child: InkWell(
          onTap: onTap,
          child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              height: 124,
              child: ClipRRect(
                borderRadius: const BorderRadius.vertical(
                  top: Radius.circular(26),
                ),
                child: BrandedImage(
                  label: entry.item.name,
                  imageUrl: entry.item.imageUrl,
                  kind: BrandedImageKind.dish,
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    entry.item.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    entry.restaurant.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(color: Color(0xFF8B6B4C)),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    currency.format(entry.item.price),
                    style: Theme.of(context)
                        .textTheme
                        .titleMedium
                        ?.copyWith(fontWeight: FontWeight.w900),
                  ),
                ],
              ),
            ),
          ],
        )),
      ),
    );
  }
}

class _RestaurantSearchCard extends StatelessWidget {
  const _RestaurantSearchCard({
    required this.restaurant,
    required this.onTap,
  });

  final RestaurantListing restaurant;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final featuredText = restaurant.featuredItems.isEmpty
        ? 'Branch visibility and dine-in memory'
        : restaurant.featuredItems.map((item) => item.name).join(' • ');

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            height: 184,
            child: ClipRRect(
              borderRadius: const BorderRadius.vertical(
                top: Radius.circular(28),
              ),
              child: Stack(
                fit: StackFit.expand,
                children: [
                  BrandedImage(
                    label: restaurant.name,
                    imageUrl: restaurant.coverImageUrl,
                    kind: BrandedImageKind.venue,
                  ),
                  Positioned(
                    top: 14,
                    left: 14,
                    child: _MiniBadge(
                      label: restaurant.kind.toUpperCase(),
                      color: Colors.white.withValues(alpha: 0.92),
                      textColor: const Color(0xFF211208),
                    ),
                  ),
                ],
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(18),
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
                            ?.copyWith(fontWeight: FontWeight.w900),
                      ),
                    ),
                    const Icon(Icons.star_rounded,
                        color: Color(0xFFFFA000), size: 20),
                    const SizedBox(width: 4),
                    const Text(
                      '4.8',
                      style: TextStyle(fontWeight: FontWeight.w800),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Text(
                  featuredText,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: Color(0xFF8B6B4C), height: 1.35),
                ),
                const SizedBox(height: 14),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    for (final branch in restaurant.branches.take(3))
                      _InfoPill(
                        icon: Icons.location_on_outlined,
                        label: branch.location ?? branch.name,
                      ),
                  ],
                ),
              ],
            ),
          ),
        ],
      )),
    );
  }
}

class _CustomerOrderCard extends StatelessWidget {
  const _CustomerOrderCard({
    required this.order,
    required this.currency,
    required this.onTap,
  });

  final CustomerOrder order;
  final NumberFormat currency;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final heroItem = order.items.isNotEmpty ? order.items.first : null;

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(22),
                  child: SizedBox(
                    width: 92,
                    height: 92,
                    child: BrandedImage(
                      label: heroItem?.name ?? order.restaurantName ?? 'Order',
                      imageUrl: heroItem?.imageUrl,
                      kind: BrandedImageKind.dish,
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        order.restaurantName ?? 'Restaurant',
                        style: Theme.of(context)
                            .textTheme
                            .titleMedium
                            ?.copyWith(fontWeight: FontWeight.w900),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${order.branchName ?? 'Branch'}${order.branchLocation == null ? '' : ' • ${order.branchLocation}'}',
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(color: Color(0xFF8B6B4C)),
                      ),
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        runSpacing: 8,
                        children: [
                          _MiniBadge(
                            label: order.status.toUpperCase(),
                            color: const Color(0xFFFFE4CE),
                            textColor: const Color(0xFF8A4316),
                          ),
                          _MiniBadge(
                            label: order.orderType.toUpperCase(),
                            color: const Color(0xFFFEF3C7),
                            textColor: const Color(0xFF8A5A00),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final item in order.items.take(3))
                  _InfoPill(
                    icon: Icons.restaurant_menu_rounded,
                    label: '${item.quantity}x ${item.name}',
                  ),
              ],
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: Text(
                    currency.format(order.total),
                    style: Theme.of(context)
                        .textTheme
                        .titleLarge
                        ?.copyWith(fontWeight: FontWeight.w900),
                  ),
                ),
                Text(
                  _formatDate(order.createdAt),
                  style: const TextStyle(color: Color(0xFF8B6B4C)),
                ),
              ],
            ),
          ],
        ),
      )),
    );
  }
}

class _RewardPerkCard extends StatelessWidget {
  const _RewardPerkCard({
    required this.icon,
    required this.title,
    required this.description,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String description;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(26)),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
        padding: const EdgeInsets.all(18),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CircleAvatar(
              radius: 22,
              backgroundColor: const Color(0xFFFFE4C6),
              child: Icon(icon, color: const Color(0xFFFF7B2C)),
            ),
            const SizedBox(height: 14),
            Text(
              title,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w900,
                  ),
            ),
            const SizedBox(height: 8),
            Text(
              description,
              style: const TextStyle(color: Color(0xFF8B6B4C), height: 1.4),
            ),
          ],
        ),
      )),
    );
  }
}

class _RewardEntryRow extends StatelessWidget {
  const _RewardEntryRow({
    required this.entry,
    required this.onTap,
  });

  final LoyaltyEntry entry;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final positive = entry.type == 'earn';
    final color = positive ? const Color(0xFF1F9D63) : const Color(0xFFE86C2F);

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 10),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 2),
          child: Row(
        children: [
          CircleAvatar(
            radius: 22,
            backgroundColor: color.withValues(alpha: 0.12),
            child: Icon(
              positive
                  ? Icons.north_east_rounded
                  : Icons.south_west_rounded,
              color: color,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${entry.restaurantName ?? 'Restaurant'}${entry.branchName == null ? '' : ' • ${entry.branchName}'}',
                  style: Theme.of(context).textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 4),
                Text(
                  '${positive ? 'Earned' : 'Redeemed'} ${entry.points} pts • ${_formatDate(entry.createdAt)}',
                  style: const TextStyle(color: Color(0xFF8B6B4C)),
                ),
              ],
            ),
          ),
          Text(
            '${positive ? '+' : '-'}${entry.points}',
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.w900,
              fontSize: 18,
            ),
          ),
        ],
      ))),
    );
  }
}

class _CustomerAccountSheet extends ConsumerWidget {
  const _CustomerAccountSheet({required this.session});

  final AppSession? session;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return _CustomerSheet(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            session?.name ?? 'Guest',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w900,
                ),
          ),
          const SizedBox(height: 8),
          Text(
            session?.phone ?? session?.email ?? 'No contact details available',
            style: const TextStyle(color: Color(0xFF8B6B4C)),
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: _RewardPerkCard(
                  icon: Icons.receipt_long_rounded,
                  title: 'Recent orders',
                  description: '${session?.loyaltyPoints ?? 0} points currently visible.',
                  onTap: () => Navigator.of(context).pop(),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _RewardPerkCard(
                  icon: Icons.workspace_premium_rounded,
                  title: 'Rewards',
                  description: 'Points are earned from paid dine-in orders.',
                  onTap: () => Navigator.of(context).pop(),
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          FilledButton.icon(
            onPressed: () {
              Navigator.of(context).pop();
              ref.read(authProvider.notifier).logout();
            },
            icon: const Icon(Icons.logout_rounded),
            label: const Text('Logout'),
          ),
        ],
      ),
    );
  }
}

class _RestaurantDetailPage extends ConsumerStatefulWidget {
  const _RestaurantDetailPage({
    required this.restaurant,
    this.initialProductId,
  });

  final RestaurantListing restaurant;
  final int? initialProductId;

  @override
  ConsumerState<_RestaurantDetailPage> createState() =>
      _RestaurantDetailPageState();
}

class _RestaurantDetailPageState extends ConsumerState<_RestaurantDetailPage> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();
  final List<CustomerMenuItem> _items = [];
  PaginationMeta? _meta;
  RestaurantListing? _restaurant;
  bool _loading = true;
  bool _openedInitialItem = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _restaurant = widget.restaurant;
    _scrollController.addListener(_maybeLoadMore);
    _load(reset: true);
  }

  @override
  void dispose() {
    _scrollController
      ..removeListener(_maybeLoadMore)
      ..dispose();
    _searchController.dispose();
    super.dispose();
  }

  void _maybeLoadMore() {
    if (!_scrollController.hasClients || _loading) return;
    final position = _scrollController.position;
    if (position.pixels >= position.maxScrollExtent - 220 &&
        (_meta?.hasMore ?? false)) {
      _load();
    }
  }

  Future<void> _load({bool reset = false}) async {
    if (_loading && !reset) return;
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final response =
          await ref.read(suiteRepositoryProvider).fetchCustomerRestaurantDetail(
                restaurantId: widget.restaurant.id,
                page: reset ? 1 : (_meta?.currentPage ?? 0) + 1,
                perPage: 12,
                search: _searchController.text.trim(),
              );

      setState(() {
        _restaurant = response.restaurant;
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

      if (!_openedInitialItem && widget.initialProductId != null) {
        for (final item in _items) {
          if (item.id == widget.initialProductId) {
            _openedInitialItem = true;
            WidgetsBinding.instance.addPostFrameCallback((_) {
              if (!mounted) return;
              _openItem(item);
            });
            break;
          }
        }
      }
    } catch (e) {
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  void _openItem(CustomerMenuItem item) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _CustomerDishDetailSheet(
        restaurant: _restaurant ?? widget.restaurant,
        item: item,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final restaurant = _restaurant ?? widget.restaurant;

    return Scaffold(
      backgroundColor: const Color(0xFFFFFAF5),
      appBar: AppBar(
        backgroundColor: const Color(0xFFFFFAF5),
        surfaceTintColor: Colors.transparent,
        title: Text(
          restaurant.name,
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
      ),
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _searchController,
                      decoration: const InputDecoration(
                        prefixIcon: Icon(Icons.search_rounded),
                        hintText: 'Search menu items or branches',
                      ),
                      onSubmitted: (_) => _load(reset: true),
                    ),
                  ),
                  const SizedBox(width: 12),
                  FilledButton.icon(
                    onPressed: () => _load(reset: true),
                    icon: const Icon(Icons.search_rounded),
                    label: const Text('Search'),
                  ),
                ],
              ),
            ),
            Expanded(
              child: _loading && _items.isEmpty
                  ? const LoadingView(label: 'Loading restaurant details...')
                  : _error != null && _items.isEmpty
                      ? ErrorView(
                          message: _error!,
                          onRetry: () => _load(reset: true),
                        )
                      : RefreshIndicator(
                          onRefresh: () => _load(reset: true),
                          child: ListView.separated(
                            controller: _scrollController,
                            padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                            itemCount: _items.length + 2,
                            separatorBuilder: (_, __) =>
                                const SizedBox(height: 14),
                            itemBuilder: (context, index) {
                              if (index == 0) {
                                return _RestaurantHeroCard(restaurant: restaurant);
                              }

                              if (index == _items.length + 1) {
                                if (!(_meta?.hasMore ?? false)) {
                                  return const SizedBox(height: 12);
                                }

                                return Center(
                                  child: OutlinedButton.icon(
                                    onPressed: _loading ? null : () => _load(),
                                    icon: const Icon(Icons.expand_more_rounded),
                                    label: Text(
                                      _loading ? 'Loading...' : 'Load more items',
                                    ),
                                  ),
                                );
                              }

                              final item = _items[index - 1];
                              return _RestaurantMenuCard(
                                item: item,
                                onTap: () => _openItem(item),
                              );
                            },
                          ),
                        ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RestaurantHeroCard extends StatelessWidget {
  const _RestaurantHeroCard({required this.restaurant});

  final RestaurantListing restaurant;

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      clipBehavior: Clip.antiAlias,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            height: 210,
            child: Stack(
              fit: StackFit.expand,
              children: [
                BrandedImage(
                  label: restaurant.name,
                  imageUrl: restaurant.coverImageUrl,
                  kind: BrandedImageKind.venue,
                  overlay: const LinearGradient(
                    colors: [Color(0x22000000), Color(0xAA180C07)],
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.all(18),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _MiniBadge(
                        label: restaurant.kind == 'cafe' ? 'Cafe' : 'Restaurant',
                        color: Colors.white.withValues(alpha: 0.9),
                        textColor: const Color(0xFF1E120B),
                      ),
                      const Spacer(),
                      Text(
                        restaurant.name,
                        style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                              color: Colors.white,
                              fontWeight: FontWeight.w900,
                            ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        '${restaurant.branchCount} branches in the suite',
                        style: const TextStyle(color: Colors.white70),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(18),
            child: Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final branch in restaurant.branches)
                  _InfoPill(
                    icon: Icons.location_on_outlined,
                    label: branch.location ?? branch.name,
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _RestaurantMenuCard extends StatelessWidget {
  const _RestaurantMenuCard({
    required this.item,
    required this.onTap,
  });

  final CustomerMenuItem item;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');

    return Card(
      elevation: 0,
      clipBehavior: Clip.antiAlias,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(26)),
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(18),
                child: SizedBox(
                  width: 92,
                  height: 92,
                  child: BrandedImage(
                    label: item.name,
                    imageUrl: item.imageUrl,
                    kind: BrandedImageKind.dish,
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item.name,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w900,
                          ),
                    ),
                    const SizedBox(height: 6),
                    if (item.categoryName != null)
                      Text(
                        item.categoryName!,
                        style: const TextStyle(color: Color(0xFF8B6B4C)),
                      ),
                    if (item.categoryName != null) const SizedBox(height: 6),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        if (item.branchName != null)
                          _InfoPill(
                            icon: Icons.store_mall_directory_outlined,
                            label: item.branchName!,
                          ),
                        if (item.branchLocation != null)
                          _InfoPill(
                            icon: Icons.location_on_outlined,
                            label: item.branchLocation!,
                          ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Text(
                      currency.format(item.price),
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w900,
                          ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 8),
              const Icon(Icons.chevron_right_rounded),
            ],
          ),
        ),
      ),
    );
  }
}

class _CustomerDishDetailSheet extends StatelessWidget {
  const _CustomerDishDetailSheet({
    required this.restaurant,
    required this.item,
  });

  final RestaurantListing restaurant;
  final CustomerMenuItem item;

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');

    return _CustomerSheet(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(24),
            child: SizedBox(
              height: 220,
              width: double.infinity,
              child: BrandedImage(
                label: item.name,
                imageUrl: item.imageUrl,
                kind: BrandedImageKind.dish,
              ),
            ),
          ),
          const SizedBox(height: 18),
          Text(
            item.name,
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w900,
                ),
          ),
          const SizedBox(height: 8),
          Text(
            restaurant.name,
            style: const TextStyle(
              color: Color(0xFF8B6B4C),
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              if (item.categoryName != null)
                _InfoPill(
                  icon: Icons.category_outlined,
                  label: item.categoryName!,
                ),
              if (item.branchName != null)
                _InfoPill(
                  icon: Icons.storefront_outlined,
                  label: item.branchName!,
                ),
              if (item.branchLocation != null)
                _InfoPill(
                  icon: Icons.location_on_outlined,
                  label: item.branchLocation!,
                ),
            ],
          ),
          const SizedBox(height: 18),
          Text(
            currency.format(item.price),
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w900,
                ),
          ),
          const SizedBox(height: 12),
          const Text(
            'This customer app currently supports discovery, visit memory, and rewards visibility. Ordering still happens inside the restaurant workflow.',
            style: TextStyle(color: Color(0xFF8B6B4C), height: 1.45),
          ),
        ],
      ),
    );
  }
}

class _CustomerOrderDetailSheet extends ConsumerStatefulWidget {
  const _CustomerOrderDetailSheet({required this.orderId});

  final int orderId;

  @override
  ConsumerState<_CustomerOrderDetailSheet> createState() =>
      _CustomerOrderDetailSheetState();
}

class _CustomerOrderDetailSheetState
    extends ConsumerState<_CustomerOrderDetailSheet> {
  late Future<CustomerOrder> _future;

  @override
  void initState() {
    super.initState();
    _future =
        ref.read(suiteRepositoryProvider).fetchCustomerOrderDetail(widget.orderId);
  }

  @override
  Widget build(BuildContext context) {
    final currency = NumberFormat.currency(symbol: 'EGP ');

    return FutureBuilder<CustomerOrder>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const _CustomerSheet(
            child: SizedBox(
              height: 220,
              child: Center(child: CircularProgressIndicator()),
            ),
          );
        }

        if (snapshot.hasError) {
          return _CustomerSheet(
            child: ErrorView(
              message: snapshot.error.toString(),
              onRetry: () => setState(() {
                _future = ref
                    .read(suiteRepositoryProvider)
                    .fetchCustomerOrderDetail(widget.orderId);
              }),
            ),
          );
        }

        final order = snapshot.data!;
        final heroItem = order.items.isNotEmpty ? order.items.first : null;

        return _CustomerSheet(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(22),
                    child: SizedBox(
                      width: 108,
                      height: 108,
                      child: BrandedImage(
                        label: heroItem?.name ?? order.restaurantName ?? 'Order',
                        imageUrl: heroItem?.imageUrl,
                        kind: BrandedImageKind.dish,
                      ),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          order.restaurantName ?? 'Restaurant',
                          style: Theme.of(context)
                              .textTheme
                              .titleLarge
                              ?.copyWith(fontWeight: FontWeight.w900),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${order.branchName ?? 'Branch'}${order.branchLocation == null ? '' : ' • ${order.branchLocation}'}',
                          style: const TextStyle(color: Color(0xFF8B6B4C)),
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            _MiniBadge(
                              label: order.status.toUpperCase(),
                              color: const Color(0xFFFFE4CE),
                              textColor: const Color(0xFF8A4316),
                            ),
                            _MiniBadge(
                              label: order.paymentStatus.toUpperCase(),
                              color: const Color(0xFFE9F8EF),
                              textColor: const Color(0xFF196C48),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              Text(
                'Order items',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
              ),
              const SizedBox(height: 10),
              for (final item in order.items)
                Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          '${item.quantity}x ${item.name}',
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                      ),
                      Text(
                        currency.format(item.total),
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                    ],
                  ),
                ),
              const SizedBox(height: 10),
              Text(
                'Payments',
                style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w900,
                    ),
              ),
              const SizedBox(height: 10),
              if (order.payments.isEmpty)
                const Text(
                  'No payment records were attached to this order.',
                  style: TextStyle(color: Color(0xFF8B6B4C)),
                )
              else
                for (final payment in order.payments)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Row(
                      children: [
                        Expanded(child: Text(payment.method.toUpperCase())),
                        Text(currency.format(payment.amount)),
                      ],
                    ),
                  ),
              const SizedBox(height: 14),
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Placed ${_formatDate(order.createdAt)}',
                      style: const TextStyle(color: Color(0xFF8B6B4C)),
                    ),
                  ),
                  Text(
                    currency.format(order.total),
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                          fontWeight: FontWeight.w900,
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

class _RewardEntryDetailSheet extends StatelessWidget {
  const _RewardEntryDetailSheet({required this.entry});

  final LoyaltyEntry entry;

  @override
  Widget build(BuildContext context) {
    final positive = entry.type == 'earn';
    return _CustomerSheet(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            positive ? 'Points earned' : 'Points redeemed',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w900,
                ),
          ),
          const SizedBox(height: 10),
          Text(
            '${entry.restaurantName ?? 'Restaurant'}${entry.branchName == null ? '' : ' • ${entry.branchName}'}',
            style: const TextStyle(
              color: Color(0xFF8B6B4C),
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 16),
          Text(
            '${positive ? '+' : '-'}${entry.points} pts',
            style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                  fontWeight: FontWeight.w900,
                  color: positive
                      ? const Color(0xFF1F9D63)
                      : const Color(0xFFE86C2F),
                ),
          ),
          const SizedBox(height: 12),
          Text(
            'Recorded ${_formatDate(entry.createdAt)}',
            style: const TextStyle(color: Color(0xFF8B6B4C)),
          ),
          if (entry.orderId != null) ...[
            const SizedBox(height: 12),
            Text(
              'Linked to order #${entry.orderId}',
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
          ],
        ],
      ),
    );
  }
}

class _CustomerSheet extends StatelessWidget {
  const _CustomerSheet({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return FractionallySizedBox(
      heightFactor: 0.92,
      child: DecoratedBox(
        decoration: const BoxDecoration(
          color: Color(0xFFFFFAF5),
          borderRadius: BorderRadius.vertical(top: Radius.circular(32)),
        ),
        child: SafeArea(
          top: false,
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
            child: child,
          ),
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle({
    required this.title,
    required this.subtitle,
  });

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                fontWeight: FontWeight.w900,
                color: const Color(0xFF24140C),
              ),
        ),
        const SizedBox(height: 4),
        Text(
          subtitle,
          style: const TextStyle(color: Color(0xFF8B6B4C), height: 1.35),
        ),
      ],
    );
  }
}

class _QuickServiceChip extends StatelessWidget {
  const _QuickServiceChip({
    required this.icon,
    required this.label,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(24),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(24),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(24),
          ),
          child: Row(
            children: [
              Icon(icon, size: 18, color: const Color(0xFFFF7B2C)),
              const SizedBox(width: 8),
              Text(
                label,
                style: const TextStyle(fontWeight: FontWeight.w800),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _MetricBadge extends StatelessWidget {
  const _MetricBadge({
    required this.label,
    required this.icon,
    required this.color,
  });

  final String label;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: color, size: 18),
          const SizedBox(width: 8),
          Text(
            label,
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800),
          ),
        ],
      ),
    );
  }
}

class _FilterPill extends StatelessWidget {
  const _FilterPill({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ChoiceChip(
      selected: selected,
      onSelected: (_) => onTap(),
      avatar: Icon(
        icon,
        size: 16,
        color: selected ? Colors.white : const Color(0xFFFF7B2C),
      ),
      label: Text(label),
      backgroundColor: Colors.white,
      selectedColor: const Color(0xFFFF7B2C),
      side: BorderSide.none,
      labelStyle: TextStyle(
        color: selected ? Colors.white : const Color(0xFF24140C),
        fontWeight: FontWeight.w700,
      ),
    );
  }
}

class _InfoPill extends StatelessWidget {
  const _InfoPill({
    required this.icon,
    required this.label,
  });

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF2E2),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: const Color(0xFFB45D20)),
          const SizedBox(width: 6),
          Flexible(
            child: Text(
              label,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontWeight: FontWeight.w700,
                color: Color(0xFF6E3D16),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SoftEmptyCard extends StatelessWidget {
  const _SoftEmptyCard({
    required this.title,
    required this.description,
  });

  final String title;
  final String description;

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
      child: Padding(
        padding: const EdgeInsets.all(22),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w900,
                  ),
            ),
            const SizedBox(height: 8),
            Text(
              description,
              style: const TextStyle(color: Color(0xFF8B6B4C), height: 1.45),
            ),
          ],
        ),
      ),
    );
  }
}

class _MiniBadge extends StatelessWidget {
  const _MiniBadge({
    required this.label,
    required this.color,
    required this.textColor,
  });

  final String label;
  final Color color;
  final Color textColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Text(
        label,
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: TextStyle(
          color: textColor,
          fontSize: 11,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }
}

class _RoundIconButton extends StatelessWidget {
  const _RoundIconButton({
    required this.icon,
    required this.onPressed,
  });

  final IconData icon;
  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(20),
      child: InkWell(
        onTap: onPressed,
        borderRadius: BorderRadius.circular(20),
        child: SizedBox(
          width: 46,
          height: 46,
          child: Icon(icon),
        ),
      ),
    );
  }
}

String _formatDate(DateTime? value) {
  if (value == null) return 'Unknown date';
  return DateFormat('dd MMM • hh:mm a').format(value);
}
