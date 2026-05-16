class ReportItem {
  const ReportItem({
    required this.title,
    required this.category,
    required this.owner,
    required this.date,
    required this.status,
  });

  final String title;
  final String category;
  final String owner;
  final String date;
  final String status;
}

const reportItems = [
  ReportItem(
    title: 'Daily sales summary',
    category: 'Sales',
    owner: 'Ana Jimenez',
    date: 'Today',
    status: 'Ready',
  ),
  ReportItem(
    title: 'Overtime and absence',
    category: 'HR',
    owner: 'HR Ops',
    date: 'Today',
    status: 'Pending',
  ),
  ReportItem(
    title: 'Service issue log',
    category: 'Service',
    owner: 'Floor team',
    date: 'Yesterday',
    status: 'Ready',
  ),
  ReportItem(
    title: 'New branch milestones',
    category: 'Projects',
    owner: 'Operations',
    date: 'This week',
    status: 'Delayed',
  ),
];

const reportFilters = ['Sales', 'HR', 'Service', 'Projects'];
