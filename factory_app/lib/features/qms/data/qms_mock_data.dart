import 'package:flutter/material.dart';
import '../../../core/widgets/feature_group.dart';

const qmsFeatures = [
  FeatureEntry(title: 'Inspection plans', subtitle: 'Sampling plans and checklists', status: 'Sample', icon: Icons.rule_folder_outlined),
  FeatureEntry(title: 'Inspections', subtitle: 'Execution logs with results', status: 'Sample', icon: Icons.fact_check_outlined),
  FeatureEntry(title: 'Non-conformities', subtitle: 'NCR log with containment actions', status: 'Sample', icon: Icons.report_gmailerrorred_outlined),
  FeatureEntry(title: 'CAPA actions', subtitle: 'Corrective actions and owners', status: 'Sample', icon: Icons.check_circle_outline),
  FeatureEntry(title: 'Audits', subtitle: 'Audit programs and findings', status: 'Sample', icon: Icons.library_books_outlined),
];
