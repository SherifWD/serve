import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/section_card.dart';
import '../data/hr_mock_data.dart';

class HrPage extends ConsumerStatefulWidget {
  const HrPage({super.key});

  @override
  ConsumerState<HrPage> createState() => _HrPageState();
}

class _HrPageState extends ConsumerState<HrPage> {
  bool _clockedIn = false;
  DateTime? _clockedInAt;
  DateTime? _clockedOutAt;
  DateTime? _vacStart;
  DateTime? _vacEnd;
  final _noteController = TextEditingController();
  late List<HrRequest> _requests;

  @override
  void initState() {
    super.initState();
    _requests = List<HrRequest>.from(hrRequests);
  }

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'Clock in / out',
            trailing: FilledButton.icon(
              onPressed: _toggleClock,
              icon: Icon(_clockedIn ? Icons.logout : Icons.login),
              label: Text(_clockedIn ? 'Clock out' : 'Clock in'),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Wrap(
                  spacing: 12,
                  runSpacing: 8,
                  children: [
                    Chip(label: Text(_clockedIn ? 'Status: Clocked in' : 'Status: Clocked out')),
                    Chip(label: Text('Last in: ${_formatDate(_clockedInAt)}')),
                    Chip(label: Text('Last out: ${_formatDate(_clockedOutAt)}')),
                  ],
                ),
                const SizedBox(height: 8),
                Text('Tap the button to simulate a live clock punch for your shift.', style: theme.textTheme.bodySmall),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Vacation request',
            trailing: FilledButton.tonal(
              onPressed: _submitVacation,
              child: const Text('Submit request'),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Wrap(
                  spacing: 12,
                  runSpacing: 8,
                  children: [
                    OutlinedButton.icon(
                      onPressed: () => _pickDate(true),
                      icon: const Icon(Icons.calendar_today_outlined),
                      label: Text(_vacStart == null ? 'Start date' : DateFormat.yMMMd().format(_vacStart!)),
                    ),
                    OutlinedButton.icon(
                      onPressed: () => _pickDate(false),
                      icon: const Icon(Icons.calendar_today_outlined),
                      label: Text(_vacEnd == null ? 'End date' : DateFormat.yMMMd().format(_vacEnd!)),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _noteController,
                  decoration: const InputDecoration(labelText: 'Notes / reason'),
                  maxLines: 2,
                ),
                const SizedBox(height: 8),
                Text('Requests appear in the list below and can be approved in a future backend hook.',
                    style: theme.textTheme.bodySmall),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'HR requests',
            child: Column(
              children: [
                for (final req in _requests)
                  ListTile(
                    leading: const Icon(Icons.request_page_outlined),
                    title: Text(req.title),
                    subtitle: Text(req.employee),
                    trailing: Chip(label: Text(req.status)),
                    onTap: () => _toast('Opening ${req.title}'),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Documents',
            trailing: FilledButton.tonalIcon(
              onPressed: () => _toast('Upload document'),
              icon: const Icon(Icons.upload_file_outlined),
              label: const Text('Upload'),
            ),
            child: Column(
              children: [
                for (final doc in hrDocuments)
                  Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: ListTile(
                      leading: const Icon(Icons.picture_as_pdf_outlined),
                      title: Text(doc.name),
                      subtitle: Text('${doc.type} - ${doc.owner}'),
                      trailing: IconButton(onPressed: () => _toast('Downloading ${doc.name}'), icon: const Icon(Icons.download_outlined)),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Quick HR actions',
            child: ActionGrid(
              items: [
                ActionItem(
                  title: 'Update profile',
                  subtitle: 'Edit personal info, emergency contacts',
                  icon: Icons.person_outline,
                  onTap: () => _toast('Profile update launched'),
                ),
                ActionItem(
                  title: 'Upload contract',
                  subtitle: 'Send signed document to HR',
                  icon: Icons.picture_as_pdf_outlined,
                  onTap: () => _toast('Contract upload started'),
                ),
                ActionItem(
                  title: 'Request training',
                  subtitle: 'Submit for safety or role training',
                  icon: Icons.school_outlined,
                  onTap: () => _toast('Training request sent'),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Profile snapshot',
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    CircleAvatar(
                      radius: 28,
                      child: Text('AL', style: theme.textTheme.titleLarge?.copyWith(color: Colors.white)),
                    ),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Alex Lee', style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold)),
                        Text('Employee - Assembly'),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 8,
                  children: const [
                    Chip(label: Text('Documents up to date')),
                    Chip(label: Text('No open HR cases')),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  void _toggleClock() {
    final now = DateTime.now();
    setState(() {
      if (_clockedIn) {
        _clockedOutAt = now;
      } else {
        _clockedInAt = now;
      }
      _clockedIn = !_clockedIn;
    });
    _toast(_clockedIn ? 'Clocked in at ${_formatDate(_clockedInAt)}' : 'Clocked out at ${_formatDate(_clockedOutAt)}');
  }

  Future<void> _pickDate(bool isStart) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: now,
      firstDate: now.subtract(const Duration(days: 30)),
      lastDate: now.add(const Duration(days: 365)),
    );
    if (picked != null) {
      setState(() {
        if (isStart) {
          _vacStart = picked;
        } else {
          _vacEnd = picked;
        }
      });
    }
  }

  void _submitVacation() {
    if (_vacStart == null || _vacEnd == null) {
      _toast('Select start and end dates first');
      return;
    }
    if (_vacStart!.isAfter(_vacEnd!)) {
      _toast('Start date must be before end date');
      return;
    }
    final title = 'Vacation ${DateFormat.MMMd().format(_vacStart!)} - ${DateFormat.MMMd().format(_vacEnd!)}';
    setState(() {
      _requests.insert(0, HrRequest(title: title, employee: 'You', status: 'Submitted'));
    });
    _toast('Vacation request submitted');
    _noteController.clear();
  }

  String _formatDate(DateTime? date) {
    if (date == null) return '—';
    return DateFormat('MMM d, HH:mm').format(date);
  }

  void _toast(String message) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }
}
