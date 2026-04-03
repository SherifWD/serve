import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_flavor.dart';
import '../../../core/models/app_models.dart';
import '../../auth/providers/auth_providers.dart';
import '../../cashier/presentation/cashier_workspace.dart';
import '../../customer/presentation/customer_workspace.dart';
import '../../kitchen/presentation/kitchen_workspace.dart';
import '../../owner/presentation/owner_workspace.dart';
import '../../waiter/presentation/waiter_workspace.dart';

class DashboardPage extends ConsumerWidget {
  const DashboardPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final session = ref.watch(currentSessionProvider);
    final roles = ref.watch(availableRolesProvider);
    final fixedRole = ref.watch(fixedRoleProvider);

    if (session == null) {
      return const Scaffold(body: SizedBox.shrink());
    }

    final workspace = switch (session.activeRole) {
      AppRole.customer => const CustomerWorkspacePage(),
      AppRole.waiter => const WaiterWorkspacePage(),
      AppRole.cashier => const CashierWorkspacePage(),
      AppRole.kitchen => const KitchenWorkspacePage(),
      AppRole.owner => const OwnerWorkspacePage(),
    };

    final title = switch (session.activeRole) {
      AppRole.customer => 'Customer App',
      AppRole.waiter => 'Waiter App',
      AppRole.cashier => 'Cashier App',
      AppRole.kitchen => 'Kitchen App',
      AppRole.owner => 'Owner Control Center',
    };

    final subtitle = switch (session.activeRole) {
      AppRole.customer =>
        'Talabat-inspired discovery, loyalty, and order memory',
      AppRole.waiter => 'Table-first dine-in operations',
      AppRole.cashier => 'Queue settlement and multi-tender payments',
      AppRole.kitchen => 'Live KDS execution board',
      AppRole.owner => 'SaaS-level visibility across branches and operations',
    };

    final isCustomer = session.activeRole == AppRole.customer;
    final background = switch (session.activeRole) {
      AppRole.customer => const Color(0xFFFFF8F1),
      AppRole.kitchen => const Color(0xFF0D1321),
      AppRole.cashier => const Color(0xFFF4F1EA),
      AppRole.waiter => const Color(0xFFF7F2EA),
      AppRole.owner => const Color(0xFF0F172A),
    };

    return Scaffold(
      backgroundColor: background,
      appBar: isCustomer
          ? null
          : AppBar(
              backgroundColor: background,
              foregroundColor: session.activeRole == AppRole.owner ||
                      session.activeRole == AppRole.kitchen
                  ? Colors.white
                  : null,
              titleSpacing: 0,
              title: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title),
                  Text(
                    subtitle,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: session.activeRole == AppRole.owner ||
                                  session.activeRole == AppRole.kitchen
                              ? Colors.white70
                              : Theme.of(context).colorScheme.onSurfaceVariant,
                        ),
                  ),
                ],
              ),
              actions: [
                if (fixedRole == null && roles.length > 1)
                  PopupMenuButton<AppRole>(
                    tooltip: 'Switch role',
                    icon: const Icon(Icons.swap_horiz),
                    onSelected: (role) =>
                        ref.read(authProvider.notifier).switchRole(role),
                    itemBuilder: (context) {
                      return [
                        for (final role in roles)
                          PopupMenuItem<AppRole>(
                            value: role,
                            child: Row(
                              children: [
                                Icon(role.icon, size: 18),
                                const SizedBox(width: 10),
                                Text(role.label),
                              ],
                            ),
                          ),
                      ];
                    },
                  ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 8),
                  child: Center(
                    child: Text(
                      session.name,
                      style: Theme.of(context).textTheme.labelLarge?.copyWith(
                            fontWeight: FontWeight.w700,
                            color: session.activeRole == AppRole.owner ||
                                    session.activeRole == AppRole.kitchen
                                ? Colors.white
                                : null,
                          ),
                    ),
                  ),
                ),
                IconButton(
                  tooltip: 'Logout',
                  onPressed: () => ref.read(authProvider.notifier).logout(),
                  icon: const Icon(Icons.logout),
                ),
              ],
            ),
      body: SafeArea(
        top: isCustomer,
        child: Padding(
          padding: EdgeInsets.fromLTRB(
            isCustomer ? 0 : 18,
            isCustomer ? 0 : 8,
            isCustomer ? 0 : 18,
            isCustomer ? 0 : 18,
          ),
          child: workspace,
        ),
      ),
    );
  }
}
