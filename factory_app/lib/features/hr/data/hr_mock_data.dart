class HrRequest {
  const HrRequest({required this.title, required this.employee, required this.status});
  final String title;
  final String employee;
  final String status;
}

class DocumentItem {
  const DocumentItem({required this.name, required this.type, required this.owner});
  final String name;
  final String type;
  final String owner;
}

const hrRequests = [
  HrRequest(title: 'Vacation request', employee: 'Lee Wong', status: 'Approved'),
  HrRequest(title: 'Contract renewal', employee: 'Ana Jimenez', status: 'Pending'),
  HrRequest(title: 'Shift change', employee: 'Maria Lopez', status: 'Review'),
];

const hrDocuments = [
  DocumentItem(name: 'Contract - Ana', type: 'PDF', owner: 'HR'),
  DocumentItem(name: 'Policy - Safety', type: 'PDF', owner: 'EHS'),
  DocumentItem(name: 'Handbook', type: 'PDF', owner: 'HR'),
];
