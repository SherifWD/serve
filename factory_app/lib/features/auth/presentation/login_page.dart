import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/config/app_flavor.dart';
import '../../../core/localization/app_language.dart';
import '../../../core/models/app_models.dart';
import '../../../core/network/api_client.dart';
import '../../../core/widgets/language_toggle.dart';
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
  final _customerOtpController = TextEditingController();

  AppRole _selectedStaffRole = AppRole.owner;

  @override
  void dispose() {
    _staffEmailController.dispose();
    _staffPasswordController.dispose();
    _customerNameController.dispose();
    _customerPhoneController.dispose();
    _customerEmailController.dispose();
    _customerOtpController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final lockedRole = ref.watch(fixedRoleProvider);
    final theme = Theme.of(context);
    final customerMode = lockedRole == AppRole.customer;
    final selectedRole = lockedRole ?? _selectedStaffRole;
    final strings = ref.watch(appStringsProvider);
    final otpChallenge = authState.customerOtpChallenge;

    if (!authState.hasBootstrapped) {
      return Scaffold(
        body: LoadingView(label: strings.t('login.preparing')),
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
                                apiBaseUrl: ref.watch(apiBaseUrlProvider),
                              ),
                            ),
                          Expanded(
                            child: SingleChildScrollView(
                              padding: const EdgeInsets.all(28),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Align(
                                    alignment: AlignmentDirectional.topEnd,
                                    child: LanguageToggle(),
                                  ),
                                  const SizedBox(height: 18),
                                  Text(
                                    customerMode
                                        ? strings.t('login.customerTitle')
                                        : lockedRole == null
                                            ? strings.t('login.staffTitle')
                                            : '${strings.roleLabel(selectedRole.apiType)} ${strings.t('login.staffTitle')}',
                                    style: theme.textTheme.headlineMedium
                                        ?.copyWith(
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                  const SizedBox(height: 8),
                                  Text(
                                    customerMode
                                        ? strings.t('login.customerSubtitle')
                                        : lockedRole == null
                                            ? strings.t('login.staffSubtitle')
                                            : strings.t('login.pinnedSubtitle'),
                                    style: theme.textTheme.bodyLarge?.copyWith(
                                      color: theme.colorScheme.onSurfaceVariant,
                                    ),
                                  ),
                                  const SizedBox(height: 20),
                                  const SizedBox(height: 24),
                                  if (!customerMode) ...[
                                    TextField(
                                      controller: _staffEmailController,
                                      keyboardType: TextInputType.emailAddress,
                                      decoration: InputDecoration(
                                        labelText: strings.t('login.email'),
                                        hintText: 'owner@restaurant.com',
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    TextField(
                                      controller: _staffPasswordController,
                                      obscureText: true,
                                      decoration: InputDecoration(
                                        labelText: strings.t('login.password'),
                                      ),
                                    ),
                                    const SizedBox(height: 18),
                                    Text(
                                      lockedRole == null
                                          ? strings.t('login.workspace')
                                          : strings.t('login.pinnedWorkspace'),
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
                                              label: Text(
                                                strings.roleLabel(role.apiType),
                                              ),
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
                                        label: Text(strings
                                            .roleLabel(selectedRole.apiType)),
                                      ),
                                  ] else if (otpChallenge == null) ...[
                                    TextField(
                                      controller: _customerNameController,
                                      decoration: InputDecoration(
                                        labelText: strings.t('login.name'),
                                        hintText: strings.t('login.guestName'),
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    TextField(
                                      controller: _customerPhoneController,
                                      keyboardType: TextInputType.phone,
                                      decoration: InputDecoration(
                                        labelText: strings.t('login.phone'),
                                        hintText: '01xxxxxxxxx',
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    TextField(
                                      controller: _customerEmailController,
                                      keyboardType: TextInputType.emailAddress,
                                      decoration: InputDecoration(
                                        labelText:
                                            strings.t('login.optionalEmail'),
                                      ),
                                    ),
                                  ] else ...[
                                    Text(
                                      strings.t(
                                        'login.otpSent',
                                        params: {
                                          'destination':
                                              otpChallenge.destination,
                                        },
                                      ),
                                      style:
                                          theme.textTheme.bodyMedium?.copyWith(
                                        color:
                                            theme.colorScheme.onSurfaceVariant,
                                      ),
                                    ),
                                    const SizedBox(height: 16),
                                    TextField(
                                      controller: _customerOtpController,
                                      keyboardType: TextInputType.number,
                                      maxLength: 6,
                                      decoration: InputDecoration(
                                        labelText: strings.t('login.otpCode'),
                                        counterText: '',
                                      ),
                                    ),
                                    if (otpChallenge.debugCode != null) ...[
                                      const SizedBox(height: 8),
                                      Text(
                                        strings.t(
                                          'login.devOtp',
                                          params: {
                                            'code': otpChallenge.debugCode!,
                                          },
                                        ),
                                        style:
                                            theme.textTheme.bodySmall?.copyWith(
                                          color: theme
                                              .colorScheme.onSurfaceVariant,
                                        ),
                                      ),
                                    ],
                                    const SizedBox(height: 8),
                                    TextButton.icon(
                                      onPressed: authState.isLoading
                                          ? null
                                          : () {
                                              _customerOtpController.clear();
                                              ref
                                                  .read(authProvider.notifier)
                                                  .resetCustomerOtpChallenge();
                                            },
                                      icon: const Icon(Icons.edit_outlined),
                                      label:
                                          Text(strings.t('login.changePhone')),
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
                                          ? (otpChallenge == null
                                              ? Icons.sms_outlined
                                              : Icons.verified_user_outlined)
                                          : selectedRole.icon),
                                      label: Text(authState.isLoading
                                          ? strings.t('login.signingIn')
                                          : customerMode
                                              ? (otpChallenge == null
                                                  ? strings
                                                      .t('login.requestCode')
                                                  : strings
                                                      .t('login.verifyCode'))
                                              : strings.t('login.continue')),
                                    ),
                                  ),
                                  const SizedBox(height: 14),
                                  Text(
                                    customerMode
                                        ? strings.t('login.customerNote')
                                        : lockedRole == null
                                            ? strings.t('login.staffNote')
                                            : strings.t('login.lockedNote'),
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
    final customerMode = ref.read(fixedRoleProvider) == AppRole.customer;

    if (customerMode) {
      if (ref.read(authProvider).customerOtpChallenge == null) {
        await notifier.requestCustomerOtp(
          name: _customerNameController.text.trim(),
          phone: _customerPhoneController.text.trim(),
          email: _customerEmailController.text.trim(),
        );
      } else {
        await notifier.verifyCustomerOtp(
          code: _customerOtpController.text.trim(),
        );
      }
      return;
    }

    await notifier.loginStaff(
      email: _staffEmailController.text.trim(),
      password: _staffPasswordController.text,
      role: ref.read(fixedRoleProvider) ?? _selectedStaffRole,
    );
  }
}

class _HeroPanel extends ConsumerWidget {
  const _HeroPanel({required this.apiBaseUrl});

  final String apiBaseUrl;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final strings = ref.watch(appStringsProvider);
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
            child: Text(
              strings.t('hero.brand'),
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const Spacer(),
          Text(
            strings.t('hero.title'),
            style: theme.textTheme.displaySmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w800,
              height: 1.1,
            ),
          ),
          const SizedBox(height: 18),
          Text(
            strings.t('hero.subtitle'),
            style: theme.textTheme.bodyLarge?.copyWith(
              color: Colors.white.withValues(alpha: 0.88),
              height: 1.5,
            ),
          ),
          const SizedBox(height: 24),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              _HeroPill(label: strings.t('hero.customerLoyalty')),
              _HeroPill(label: strings.t('hero.waiterFlow')),
              _HeroPill(label: strings.t('hero.kitchenBoard')),
              _HeroPill(label: strings.t('hero.splitPayments')),
              _HeroPill(label: strings.t('hero.ownerAnalytics')),
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
