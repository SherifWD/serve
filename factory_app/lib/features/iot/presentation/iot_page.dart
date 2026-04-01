import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/widgets/section_card.dart';
import '../../../core/widgets/feature_group.dart';
import '../../../core/widgets/action_grid.dart';
import '../../../core/widgets/module_data_view.dart';
import '../data/iot_mock_data.dart';

class IotPage extends ConsumerWidget {
  const IotPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    void notify(String msg) => ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));

    final actions = [
      ActionItem(
        title: 'Register device',
        subtitle: 'Add new IoT device with keys',
        icon: Icons.devices_other_outlined,
        onTap: () => notify('Device registered'),
      ),
      ActionItem(
        title: 'Add sensor',
        subtitle: 'Attach sensor to a device',
        icon: Icons.sensors_outlined,
        onTap: () => notify('Sensor added'),
      ),
      ActionItem(
        title: 'View telemetry',
        subtitle: 'Open latest readings and charts',
        icon: Icons.timeline_outlined,
        onTap: () => notify('Telemetry stream opened'),
      ),
    ];

    return SingleChildScrollView(
      child: Column(
        children: [
          SectionCard(
            title: 'IoT telemetry',
            child: FeatureGroup(
              entries: iotFeatures,
              onEntryTap: (entry) => notify('${entry.title} opened'),
            ),
          ),
          const SizedBox(height: 12),
          SectionCard(title: 'Quick IoT actions', child: ActionGrid(items: actions)),
          const SizedBox(height: 12),
          const ModuleDataView(
            moduleId: 'iot',
            endpoint: '/iot/devices',
            statusOptions: ['Online', 'Offline'],
            title: 'Devices',
          ),
        ],
      ),
    );
  }
}
