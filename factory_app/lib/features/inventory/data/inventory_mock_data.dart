import 'package:flutter/material.dart';
import '../../../core/widgets/feature_group.dart';

const inventoryFeatures = [
  FeatureEntry(
    title: 'Ingredients',
    subtitle: 'CRUD ingredients and stock updates',
    status: 'Mocked',
    icon: Icons.spa_outlined,
  ),
  FeatureEntry(
    title: 'Inventory items',
    subtitle: 'List items with qty, costs, branches',
    status: 'Mocked',
    icon: Icons.inventory_2_outlined,
  ),
  FeatureEntry(
    title: 'Inventory transactions',
    subtitle: 'Adjustments, receipts, issues logs',
    status: 'Sample',
    icon: Icons.swap_horiz_outlined,
  ),
  FeatureEntry(
    title: 'Suppliers',
    subtitle: 'Supplier directory for replenishment',
    status: 'Sample',
    icon: Icons.store_mall_directory_outlined,
  ),
  FeatureEntry(
    title: 'Recipes & BOM',
    subtitle: 'Recipes linked to ingredients for costing',
    status: 'Sample',
    icon: Icons.science_outlined,
  ),
];
