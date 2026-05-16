class EmployeeItem {
  const EmployeeItem({
    required this.name,
    required this.role,
    required this.department,
    required this.performance,
  });

  final String name;
  final String role;
  final String department;
  final String performance;
}

const employees = [
  EmployeeItem(
      name: 'Ana Jimenez',
      role: 'Supervisor',
      department: 'Dining room',
      performance: 'A'),
  EmployeeItem(
      name: 'Lee Wong',
      role: 'Kitchen lead',
      department: 'Kitchen',
      performance: 'B+'),
  EmployeeItem(
      name: 'Sam Patel',
      role: 'Planner',
      department: 'Operations',
      performance: 'A-'),
  EmployeeItem(
      name: 'Maria Lopez',
      role: 'Server',
      department: 'Dining room',
      performance: 'B'),
];

const permissionsByRole = {
  'Owner': ['Full access', 'Payroll approvals', 'Role management'],
  'Manager': ['Task assignment', 'Time approvals', 'Report exports'],
  'Employee': ['Task updates', 'My attendance', 'My salary slips'],
};
