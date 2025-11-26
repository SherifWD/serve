import 'package:flutter/material.dart';

import 'roles.dart';

class AppModule {
  const AppModule({
    required this.id,
    required this.name,
    required this.icon,
    required this.route,
    required this.allowedRoles,
    this.description,
  });

  final String id;
  final String name;
  final IconData icon;
  final String route;
  final Set<UserRole> allowedRoles;
  final String? description;

  bool isAllowedFor(UserRole role) => allowedRoles.contains(role);
}
