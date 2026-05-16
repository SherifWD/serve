import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_flavor.dart';
import '../../../core/localization/app_language.dart';
import '../../../core/models/app_models.dart';
import '../../../core/widgets/language_toggle.dart';
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
    final strings = ref.watch(appStringsProvider);

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
      AppRole.customer => strings.t('app.customer'),
      AppRole.waiter => strings.t('app.waiter'),
      AppRole.cashier => strings.t('app.cashier'),
      AppRole.kitchen => strings.t('app.kitchen'),
      AppRole.owner => strings.t('app.owner'),
    };

    final subtitle = switch (session.activeRole) {
      AppRole.customer => strings.t('role.customer'),
      AppRole.waiter => strings.t('role.waiter'),
      AppRole.cashier => strings.t('role.cashier'),
      AppRole.kitchen => strings.t('role.kitchen'),
      AppRole.owner => strings.t('role.owner'),
    };

    final isCustomer = session.activeRole == AppRole.customer;
    final background = switch (session.activeRole) {
      AppRole.customer => const Color(0xFFFFF8F1),
      AppRole.kitchen => const Color(0xFF0D1321),
      AppRole.cashier => const Color(0xFFF4F1EA),
      AppRole.waiter => const Color(0xFFF7F2EA),
      AppRole.owner => const Color(0xFF0F172A),
    };
    final width = MediaQuery.sizeOf(context).width;
    final compact = width < 520;
    final darkChrome = session.activeRole == AppRole.owner ||
        session.activeRole == AppRole.kitchen;

    return Scaffold(
      backgroundColor: background,
      appBar: isCustomer
          ? null
          : AppBar(
              backgroundColor: background,
              foregroundColor: darkChrome ? Colors.white : null,
              toolbarHeight: compact ? 64 : kToolbarHeight,
              titleSpacing: compact ? 12 : 0,
              // title: Column(
              //   crossAxisAlignment: CrossAxisAlignment.start,
              //   children: [
              //     Text(
              //       title,
              //       maxLines: 1,
              //       overflow: TextOverflow.ellipsis,
              //     ),
              //     Text(
              //       subtitle,
              //       maxLines: 1,
              //       overflow: TextOverflow.ellipsis,
              //       style: Theme.of(context).textTheme.bodySmall?.copyWith(
              //             color: darkChrome
              //                 ? Colors.white70
              //                 : Theme.of(context).colorScheme.onSurfaceVariant,
              //           ),
              //     ),
              //   ],
              // ),
              actions: [
                if (fixedRole == null && roles.length > 1)
                  PopupMenuButton<AppRole>(
                    tooltip: strings.t('action.switchRole'),
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
                                Text(strings.roleLabel(role.apiType)),
                              ],
                            ),
                          ),
                      ];
                    },
                  ),
                if (!compact)
                  ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 160),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 8),
                      child: Center(
                        child: Text(
                          session.name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style:
                              Theme.of(context).textTheme.labelLarge?.copyWith(
                                    fontWeight: FontWeight.w700,
                                    color: darkChrome ? Colors.white : null,
                                  ),
                        ),
                      ),
                    ),
                  ),
                IconButton(
                  tooltip: strings.t('action.logout'),
                  onPressed: () => ref.read(authProvider.notifier).logout(),
                  icon: const Icon(Icons.logout),
                ),
                if (width >= 390)
                  LanguageToggle(
                    compact: true,
                    foregroundColor: darkChrome ? Colors.white : null,
                  ),
              ],
            ),
      body: SafeArea(
        top: isCustomer,
        child: Padding(
          padding: EdgeInsets.fromLTRB(
            isCustomer ? 0 : (compact ? 12 : 18),
            isCustomer ? 0 : 8,
            isCustomer ? 0 : (compact ? 12 : 18),
            isCustomer ? 0 : (compact ? 12 : 18),
          ),
          child: workspace,
        ),
      ),
    );
  }
}
