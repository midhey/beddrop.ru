import type { ExpoConfig } from "expo/config";

const config: ExpoConfig = {
  name: "BedDrop Courier",
  slug: "beddrop-courier",
  scheme: "beddropcourier",
  version: "1.0.0",
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
    apiBase: process.env.EXPO_PUBLIC_API_BASE ?? "http://10.0.2.2:8080/api/v1",
  },
};

export default config;
