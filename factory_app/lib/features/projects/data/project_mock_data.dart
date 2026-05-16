class ProjectItem {
  const ProjectItem({
    required this.name,
    required this.progress,
    required this.owner,
    required this.due,
    required this.status,
    required this.team,
  });

  final String name;
  final double progress;
  final String owner;
  final String due;
  final String status;
  final List<String> team;
}

const projects = [
  ProjectItem(
    name: 'New branch opening',
    progress: 0.76,
    owner: 'Operations',
    due: 'Oct 20',
    status: 'On track',
    team: ['Ana', 'Lee', 'Maria'],
  ),
  ProjectItem(
    name: 'POS rollout branch 2',
    progress: 0.52,
    owner: 'IT',
    due: 'Nov 4',
    status: 'At risk',
    team: ['Sam', 'Priya', 'Jordan'],
  ),
  ProjectItem(
    name: 'Food safety training Q4',
    progress: 0.34,
    owner: 'HR',
    due: 'Oct 30',
    status: 'Behind',
    team: ['Rui', 'Omar'],
  ),
];
