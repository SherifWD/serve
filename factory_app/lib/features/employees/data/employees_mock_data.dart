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
  EmployeeItem(name: 'Ana Jimenez', role: 'Supervisor', department: 'Assembly', performance: 'A'),
  EmployeeItem(name: 'Lee Wong', role: 'Technician', department: 'Maintenance', performance: 'B+'),
  EmployeeItem(name: 'Sam Patel', role: 'Planner', department: 'Planning', performance: 'A-'),
  EmployeeItem(name: 'Maria Lopez', role: 'Operator', department: 'Packaging', performance: 'B'),
];

const permissionsByRole = {
  'Owner': ['Full access', 'Payroll approvals', 'Role management'],
  'Manager': ['Task assignment', 'Time approvals', 'Report exports'],
  'Employee': ['Task updates', 'My attendance', 'My salary slips'],
};
