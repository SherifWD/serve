import 'package:flutter/material.dart';
import '../../../core/widgets/feature_group.dart';

const posFeatures = [
  FeatureEntry(
    title: 'Tables & moves',
    subtitle: 'List tables, open table details, move orders between tables',
    status: 'Sample data',
    icon: Icons.table_bar_outlined,
  ),
  FeatureEntry(
    title: 'Orders',
    subtitle: 'List, show, create, update, reopen, send to KDS/Cashier, pay, receipt',
    status: 'Mocked',
    icon: Icons.receipt_long_outlined,
  ),
  FeatureEntry(
    title: 'Order items',
    subtitle: 'History and refund/change flow',
    status: 'Mocked',
    icon: Icons.history_outlined,
  ),
  FeatureEntry(
    title: 'Products & modifiers',
    subtitle: 'Menu products, modifiers, available options for waiters',
    status: 'Mocked',
    icon: Icons.restaurant_menu_outlined,
  ),
  FeatureEntry(
    title: 'Coupons',
    subtitle: 'Verify coupons and apply discounts',
    status: 'Mocked',
    icon: Icons.local_offer_outlined,
  ),
  FeatureEntry(
    title: 'KDS',
    subtitle: 'Kitchen tickets and item status updates',
    status: 'Mocked',
    icon: Icons.kitchen,
  ),
];
