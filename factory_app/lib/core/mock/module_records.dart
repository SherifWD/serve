class ModuleRecords {
  static final Map<String, List<Map<String, dynamic>>> _records = {
    'pos': [
      {'title': 'Table A12', 'status': 'Open', 'subtitle': 'Order #451 - 3 items', 'date': 'Today'},
      {'title': 'Table B3', 'status': 'Preparing', 'subtitle': 'Order #450 - KDS sent', 'date': 'Today'},
      {'title': 'Table C7', 'status': 'Paid', 'subtitle': 'Order #449 - Card', 'date': 'Today'},
    ],
    'inventory': [
      {'title': 'Sugar 25kg', 'status': 'In stock', 'subtitle': 'Qty: 120, cost: 12.5', 'date': 'This week'},
      {'title': 'Cocoa beans', 'status': 'Low', 'subtitle': 'Qty: 12, supplier: ChocoTrade', 'date': 'This week'},
      {'title': 'Vanilla pods', 'status': 'Out', 'subtitle': 'Qty: 0, PO pending', 'date': 'This week'},
    ],
    'erp': [
      {'title': 'Site - Cairo', 'status': 'Active', 'subtitle': '2 departments', 'date': 'Updated today'},
      {'title': 'Item SKU-1001', 'status': 'Draft', 'subtitle': 'Awaiting cost', 'date': 'Updated yesterday'},
    ],
    'mes': [
      {'title': 'WO-2024-001', 'status': 'Running', 'subtitle': 'Line 1, 60% done', 'date': 'Today'},
      {'title': 'WO-2024-002', 'status': 'Scheduled', 'subtitle': 'Line 2, starts 3pm', 'date': 'Today'},
    ],
    'plm': [
      {'title': 'Design Rev A', 'status': 'Released', 'subtitle': 'Snack Bar Wrapper', 'date': 'Oct 01'},
      {'title': 'ECO-14', 'status': 'Pending', 'subtitle': 'Change film thickness', 'date': 'Oct 03'},
    ],
    'scm': [
      {'title': 'PO-556', 'status': 'Open', 'subtitle': 'Supplier: FreshGoods', 'date': 'Today'},
      {'title': 'Inbound #88', 'status': 'Arrived', 'subtitle': 'Dock 3', 'date': 'Today'},
    ],
    'wms': [
      {'title': 'Lot 2024-10-05', 'status': 'Hold', 'subtitle': 'Qty 400, Bin A-03', 'date': 'Today'},
      {'title': 'Transfer TO-77', 'status': 'In transit', 'subtitle': 'A-01 to B-02', 'date': 'Today'},
    ],
    'qms': [
      {'title': 'Inspection 120', 'status': 'Pass', 'subtitle': 'Line 1 - bars', 'date': 'Today'},
      {'title': 'NCR #55', 'status': 'Open', 'subtitle': 'Packaging defect', 'date': 'Today'},
    ],
    'hrms': [
      {'title': 'Worker - Ahmed M.', 'status': 'Active', 'subtitle': 'Department: Assembly', 'date': 'Today'},
      {'title': 'Leave #45', 'status': 'Pending', 'subtitle': 'Oct 14-16', 'date': 'Submitted today'},
    ],
    'cmms': [
      {'title': 'Asset - Mixer A', 'status': 'Running', 'subtitle': 'Next PM in 2d', 'date': 'Today'},
      {'title': 'WO-M-220', 'status': 'Waiting parts', 'subtitle': 'Spare: Seal kit', 'date': 'Today'},
    ],
    'finance': [
      {'title': 'JE-9021', 'status': 'Posted', 'subtitle': 'Month-end accrual', 'date': 'Oct 05'},
      {'title': 'AP INV-441', 'status': 'Due soon', 'subtitle': 'Supplier: FreshGoods', 'date': 'Oct 10'},
    ],
    'crm': [
      {'title': 'Lead - RetailCo', 'status': 'New', 'subtitle': 'Owner: Sarah', 'date': 'Today'},
      {'title': 'Opp - Supermart', 'status': 'Negotiation', 'subtitle': 'Value 120k', 'date': 'This week'},
    ],
    'bi': [
      {'title': 'Dashboard - Ops', 'status': 'Active', 'subtitle': '12 widgets', 'date': 'Updated today'},
      {'title': 'KPI - OEE', 'status': 'At risk', 'subtitle': 'Target 85%', 'date': 'Today'},
    ],
    'hse': [
      {'title': 'Incident - slip', 'status': 'Open', 'subtitle': 'Area: Line 2', 'date': 'Today'},
      {'title': 'Audit - GMP', 'status': 'Planned', 'subtitle': 'Nov 12', 'date': 'This month'},
    ],
    'dms': [
      {'title': 'Policy.pdf', 'status': 'Published', 'subtitle': 'Folder: HR', 'date': 'Oct 04'},
      {'title': 'SOP-21.docx', 'status': 'Draft', 'subtitle': 'Folder: QA', 'date': 'Oct 03'},
    ],
    'visitor': [
      {'title': 'Visitor - John D.', 'status': 'Checked in', 'subtitle': 'Host: Omar', 'date': '10:10'},
      {'title': 'Entry #202', 'status': 'Scheduled', 'subtitle': 'Client tour', 'date': 'Tomorrow'},
    ],
    'iot': [
      {'title': 'Device - Gateway 1', 'status': 'Online', 'subtitle': '3 sensors', 'date': 'Now'},
      {'title': 'Temp Sensor 14', 'status': 'Offline', 'subtitle': 'Last seen 5m ago', 'date': 'Today'},
    ],
    'procurement': [
      {'title': 'Tender - Packaging', 'status': 'Open', 'subtitle': '4 vendors invited', 'date': 'Today'},
      {'title': 'PR-120', 'status': 'Pending', 'subtitle': 'Requester: Ops', 'date': 'Today'},
    ],
    'commerce': [
      {'title': 'Order #881', 'status': 'Packed', 'subtitle': 'Customer: Lina', 'date': 'Today'},
      {'title': 'Customer - Youssef', 'status': 'Active', 'subtitle': 'LTV 1.2k', 'date': 'This week'},
    ],
    'budgeting': [
      {'title': 'Budget FY24 Ops', 'status': 'Draft', 'subtitle': 'Cost center OPS', 'date': 'Oct 01'},
      {'title': 'Actuals Sep', 'status': 'Posted', 'subtitle': 'Ops vs Plan', 'date': 'Oct 05'},
    ],
    'projects': [
      {'title': 'Project Orion', 'status': 'On track', 'subtitle': 'Tasks: 18 open', 'date': 'Oct 05'},
      {'title': 'Change Request CR-5', 'status': 'Pending', 'subtitle': 'Scope update', 'date': 'Oct 04'},
    ],
    'communication': [
      {'title': 'Announcement - Shift change', 'status': 'Live', 'subtitle': 'Posted by HR', 'date': 'Today'},
      {'title': 'Workflow - Access request', 'status': 'Pending', 'subtitle': 'Owner: IT', 'date': 'Today'},
    ],
    'platform': [
      {'title': 'Tenant - Factory A', 'status': 'Active', 'subtitle': 'Modules: POS, ERP', 'date': 'Today'},
      {'title': 'Module toggle', 'status': 'Pending', 'subtitle': 'Enable WMS', 'date': 'Today'},
    ],
  };

  static List<Map<String, dynamic>> fallback(String moduleId) {
    return _records[moduleId] ?? [];
  }
}
