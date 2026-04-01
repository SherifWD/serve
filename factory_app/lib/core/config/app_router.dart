import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/presentation/login_page.dart';
import '../../features/auth/providers/auth_providers.dart';
import '../../features/dashboard/presentation/dashboard_page.dart';
import '../../features/reports/presentation/reports_page.dart';
import '../../features/projects/presentation/projects_page.dart';
import '../../features/employees/presentation/employees_page.dart';
import '../../features/hr/presentation/hr_page.dart';
import '../../features/attendance/presentation/attendance_page.dart';
import '../../features/salaries/presentation/salaries_page.dart';
import '../../features/notifications/presentation/notifications_page.dart';
import '../../features/pos/presentation/pos_page.dart';
import '../../features/inventory/presentation/inventory_page.dart';
import '../../features/erp/presentation/erp_page.dart';
import '../../features/mes/presentation/mes_page.dart';
import '../../features/plm/presentation/plm_page.dart';
import '../../features/scm/presentation/scm_page.dart';
import '../../features/wms/presentation/wms_page.dart';
import '../../features/qms/presentation/qms_page.dart';
import '../../features/hrms/presentation/hrms_page.dart';
import '../../features/cmms/presentation/cmms_page.dart';
import '../../features/finance/presentation/finance_page.dart';
import '../../features/crm/presentation/crm_page.dart';
import '../../features/bi/presentation/bi_page.dart';
import '../../features/hse/presentation/hse_page.dart';
import '../../features/dms/presentation/dms_page.dart';
import '../../features/visitor/presentation/visitor_page.dart';
import '../../features/iot/presentation/iot_page.dart';
import '../../features/procurement/presentation/procurement_page.dart';
import '../../features/commerce/presentation/commerce_page.dart';
import '../../features/budgeting/presentation/budgeting_page.dart';
import '../../features/communication/presentation/communication_page.dart';
import '../../features/platform/presentation/platform_page.dart';
import '../providers/module_provider.dart';
import '../widgets/app_shell.dart';

final appRouterProvider = Provider<GoRouter>((ref) {
  final routerNotifier = ref.watch(routerNotifierProvider);

  return GoRouter(
    initialLocation: '/login',
    refreshListenable: routerNotifier,
    redirect: (context, state) {
      final loggedIn = ref.read(isAuthenticatedProvider);
      final loggingIn = state.matchedLocation == '/login';

      if (!loggedIn && !loggingIn) {
        return '/login';
      }
      if (loggedIn && loggingIn) {
        final firstAllowed = ref.read(allowedModulesProvider).first;
        return firstAllowed.route;
      }
      if (loggedIn && state.matchedLocation.startsWith('/home/')) {
        final moduleId = state.matchedLocation.split('/').last;
        final allowed = ref.read(allowedModulesProvider);
        final exists = allowed.any((m) => m.id == moduleId);
        if (!exists && allowed.isNotEmpty) {
          return allowed.first.route;
        }
      }
      return null;
    },
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginPage(),
      ),
      ShellRoute(
        builder: (context, state, child) => AppShell(child: child, location: state.matchedLocation),
        routes: [
          GoRoute(
            path: '/home/:moduleId',
            name: 'module',
            builder: (context, state) {
              final moduleId = state.pathParameters['moduleId'] ?? 'dashboard';
              return ModuleScreen(moduleId: moduleId);
            },
          ),
        ],
      ),
    ],
  );
});

/// Notifies GoRouter when auth state changes so redirects are reevaluated.
class RouterNotifier extends ChangeNotifier {
  RouterNotifier(this.ref) {
    ref.listen<AuthState>(authProvider, (_, __) => notifyListeners());
  }

  final Ref ref;
}

final routerNotifierProvider = Provider<RouterNotifier>((ref) {
  return RouterNotifier(ref);
});

class ModuleScreen extends ConsumerWidget {
  const ModuleScreen({required this.moduleId, super.key});

  final String moduleId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    ref.read(selectedModuleIdProvider.notifier).state = moduleId;
    final module = ref.watch(allModulesProvider).firstWhere(
          (m) => m.id == moduleId,
          orElse: () => ref.watch(allModulesProvider).first,
        );

    final pages = <String, Widget Function()> {
      'dashboard': () => const DashboardPage(),
      'pos': () => const PosPage(),
      'reports': () => const ReportsPage(),
      'projects': () => const ProjectsPage(),
      'employees': () => const EmployeesPage(),
      'hr': () => const HrPage(),
      'inventory': () => const InventoryPage(),
      'erp': () => const ErpPage(),
      'mes': () => const MesPage(),
      'plm': () => const PlmPage(),
      'scm': () => const ScmPage(),
      'wms': () => const WmsPage(),
      'qms': () => const QmsPage(),
      'attendance': () => const AttendancePage(),
      'salaries': () => const SalariesPage(),
      'hrms': () => const HrmsPage(),
      'cmms': () => const CmmsPage(),
      'finance': () => const FinancePage(),
      'crm': () => const CrmPage(),
      'bi': () => const BiPage(),
      'hse': () => const HsePage(),
      'dms': () => const DmsPage(),
      'visitor': () => const VisitorPage(),
      'iot': () => const IotPage(),
      'procurement': () => const ProcurementPage(),
      'commerce': () => const CommercePage(),
      'budgeting': () => const BudgetingPage(),
      'communication': () => const CommunicationPage(),
      'platform': () => const PlatformPage(),
      'notifications': () => const NotificationsPage(),
    };

    final builder = pages[module.id] ?? pages['dashboard']!;
    return builder();
  }
}
