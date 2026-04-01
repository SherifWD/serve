import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../models/module.dart';

class SideMenu extends StatelessWidget {
  const SideMenu({
    required this.modules,
    this.isDrawer = false,
    super.key,
  });

  final List<AppModule> modules;
  final bool isDrawer;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      child: ListView(
        padding: const EdgeInsets.symmetric(vertical: 16),
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Text(
              'Legacy Menu',
              style: Theme.of(context)
                  .textTheme
                  .titleMedium
                  ?.copyWith(fontWeight: FontWeight.w700),
            ),
          ),
          const SizedBox(height: 8),
          for (final module in modules)
            ListTile(
              leading: Icon(module.icon),
              title: Text(module.name),
              subtitle:
                  module.description == null ? null : Text(module.description!),
              onTap: () {
                if (isDrawer) Navigator.of(context).pop();
                context.go(module.route);
              },
            ),
        ],
      ),
    );
  }
}
