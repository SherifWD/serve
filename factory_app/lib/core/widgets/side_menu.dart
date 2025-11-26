import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../models/module.dart';
import '../providers/module_provider.dart';
import '../../features/auth/providers/auth_providers.dart';
import '../models/roles.dart';

class SideMenu extends ConsumerStatefulWidget {
  const SideMenu({
    required this.modules,
    this.isDrawer = false,
    super.key,
  });

  final List<AppModule> modules;
  final bool isDrawer;

  @override
  ConsumerState<SideMenu> createState() => _SideMenuState();
}

class _SideMenuState extends ConsumerState<SideMenu> {
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final selectedId = ref.watch(selectedModuleIdProvider);
    final role = ref.watch(currentRoleProvider) ?? UserRole.employee;

    return MouseRegion(
      onEnter: (_) => setState(() => _expanded = true),
      onExit: (_) => setState(() => _expanded = false),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 220),
        width: (_expanded || widget.isDrawer) ? 240 : 78,
        padding: const EdgeInsets.symmetric(vertical: 18),
        decoration: BoxDecoration(
          color: theme.colorScheme.surfaceVariant,
          borderRadius: widget.isDrawer ? null : BorderRadius.circular(16),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildHeader(context, theme),
            const SizedBox(height: 8),
            Expanded(
              child: ListView(
                children: widget.modules.map((module) {
                  final selected = module.id == selectedId;
                  return _NavItem(
                    expanded: _expanded || widget.isDrawer,
                    module: module,
                    selected: selected,
                    onTap: () {
                      ref.read(selectedModuleIdProvider.notifier).state = module.id;
                      if (widget.isDrawer) Navigator.of(context).pop();
                      context.go(module.route);
                    },
                  );
                }).toList(),
              ),
            ),
            const Divider(height: 0),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              child: Row(
                children: [
                  Icon(Icons.verified_user_outlined, color: theme.colorScheme.primary),
                  if (_expanded || widget.isDrawer) ...[
                    const SizedBox(width: 10),
                    Text('Role: ${role.label}', style: theme.textTheme.labelMedium),
                  ],
                ],
              ),
            )
          ],
        ),
      ),
    );
  }

  Widget _buildHeader(BuildContext context, ThemeData theme) {
    final showLabel = _expanded || widget.isDrawer;
    return InkWell(
      onTap: () => setState(() => _expanded = !_expanded),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 16),
        child: Row(
          children: [
            Container(
              decoration: BoxDecoration(
                color: theme.colorScheme.primary.withOpacity(0.15),
                borderRadius: BorderRadius.circular(12),
              ),
              padding: const EdgeInsets.all(10),
              child: Icon(Icons.factory_outlined, color: theme.colorScheme.primary),
            ),
            if (showLabel) ...[
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Factory Ops', style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700)),
                  Text('Control center', style: theme.textTheme.labelMedium),
                ],
              ),
              const Spacer(),
              Icon(
                _expanded ? Icons.expand_less : Icons.expand_more,
                color: theme.colorScheme.onSurfaceVariant,
              )
            ],
          ],
        ),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  const _NavItem({
    required this.expanded,
    required this.module,
    required this.selected,
    required this.onTap,
  });

  final bool expanded;
  final AppModule module;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final resolvedColor = selected ? theme.colorScheme.primary : theme.colorScheme.onSurfaceVariant;

    return AnimatedContainer(
      duration: const Duration(milliseconds: 180),
      margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: selected ? theme.colorScheme.primary.withOpacity(0.1) : Colors.transparent,
        borderRadius: BorderRadius.circular(12),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.center,
            children: [
              Icon(module.icon, color: resolvedColor, size: 22),
              if (expanded) ...[
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        module.name,
                        style: TextStyle(
                          color: selected ? theme.colorScheme.primary : theme.colorScheme.onSurface,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                      if (module.description != null)
                        Text(
                          module.description!,
                          style: theme.textTheme.labelSmall,
                          overflow: TextOverflow.ellipsis,
                        ), // brief helper text
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
