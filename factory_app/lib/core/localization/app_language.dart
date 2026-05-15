import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

enum AppLanguage {
  en,
  ar,
}

extension AppLanguageX on AppLanguage {
  Locale get locale {
    switch (this) {
      case AppLanguage.en:
        return const Locale('en');
      case AppLanguage.ar:
        return const Locale('ar');
    }
  }

  TextDirection get textDirection {
    switch (this) {
      case AppLanguage.en:
        return TextDirection.ltr;
      case AppLanguage.ar:
        return TextDirection.rtl;
    }
  }

  String get code => locale.languageCode;
}

final appLanguageProvider = StateProvider<AppLanguage>((ref) => AppLanguage.en);

final appStringsProvider = Provider<AppStrings>((ref) {
  return AppStrings(ref.watch(appLanguageProvider));
});

class AppStrings {
  const AppStrings(this.language);

  final AppLanguage language;

  bool get isArabic => language == AppLanguage.ar;

  String t(String key, {Map<String, String>? params}) {
    var value =
        _localized[key]?[language] ?? _localized[key]?[AppLanguage.en] ?? key;
    params?.forEach((name, replacement) {
      value = value.replaceAll('{$name}', replacement);
    });
    return value;
  }

  String roleLabel(String role) => t('role.$role');

  static const Map<String, Map<AppLanguage, String>> _localized = {
    'language.english': {
      AppLanguage.en: 'English',
      AppLanguage.ar: 'الإنجليزية',
    },
    'language.arabic': {
      AppLanguage.en: 'Arabic',
      AppLanguage.ar: 'العربية',
    },
    'language.switch': {
      AppLanguage.en: 'Switch language',
      AppLanguage.ar: 'تغيير اللغة',
    },
    'login.preparing': {
      AppLanguage.en: 'Preparing restaurant suite...',
      AppLanguage.ar: 'جاري تجهيز نظام المطعم...',
    },
    'login.customerTitle': {
      AppLanguage.en: 'Customer app access',
      AppLanguage.ar: 'دخول تطبيق العملاء',
    },
    'login.staffTitle': {
      AppLanguage.en: 'Staff suite sign in',
      AppLanguage.ar: 'تسجيل دخول فريق العمل',
    },
    'login.customerSubtitle': {
      AppLanguage.en:
          'View previous orders, loyalty points, and registered restaurants.',
      AppLanguage.ar: 'تابع الطلبات السابقة ونقاط الولاء والمطاعم المسجلة.',
    },
    'login.staffSubtitle': {
      AppLanguage.en:
          'Choose the exact staff workspace you want to run: waiter, cashier, kitchen, or owner.',
      AppLanguage.ar:
          'اختر مساحة العمل المطلوبة: النادل أو الكاشير أو المطبخ أو المالك.',
    },
    'login.pinnedSubtitle': {
      AppLanguage.en:
          'This app is pinned to the selected workspace for a cleaner branch rollout.',
      AppLanguage.ar:
          'هذا التطبيق مثبت على مساحة عمل محددة لتشغيل الفرع بسهولة.',
    },
    'login.email': {
      AppLanguage.en: 'Email',
      AppLanguage.ar: 'البريد الإلكتروني',
    },
    'login.password': {
      AppLanguage.en: 'Password',
      AppLanguage.ar: 'كلمة المرور',
    },
    'login.name': {
      AppLanguage.en: 'Name',
      AppLanguage.ar: 'الاسم',
    },
    'login.guestName': {
      AppLanguage.en: 'Guest name',
      AppLanguage.ar: 'اسم الضيف',
    },
    'login.phone': {
      AppLanguage.en: 'Phone',
      AppLanguage.ar: 'رقم الهاتف',
    },
    'login.optionalEmail': {
      AppLanguage.en: 'Email (optional)',
      AppLanguage.ar: 'البريد الإلكتروني (اختياري)',
    },
    'login.workspace': {
      AppLanguage.en: 'Workspace',
      AppLanguage.ar: 'مساحة العمل',
    },
    'login.pinnedWorkspace': {
      AppLanguage.en: 'Pinned workspace',
      AppLanguage.ar: 'مساحة العمل المثبتة',
    },
    'login.signingIn': {
      AppLanguage.en: 'Signing in...',
      AppLanguage.ar: 'جاري تسجيل الدخول...',
    },
    'login.continue': {
      AppLanguage.en: 'Continue',
      AppLanguage.ar: 'متابعة',
    },
    'login.requestCode': {
      AppLanguage.en: 'Send verification code',
      AppLanguage.ar: 'إرسال رمز التحقق',
    },
    'login.verifyCode': {
      AppLanguage.en: 'Verify and continue',
      AppLanguage.ar: 'تحقق وتابع',
    },
    'login.otpCode': {
      AppLanguage.en: 'Verification code',
      AppLanguage.ar: 'رمز التحقق',
    },
    'login.otpSent': {
      AppLanguage.en: 'Enter the code sent to {destination}.',
      AppLanguage.ar: 'أدخل الرمز المرسل إلى {destination}.',
    },
    'login.changePhone': {
      AppLanguage.en: 'Change phone',
      AppLanguage.ar: 'تغيير رقم الهاتف',
    },
    'login.devOtp': {
      AppLanguage.en: 'Development code: {code}',
      AppLanguage.ar: 'رمز التطوير: {code}',
    },
    'login.customerNote': {
      AppLanguage.en:
          'Customer access is protected with OTP before a session token is issued.',
      AppLanguage.ar: 'يتم حماية دخول العميل برمز تحقق قبل إصدار جلسة الدخول.',
    },
    'login.staffNote': {
      AppLanguage.en:
          'The selected role is enforced against the backend type list, so the staff suite only opens against the correct permission set.',
      AppLanguage.ar:
          'يتم التحقق من الدور عبر الخادم حتى لا تفتح مساحة العمل إلا بالصلاحيات الصحيحة.',
    },
    'login.lockedNote': {
      AppLanguage.en:
          'This role-specific app stays locked to one operational role for branch devices and staff training.',
      AppLanguage.ar:
          'يبقى هذا التطبيق مثبتا على دور تشغيلي واحد لأجهزة الفروع وتدريب الفريق.',
    },
    'hero.brand': {
      AppLanguage.en: 'Restaurant Suite',
      AppLanguage.ar: 'نظام المطاعم',
    },
    'hero.title': {
      AppLanguage.en:
          'One platform for guest loyalty, table service, kitchen flow, payments, and ownership control.',
      AppLanguage.ar:
          'منصة واحدة للولاء وخدمة الطاولات والمطبخ والمدفوعات وتحكم المالك.',
    },
    'hero.subtitle': {
      AppLanguage.en:
          'Designed for Egyptian restaurants and cafes that need a cleaner operator UX than the current stack and more control than off-the-shelf POS bundles.',
      AppLanguage.ar:
          'مصمم للمطاعم والكافيهات في مصر التي تحتاج تجربة تشغيل أوضح وتحكما أعلى من أنظمة نقاط البيع الجاهزة.',
    },
    'hero.customerLoyalty': {
      AppLanguage.en: 'Customer loyalty',
      AppLanguage.ar: 'ولاء العملاء',
    },
    'hero.waiterFlow': {
      AppLanguage.en: 'Waiter order flow',
      AppLanguage.ar: 'طلبات النادل',
    },
    'hero.kitchenBoard': {
      AppLanguage.en: 'Kitchen board',
      AppLanguage.ar: 'لوحة المطبخ',
    },
    'hero.splitPayments': {
      AppLanguage.en: 'Split payments',
      AppLanguage.ar: 'تقسيم الدفع',
    },
    'hero.ownerAnalytics': {
      AppLanguage.en: 'Owner analytics',
      AppLanguage.ar: 'تحليلات المالك',
    },
    'role.customer': {
      AppLanguage.en: 'Customer',
      AppLanguage.ar: 'عميل',
    },
    'role.waiter': {
      AppLanguage.en: 'Waiter',
      AppLanguage.ar: 'نادل',
    },
    'role.cashier': {
      AppLanguage.en: 'Cashier',
      AppLanguage.ar: 'كاشير',
    },
    'role.kitchen': {
      AppLanguage.en: 'Kitchen',
      AppLanguage.ar: 'مطبخ',
    },
    'role.owner': {
      AppLanguage.en: 'Owner',
      AppLanguage.ar: 'مالك',
    },
    'app.customer': {
      AppLanguage.en: 'Customer App',
      AppLanguage.ar: 'تطبيق العملاء',
    },
    'app.waiter': {
      AppLanguage.en: 'Waiter App',
      AppLanguage.ar: 'تطبيق النادل',
    },
    'app.cashier': {
      AppLanguage.en: 'Cashier App',
      AppLanguage.ar: 'تطبيق الكاشير',
    },
    'app.kitchen': {
      AppLanguage.en: 'Kitchen App',
      AppLanguage.ar: 'تطبيق المطبخ',
    },
    'app.owner': {
      AppLanguage.en: 'Owner Control Center',
      AppLanguage.ar: 'مركز تحكم المالك',
    },
    'action.switchRole': {
      AppLanguage.en: 'Switch role',
      AppLanguage.ar: 'تغيير الدور',
    },
    'action.logout': {
      AppLanguage.en: 'Logout',
      AppLanguage.ar: 'تسجيل الخروج',
    },
    'customer.deliveringTo': {
      AppLanguage.en: 'Delivering the restaurant suite memory to',
      AppLanguage.ar: 'نجهز تجربة المطعم باسم',
    },
    'customer.guest': {
      AppLanguage.en: 'Guest',
      AppLanguage.ar: 'ضيف',
    },
    'customer.noNotifications': {
      AppLanguage.en: 'No new customer notifications right now.',
      AppLanguage.ar: 'لا توجد إشعارات جديدة حاليا.',
    },
    'customer.account': {
      AppLanguage.en: 'Account',
      AppLanguage.ar: 'الحساب',
    },
    'customer.searchHome': {
      AppLanguage.en: 'Search for restaurant, dish or branch',
      AppLanguage.ar: 'ابحث عن مطعم أو طبق أو فرع',
    },
    'customer.searchBrowse': {
      AppLanguage.en: 'Search restaurants, cafes or dishes',
      AppLanguage.ar: 'ابحث في المطاعم والكافيهات والأطباق',
    },
    'nav.home': {
      AppLanguage.en: 'Home',
      AppLanguage.ar: 'الرئيسية',
    },
    'nav.browse': {
      AppLanguage.en: 'Browse',
      AppLanguage.ar: 'تصفح',
    },
    'nav.orders': {
      AppLanguage.en: 'Orders',
      AppLanguage.ar: 'الطلبات',
    },
    'nav.rewards': {
      AppLanguage.en: 'Rewards',
      AppLanguage.ar: 'المكافآت',
    },
    'quick.food': {
      AppLanguage.en: 'Food',
      AppLanguage.ar: 'أكل',
    },
    'quick.cafe': {
      AppLanguage.en: 'Cafe',
      AppLanguage.ar: 'كافيه',
    },
    'quick.desserts': {
      AppLanguage.en: 'Desserts',
      AppLanguage.ar: 'حلويات',
    },
    'quick.breakfast': {
      AppLanguage.en: 'Breakfast',
      AppLanguage.ar: 'فطار',
    },
  };
}
