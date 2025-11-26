class NotificationItem {
  const NotificationItem({required this.message, required this.time, required this.severity});
  final String message;
  final String time;
  final String severity;
}

const notifications = [
  NotificationItem(message: 'Line 2 changeover completed', time: 'Just now', severity: 'info'),
  NotificationItem(message: 'New task assigned to you', time: '5 min', severity: 'info'),
  NotificationItem(message: 'Energy usage above baseline', time: '30 min', severity: 'warning'),
  NotificationItem(message: 'Security: payroll export downloaded', time: '1 hr', severity: 'alert'),
];
