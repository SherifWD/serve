import 'package:flutter_test/flutter_test.dart';
import 'package:restaurant_suite/core/config/app_flavor.dart';
import 'package:restaurant_suite/core/models/app_models.dart';

void main() {
  test('customer wrapper points to the customer flavor', () {
    expect(AppFlavor.customer.fixedRole, AppRole.customer);
    expect(AppFlavor.customer.title, 'Restaurant Customer App');
  });
}
