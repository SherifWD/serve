import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_flavor.dart';
import '../../../core/models/app_models.dart';
import '../../../core/network/api_client.dart';
import '../../../core/widgets/state_views.dart';
import '../providers/auth_providers.dart';

class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});

  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _staffEmailController = TextEditingController();
  final _staffPasswordController = TextEditingController();
  final _customerNameController = TextEditingController();
  final _customerPhoneController = TextEditingController();
  final _customerEmailController = TextEditingController();

  bool _customerMode = false;
  AppRole _selectedStaffRole = AppRole.owner;

  @override
  void dispose() {
    _staffEmailController.dispose();
    _staffPasswordController.dispose();
    _customerNameController.dispose();
    _customerPhoneController.dispose();
    _customerEmailController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final flavor = ref.watch(appFlavorProvider);
    final lockedRole = ref.watch(fixedRoleProvider);
    final theme = Theme.of(context);
    final customerMode = lockedRole == AppRole.customer || _customerMode;
    final selectedRole = lockedRole ?? _selectedStaffRole;

    if (!authState.hasBootstrapped) {
      return const Scaffold(
        body: LoadingView(label: 'Preparing restaurant suite...'),
      );
    }

    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFFF7E1C8), Color(0xFFF6F0E7), Color(0xFFE3F3EF)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: SafeArea(
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 1120),
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: LayoutBuilder(
                  builder: (context, constraints) {
                    final wide = constraints.maxWidth > 900;
                    return Card(
                      clipBehavior: Clip.antiAlias,
                      child: Row(
                        children: [
                          if (wide)
                            Expanded(
                              child: _HeroPanel(
                                  apiBaseUrl: ref.watch(apiBaseUrlProvider)),
                            ),
                          Expanded(
                            child: SingleChildScrollView(
                              padding: const EdgeInsets.all(28),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    customerMode
                                        ? 'Customer app access'
                                        : lockedRole == null
                                            ? 'Team sign in'
                                            : '${selectedRole.label} sign in',
                                    style: theme.textTheme.headlineMedium
                                        ?.copyWith(
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    customerMode
                                        ? 'View previous orders, loyalty points, and registered restaurants.'
                                        : lockedRole == null
                                            ? 'Choose the exact role workspace you want to run: waiter, cashier, kitchen, or owner.'
                                            : 'This app is pinned to the ${selectedRole.label.toLowerCase()} workspace for a cleaner branch rollout.',
                                    style: theme.textTheme.bodyLarge?.copyWith(
                                      color: theme.colorScheme.onSurfaceVariant,
                                    ),
                                  ),
                                  const SizedBox(height: 20),
                                  if (lockedRole == null)
                                    SegmentedButton<bool>(
                                      segments: const [
                                        ButtonSegment<bool>(
                                          value: false,
                                          icon: Icon(Icons.badge_outlined),
                                          label: Text('Staff'),
                                        ),
                                        ButtonSegment<bool>(
                                          value: true,
                                          icon: Icon(Icons.storefront_outlined),
                                          label: Text('Customer'),
                                        ),
                                      ],
                                      selected: {_customerMode},
                                      onSelectionChanged: (value) {
                                        setState(
                                            () => _customerMode = value.first);
                                      },
                                    ),
                                  const SizedBox(height: 24),
                                  if (!customerMode) ...[
                                    TextField(
                                      controller: _staffEmailController,
                                      keyboardType: TextInputType.emailAddress,
                                      decoration: const InputDecoration(
                                        labelText: 'Email',
                                        hintText: 'owner@restaurant.com',
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    TextField(
                                      controller: _staffPasswordController,
                                      obscureText: true,
                                      decoration: const InputDecoration(
                                        labelText: 'Password',
                                      ),
                                    ),
                                    const SizedBox(height: 18),
                                    Text(
                                      lockedRole == null
                                          ? 'Workspace'
                                          : 'Pinned workspace',
                                      style: theme.textTheme.titleMedium
                                          ?.copyWith(
                                              fontWeight: FontWeight.w700),
                                    ),
                                    const SizedBox(height: 10),
                                    if (lockedRole == null)
                                      Wrap(
                                        spacing: 10,
                                        runSpacing: 10,
                                        children: [
                                          for (final role in const [
                                            AppRole.owner,
                                            AppRole.waiter,
                                            AppRole.cashier,
                                            AppRole.kitchen,
                                          ])
                                            ChoiceChip(
                                              label: Text(role.label),
                                              avatar: Icon(role.icon, size: 18),
                                              selected:
                                                  _selectedStaffRole == role,
                                              onSelected: (_) {
                                                setState(() =>
                                                    _selectedStaffRole = role);
                                              },
                                            ),
                                        ],
                                      )
                                    else
                                      FilledButton.tonalIcon(
                                        onPressed: null,
                                        icon: Icon(selectedRole.icon),
                                        label: Text(selectedRole.label),
                                      ),
                                  ] else ...[
                                    TextField(
                                      controller: _customerNameController,
                                      decoration: const InputDecoration(
                                        labelText: 'Name',
                                        hintText: 'Guest name',
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    TextField(
                                      controller: _customerPhoneController,
                                      keyboardType: TextInputType.phone,
                                      decoration: const InputDecoration(
                                        labelText: 'Phone',
                                        hintText: '01xxxxxxxxx',
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    TextField(
                                      controller: _customerEmailController,
                                      keyboardType: TextInputType.emailAddress,
                                      decoration: const InputDecoration(
                                        labelText: 'Email (optional)',
                                      ),
                                    ),
                                  ],
                                  if (authState.error != null) ...[
                                    const SizedBox(height: 16),
                                    Text(
                                      authState.error!,
                                      style:
                                          theme.textTheme.bodyMedium?.copyWith(
                                        color: theme.colorScheme.error,
                                      ),
                                    ),
                                  ],
                                  const SizedBox(height: 24),
                                  SizedBox(
                                    width: double.infinity,
                                    child: ElevatedButton.icon(
                                      onPressed:
                                          authState.isLoading ? null : _submit,
                                      icon: Icon(customerMode
                                          ? Icons.login
                                          : selectedRole.icon),
                                      label: Text(authState.isLoading
                                          ? 'Signing in...'
                                          : 'Continue'),
                                    ),
                                  ),
                                  const SizedBox(height: 14),
                                  Text(
                                    customerMode
                                        ? 'Customer login currently uses quick phone-based access for MVP rollout. Replace with OTP before production launch.'
                                        : lockedRole == null
                                            ? 'The selected role is enforced against the backend `type` list, so each workspace opens against the correct permission set.'
                                            : '${flavor.shellLabel} app stays locked to one operational role, which is safer for branch devices and easier for staff training.',
                                    style: theme.textTheme.bodySmall?.copyWith(
                                      color: theme.colorScheme.onSurfaceVariant,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    final notifier = ref.read(authProvider.notifier);
    final customerMode =
        ref.read(fixedRoleProvider) == AppRole.customer || _customerMode;

    if (customerMode) {
      await notifier.loginCustomer(
        name: _customerNameController.text.trim(),
        phone: _customerPhoneController.text.trim(),
        email: _customerEmailController.text.trim(),
      );
      return;
    }

    await notifier.loginStaff(
      email: _staffEmailController.text.trim(),
      password: _staffPasswordController.text,
      role: ref.read(fixedRoleProvider) ?? _selectedStaffRole,
    );
  }
}

class _HeroPanel extends StatelessWidget {
  const _HeroPanel({required this.apiBaseUrl});

  final String apiBaseUrl;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(28),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFFE86C2F), Color(0xFF7C3AED)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.16),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Text(
              'Restaurant Suite',
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const Spacer(),
          Text(
            'One platform for guest loyalty, table service, kitchen flow, payments, and ownership control.',
            style: theme.textTheme.displaySmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w800,
              height: 1.1,
            ),
          ),
          const SizedBox(height: 18),
          Text(
            'Designed for Egyptian restaurants and cafes that need a cleaner operator UX than the current stack and more control than off-the-shelf POS bundles.',
            style: theme.textTheme.bodyLarge?.copyWith(
              color: Colors.white.withValues(alpha: 0.88),
              height: 1.5,
            ),
          ),
          const SizedBox(height: 24),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: const [
              _HeroPill(label: 'Customer loyalty'),
              _HeroPill(label: 'Waiter order flow'),
              _HeroPill(label: 'Kitchen board'),
              _HeroPill(label: 'Split payments'),
              _HeroPill(label: 'Owner analytics'),
            ],
          ),
          const SizedBox(height: 24),
          Text(
            'API: $apiBaseUrl',
            style: theme.textTheme.bodySmall?.copyWith(
              color: Colors.white.withValues(alpha: 0.82),
            ),
          ),
        ],
      ),
    );
  }
}

class _HeroPill extends StatelessWidget {
  const _HeroPill({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
