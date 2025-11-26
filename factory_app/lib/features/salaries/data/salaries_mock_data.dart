class SalaryItem {
  const SalaryItem({required this.employee, required this.month, required this.net, required this.adjustments, required this.status});
  final String employee;
  final String month;
  final String net;
  final String adjustments;
  final String status;
}

const salaryItems = [
  SalaryItem(employee: 'Ana Jimenez', month: 'Sep', net: '\$2,450', adjustments: '+120 OT', status: 'Approved'),
  SalaryItem(employee: 'Lee Wong', month: 'Sep', net: '\$2,120', adjustments: '-40 missing', status: 'Review'),
  SalaryItem(employee: 'Sam Patel', month: 'Sep', net: '\$2,780', adjustments: '+60 bonus', status: 'Approved'),
];
