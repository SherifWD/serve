import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../features/auth/providers/auth_providers.dart';
import '../providers/module_provider.dart';
import '../widgets/side_menu.dart';

class AppShell extends ConsumerWidget {
  const AppShell({required this.child, required this.location, super.key});

  final Widget child;
  final String location;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final isLogged = ref.watch(isAuthenticatedProvider);
    if (!isLogged) return child;

    final modules = ref.watch(allowedModulesProvider);
    final user = ref.watch(currentUserProvider);
    final theme = Theme.of(context);
    final size = MediaQuery.of(context).size;
    final isDesktop = size.width > 980;

    final today = DateFormat('EEE, MMM d').format(DateTime.now());

    return Scaffold(
      appBar: isDesktop
          ? null
          : AppBar(
              title: const Text('Factory Ops'),
              actions: [
                IconButton(
                  onPressed: () => ref.read(authProvider.notifier).logout(),
                  icon: const Icon(Icons.logout),
                ),
              ],
            ),
      drawer: isDesktop ? null : Drawer(child: SideMenu(modules: modules, isDrawer: true)),
      body: SafeArea(
        child: Row(
          children: [
            if (isDesktop)
              Padding(
                padding: const EdgeInsets.all(12),
                child: SideMenu(modules: modules),
              ),
            Expanded(
              child: AnimatedSwitcher(
                duration: const Duration(milliseconds: 240),
                child: Container(
                  key: ValueKey(location),
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (isDesktop)
                        Padding(
                          padding: const EdgeInsets.only(bottom: 16),
                          child: Row(
                            children: [
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text('Hi, ${user?.name ?? 'Guest'}', style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold)),
                                  Text('Welcome back - $today', style: theme.textTheme.labelLarge),
                                ],
                              ),
                              const Spacer(),
                              Wrap(
                                spacing: 12,
                                children: [
                                  FilledButton.tonal(
                                    onPressed: () {},
                                    child: const Text('New task'),
                                  ),
                                  FilledButton.icon(
                                    onPressed: () => ref.read(authProvider.notifier).logout(),
                                    icon: const Icon(Icons.logout),
                                    label: const Text('Logout'),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      Expanded(child: child),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
