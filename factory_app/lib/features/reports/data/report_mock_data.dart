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
    title: 'Daily production summary',
    category: 'Production',
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
    title: 'Quality NCR log',
    category: 'QA',
    owner: 'QA Desk',
    date: 'Yesterday',
    status: 'Ready',
  ),
  ReportItem(
    title: 'Project Orion milestones',
    category: 'Projects',
    owner: 'PMO',
    date: 'This week',
    status: 'Delayed',
  ),
];

const reportFilters = ['Production', 'HR', 'QA', 'Projects'];
