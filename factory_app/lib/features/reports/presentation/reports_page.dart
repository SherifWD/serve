import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/chart_cards.dart';
import '../../../core/widgets/section_card.dart';
import '../data/report_mock_data.dart';

class ReportsPage extends ConsumerStatefulWidget {
  const ReportsPage({super.key});

  @override
  ConsumerState<ReportsPage> createState() => _ReportsPageState();
}

class _ReportsPageState extends ConsumerState<ReportsPage> {
  String _search = '';
  String? _filter;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final filtered = reportItems.where((item) {
      final matchesSearch = item.title.toLowerCase().contains(_search.toLowerCase());
      final matchesFilter = _filter == null || item.category == _filter;
      return matchesSearch && matchesFilter;
    }).toList();

    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: TextField(
                  decoration: const InputDecoration(
                    prefixIcon: Icon(Icons.search),
                    labelText: 'Search reports',
                  ),
                  onChanged: (value) => setState(() => _search = value),
                ),
              ),
              const SizedBox(width: 12),
              FilledButton.icon(
                onPressed: () {},
                icon: const Icon(Icons.picture_as_pdf_outlined),
                label: const Text('Export PDF'),
              ),
              const SizedBox(width: 8),
              FilledButton.tonalIcon(
                onPressed: () {},
                icon: const Icon(Icons.table_view_outlined),
                label: const Text('Export Excel'),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            children: [
              ChoiceChip(
                label: const Text('All'),
                selected: _filter == null,
                onSelected: (_) => setState(() => _filter = null),
              ),
              for (final f in reportFilters)
                ChoiceChip(
                  label: Text(f),
                  selected: _filter == f,
                  onSelected: (_) => setState(() => _filter = f),
                ),
            ],
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Report queue',
            child: Column(
              children: [
                for (final item in filtered)
                  Card(
                    margin: const EdgeInsets.only(bottom: 8),
                    child: ListTile(
                      leading: Icon(Icons.insert_chart_outlined, color: theme.colorScheme.primary),
                      title: Text(item.title),
                      subtitle: Text('${item.category} - ${item.owner}'),
                      trailing: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          Chip(label: Text(item.status)),
                          Text(item.date, style: theme.textTheme.labelSmall),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(
            title: 'Monthly summary',
            child: SimpleBarChart(values: const [14, 18, 16, 20, 24, 22]),
          ),
        ],
      ),
    );
  }
}
