plugins {
    id("com.android.application")
    id("kotlin-android")
    // The Flutter Gradle Plugin must be applied after the Android and Kotlin Gradle plugins.
    id("dev.flutter.flutter-gradle-plugin")
}

android {
    namespace = "com.example.restaurant_suite"
    compileSdk = flutter.compileSdkVersion
    ndkVersion = "27.0.12077973"

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_11
        targetCompatibility = JavaVersion.VERSION_11
    }

    kotlinOptions {
        jvmTarget = JavaVersion.VERSION_11.toString()
    }

    defaultConfig {
        applicationId = "com.janova.serve"
        // You can update the following values to match your application needs.
        // For more information, see: https://flutter.dev/to/review-gradle-config.
        minSdk = flutter.minSdkVersion
        targetSdk = flutter.targetSdkVersion
        versionCode = flutter.versionCode
        versionName = flutter.versionName
        resValue("string", "app_name", "Janova Serve")
    }

    flavorDimensions += "role"

    productFlavors {
        create("suite") {
            dimension = "role"
            applicationIdSuffix = ".suite"
            resValue("string", "app_name", "Janova Serve")
        }
        create("cashier") {
            dimension = "role"
            applicationIdSuffix = ".cashier"
            resValue("string", "app_name", "Janova Cashier")
        }
        create("waiter") {
            dimension = "role"
            applicationIdSuffix = ".waiter"
            resValue("string", "app_name", "Janova Waiter")
        }
        create("kitchen") {
            dimension = "role"
            applicationIdSuffix = ".kitchen"
            resValue("string", "app_name", "Janova Kitchen")
        }
        create("owner") {
            dimension = "role"
            applicationIdSuffix = ".owner"
            resValue("string", "app_name", "Janova Owner")
        }
        create("customer") {
            dimension = "role"
            applicationIdSuffix = ".customer"
            resValue("string", "app_name", "Janova Customer")
        }
    }

    buildTypes {
        release {
            // TODO: Add your own signing config for the release build.
            // Signing with the debug keys for now, so `flutter run --release` works.
            signingConfig = signingConfigs.getByName("debug")
        }
    }
}

flutter {
    source = "../.."
}
