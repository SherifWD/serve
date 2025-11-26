class AttendanceLog {
  const AttendanceLog({required this.name, required this.time, required this.status});
  final String name;
  final String time;
  final String status;
}

const attendanceLogs = [
  AttendanceLog(name: 'Ana Jimenez', time: '07:02', status: 'On time'),
  AttendanceLog(name: 'Lee Wong', time: '07:18', status: 'Late'),
  AttendanceLog(name: 'Sam Patel', time: '07:05', status: 'On time'),
  AttendanceLog(name: 'Maria Lopez', time: 'Missed', status: 'Missing'),
];

const monthlyHours = [8.0, 8.2, 7.5, 8.6, 8.1, 7.8, 8.0];
