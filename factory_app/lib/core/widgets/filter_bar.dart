import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

class FilterState {
  FilterState({this.search = '', this.status, this.from, this.to});
  final String search;
  final String? status;
  final DateTime? from;
  final DateTime? to;

  FilterState copyWith({String? search, String? status, DateTime? from, DateTime? to}) {
    return FilterState(
      search: search ?? this.search,
      status: status ?? this.status,
      from: from ?? this.from,
      to: to ?? this.to,
    );
  }
}

class FilterBar extends StatelessWidget {
  const FilterBar({
    super.key,
    required this.state,
    required this.onChanged,
    this.statusOptions = const [],
  });

  final FilterState state;
  final void Function(FilterState) onChanged;
  final List<String> statusOptions;

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 12,
      runSpacing: 8,
      crossAxisAlignment: WrapCrossAlignment.center,
      children: [
        SizedBox(
          width: 220,
          child: TextField(
            decoration: const InputDecoration(labelText: 'Search', prefixIcon: Icon(Icons.search)),
            onChanged: (val) => onChanged(state.copyWith(search: val)),
          ),
        ),
        if (statusOptions.isNotEmpty)
          DropdownButton<String?>(
            value: state.status,
            hint: const Text('Status'),
            onChanged: (val) => onChanged(state.copyWith(status: val)),
            items: [
              const DropdownMenuItem<String?>(value: null, child: Text('All')),
              ...statusOptions.map((s) => DropdownMenuItem(value: s, child: Text(s))).toList(),
            ],
          ),
        OutlinedButton.icon(
          onPressed: () => _pickDate(context, true),
          icon: const Icon(Icons.calendar_today_outlined),
          label: Text(state.from == null ? 'From' : DateFormat.yMMMd().format(state.from!)),
        ),
        OutlinedButton.icon(
          onPressed: () => _pickDate(context, false),
          icon: const Icon(Icons.calendar_today_outlined),
          label: Text(state.to == null ? 'To' : DateFormat.yMMMd().format(state.to!)),
        ),
      ],
    );
  }

  Future<void> _pickDate(BuildContext context, bool isFrom) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: now,
      firstDate: now.subtract(const Duration(days: 365)),
      lastDate: now.add(const Duration(days: 365)),
    );
    if (picked != null) {
      if (isFrom) {
        onChanged(state.copyWith(from: picked));
      } else {
        onChanged(state.copyWith(to: picked));
      }
    }
  }
}
