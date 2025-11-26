import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../data/hr_mock_data.dart';

class HrPage extends ConsumerWidget {
  const HrPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'HR requests',
            child: Column(
              children: [
                for (final req in hrRequests)
                  ListTile(
                    leading: const Icon(Icons.request_page_outlined),
                    title: Text(req.title),
                    subtitle: Text(req.employee),
                    trailing: Chip(label: Text(req.status)),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Documents',
            trailing: FilledButton.tonalIcon(
              onPressed: () {},
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
                      trailing: IconButton(onPressed: () {}, icon: const Icon(Icons.download_outlined)),
                    ),
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
}
