import type { ExpoConfig } from "expo/config";

const devApiBase = "http://10.0.2.2:8080/api/v1";
const isProductionBuild =
  process.env.NODE_ENV === "production" ||
  process.env.APP_ENV === "production" ||
  process.env.EAS_BUILD_PROFILE === "production";
const apiBase = process.env.EXPO_PUBLIC_API_BASE ?? (isProductionBuild ? undefined : devApiBase);
const appVersion = process.env.APP_VERSION ?? "1.0.0";
const androidVersionCode = Number(process.env.ANDROID_VERSION_CODE ?? "1");

if (!apiBase) {
  throw new Error("EXPO_PUBLIC_API_BASE is required for production courier builds.");
}

if (!Number.isInteger(androidVersionCode) || androidVersionCode < 1) {
  throw new Error("ANDROID_VERSION_CODE must be a positive integer.");
}

const config: ExpoConfig = {
  name: "BedDrop Courier",
  slug: "beddrop-courier",
  owner: process.env.EXPO_OWNER,
  scheme: "beddropcourier",
  version: appVersion,
  orientation: "portrait",
  icon: "./assets/icon.png",
  userInterfaceStyle: "light",
  plugins: [
    [
      "expo-location",
      {
        locationWhenInUsePermission:
          "Приложению нужен доступ к геолокации, чтобы отслеживать вашу позицию во время смены.",
      },
    ],
    "expo-router",
  ],
  android: {
    package: "ru.beddrop.courier",
    versionCode: androidVersionCode,
    permissions: ["ACCESS_FINE_LOCATION", "ACCESS_COARSE_LOCATION", "INTERNET"],
    adaptiveIcon: {
      foregroundImage: "./assets/adaptive-icon.png",
      backgroundColor: "#7A3BFF",
    },
  },
  web: {
    favicon: "./assets/favicon.png",
  },
  extra: {
    apiBase,
    eas: process.env.EXPO_PROJECT_ID ? { projectId: process.env.EXPO_PROJECT_ID } : undefined,
  },
};

export default config;
