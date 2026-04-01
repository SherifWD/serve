import 'package:flutter/material.dart';

import '../../../core/widgets/feature_group.dart';

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
    name: 'Orion packaging line upgrade',
    progress: 0.76,
    owner: 'PMO',
    due: 'Oct 20',
    status: 'On track',
    team: ['Ana', 'Lee', 'Maria'],
  ),
  ProjectItem(
    name: 'ERP rollout plant 2',
    progress: 0.52,
    owner: 'IT',
    due: 'Nov 4',
    status: 'At risk',
    team: ['Sam', 'Priya', 'Jordan'],
  ),
  ProjectItem(
    name: 'Safety training Q4',
    progress: 0.34,
    owner: 'EHS',
    due: 'Oct 30',
    status: 'Behind',
    team: ['Rui', 'Omar'],
  ),
];

const projectFeatures = [
  FeatureEntry(title: 'Projects', subtitle: 'Create/update project records', status: 'Sample', icon: Icons.view_timeline_outlined),
  FeatureEntry(title: 'Project tasks', subtitle: 'Tasks with owners and due dates', status: 'Sample', icon: Icons.task_alt_outlined),
  FeatureEntry(title: 'Change requests', subtitle: 'Project change requests and approvals', status: 'Sample', icon: Icons.change_circle_outlined),
  FeatureEntry(title: 'Reports', subtitle: 'Summary and export endpoints', status: 'Sample', icon: Icons.insert_chart_outlined),
];
