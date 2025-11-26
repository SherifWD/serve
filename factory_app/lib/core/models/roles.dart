enum UserRole { owner, manager, employee }

extension UserRoleTitle on UserRole {
  String get label {
    switch (this) {
      case UserRole.owner:
        return 'Owner';
      case UserRole.manager:
        return 'Manager';
      case UserRole.employee:
        return 'Employee';
    }
  }
}
