import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../models/module.dart';
import '../models/roles.dart';
import '../../features/auth/providers/auth_providers.dart';

final allModulesProvider = Provider<List<AppModule>>((ref) {
  return [
    AppModule(
      id: 'dashboard',
      name: 'Dashboard',
      icon: Icons.dashboard_outlined,
      route: '/home/dashboard',
      allowedRoles: {UserRole.owner, UserRole.manager, UserRole.employee},
      description: 'KPIs, production stats, and alerts',
    ),
    AppModule(
      id: 'reports',
      name: 'Reports',
      icon: Icons.analytics_outlined,
      route: '/home/reports',
      allowedRoles: {UserRole.owner, UserRole.manager},
      description: 'Filtered exports and charts',
    ),
    AppModule(
      id: 'projects',
      name: 'Projects',
      icon: Icons.view_timeline_outlined,
      route: '/home/projects',
      allowedRoles: {UserRole.owner, UserRole.manager, UserRole.employee},
      description: 'Project and task tracking',
    ),
    AppModule(
      id: 'employees',
      name: 'Employees',
      icon: Icons.group_outlined,
      route: '/home/employees',
      allowedRoles: {UserRole.owner, UserRole.manager},
      description: 'Role & department management',
    ),
    AppModule(
      id: 'hr',
      name: 'HR',
      icon: Icons.badge_outlined,
      route: '/home/hr',
      allowedRoles: {UserRole.owner, UserRole.manager, UserRole.employee},
      description: 'Profiles, requests, and documents',
    ),
    AppModule(
      id: 'attendance',
      name: 'Attendance',
      icon: Icons.schedule_outlined,
      route: '/home/attendance',
      allowedRoles: {UserRole.owner, UserRole.manager, UserRole.employee},
      description: 'Daily logs, overtime, missing check-ins',
    ),
    AppModule(
      id: 'salaries',
      name: 'Salaries',
      icon: Icons.payments_outlined,
      route: '/home/salaries',
      allowedRoles: {UserRole.owner, UserRole.manager, UserRole.employee},
      description: 'Payroll history and adjustments',
    ),
    AppModule(
      id: 'notifications',
      name: 'Notifications',
      icon: Icons.notifications_outlined,
      route: '/home/notifications',
      allowedRoles: {UserRole.owner, UserRole.manager, UserRole.employee},
      description: 'Events and activity logs',
    ),
  ];
});

final allowedModulesProvider = Provider<List<AppModule>>((ref) {
  final role = ref.watch(currentRoleProvider) ?? UserRole.employee;
  return ref
      .watch(allModulesProvider)
      .where((module) => module.allowedRoles.contains(role))
      .toList(growable: false);
});

final selectedModuleIdProvider = StateProvider<String>((ref) => 'dashboard');
