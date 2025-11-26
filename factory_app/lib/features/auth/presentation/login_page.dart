import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../providers/auth_providers.dart';

class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});

  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController(text: 'owner@factory.com');
  final _passwordController = TextEditingController(text: 'password');

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final theme = Theme.of(context);

    return Scaffold(
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 420),
          child: Card(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Form(
                key: _formKey,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Factory Ops Login', style: theme.textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold)),
                    const SizedBox(height: 12),
                    Text('Use role-based mock accounts to explore modules.'),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _emailController,
                      decoration: const InputDecoration(labelText: 'Email'),
                      validator: (value) => value != null && value.contains('@') ? null : 'Enter a valid email',
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _passwordController,
                      decoration: const InputDecoration(labelText: 'Password'),
                      obscureText: true,
                      validator: (value) => value != null && value.isNotEmpty ? null : 'Password required',
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: FilledButton(
                            onPressed: authState.isLoading
                                ? null
                                : () async {
                                    if (!_formKey.currentState!.validate()) return;
                                    await ref
                                        .read(authProvider.notifier)
                                        .login(_emailController.text.trim(), _passwordController.text.trim());
                                    if (mounted && ref.read(isAuthenticatedProvider)) {
                                      context.go('/home/dashboard');
                                    }
                                  },
                            child: authState.isLoading
                                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2))
                                : const Text('Login'),
                          ),
                        ),
                      ],
                    ),
                    if (authState.error != null) ...[
                      const SizedBox(height: 12),
                      Text(authState.error!, style: TextStyle(color: theme.colorScheme.error)),
                    ],
                    const SizedBox(height: 16),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        _MockAccountChip(email: 'owner@factory.com'),
                        _MockAccountChip(email: 'manager@factory.com'),
                        _MockAccountChip(email: 'employee@factory.com'),
                      ],
                    )
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _MockAccountChip extends ConsumerWidget {
  const _MockAccountChip({required this.email});

  final String email;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return ActionChip(
      label: Text(email),
      onPressed: () async {
        await ref.read(authProvider.notifier).login(email, 'password');
        if (context.mounted && ref.read(isAuthenticatedProvider)) {
          context.go('/home/dashboard');
        }
      },
    );
  }
}
