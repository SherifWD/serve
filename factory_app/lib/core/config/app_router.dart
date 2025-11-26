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
      'reports': () => const ReportsPage(),
      'projects': () => const ProjectsPage(),
      'employees': () => const EmployeesPage(),
      'hr': () => const HrPage(),
      'attendance': () => const AttendancePage(),
      'salaries': () => const SalariesPage(),
      'notifications': () => const NotificationsPage(),
    };

    final builder = pages[module.id] ?? pages['dashboard']!;
    return builder();
  }
}
