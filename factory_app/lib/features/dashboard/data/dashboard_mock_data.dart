class Kpi {
  const Kpi({required this.title, required this.value, required this.trend});
  final String title;
  final String value;
  final String trend;
}

class AlertItem {
  const AlertItem({required this.message, required this.level});
  final String message;
  final String level;
}

class ActivityItem {
  const ActivityItem({required this.label, required this.timestamp});
  final String label;
  final String timestamp;
}

const kpiData = [
  Kpi(title: 'Production output', value: '1,240 units', trend: '+12% vs last week'),
  Kpi(title: 'Active employees', value: '86', trend: 'Stable'),
  Kpi(title: 'Pending tasks', value: '23', trend: '-5 since yesterday'),
  Kpi(title: 'Quality issues', value: '3', trend: '-1 vs avg'),
];

const productionTrend = [72.0, 80.0, 78.0, 90.0, 96.0, 94.0, 102.0];

const taskDistribution = {
  'In progress': 48.0,
  'Blocked': 12.0,
  'Done': 60.0,
  'New': 16.0,
};

const alerts = [
  AlertItem(message: 'Mixer A overdue for maintenance', level: 'High'),
  AlertItem(message: '5 employees missing afternoon check-in', level: 'Medium'),
  AlertItem(message: 'Project Orion milestone slips by 2 days', level: 'Low'),
];

const activities = [
  ActivityItem(label: 'Supervisor Ana approved overtime for Line 3', timestamp: '2 min ago'),
  ActivityItem(label: 'New NCR opened for QA - Batch 221', timestamp: '18 min ago'),
  ActivityItem(label: 'Maintenance ticket #151 resolved', timestamp: '1 hr ago'),
];
