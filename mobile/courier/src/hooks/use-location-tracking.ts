import { useEffect, useRef, useState } from "react";
import { AppState } from "react-native";
import * as Location from "expo-location";
import { courierApi } from "@/api/courier";

export type LocationStatus = "idle" | "tracking" | "denied" | "error";

export function useLocationTracking(active: boolean) {
  const [status, setStatus] = useState<LocationStatus>("idle");
  const [last, setLast] = useState<Location.LocationObject | null>(null);
  const sub = useRef<Location.LocationSubscription | null>(null);

  useEffect(() => {
    let cancelled = false;

    const stop = () => {
      sub.current?.remove();
      sub.current = null;
      setStatus((current) => (current === "denied" ? current : "idle"));
    };

    const start = async () => {
      if (sub.current) return;

      const permission = await Location.requestForegroundPermissionsAsync();
      if (permission.status !== "granted") {
        setStatus("denied");
        return;
      }

      const current = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.High });
      if (!cancelled) {
        setLast(current);
        setStatus("tracking");
        void courierApi.location({
          lat: current.coords.latitude,
          lng: current.coords.longitude,
          accuracy: current.coords.accuracy,
          heading: current.coords.heading,
          speed: current.coords.speed,
          recorded_at: new Date(current.timestamp).toISOString(),
        });
      }

      sub.current = await Location.watchPositionAsync(
        { accuracy: Location.Accuracy.High, timeInterval: 15000, distanceInterval: 25 },
        (position) => {
          if (cancelled) return;
          setLast(position);
          setStatus("tracking");
          void courierApi.location({
            lat: position.coords.latitude,
            lng: position.coords.longitude,
            accuracy: position.coords.accuracy,
            heading: position.coords.heading,
            speed: position.coords.speed,
            recorded_at: new Date(position.timestamp).toISOString(),
          });
        },
      );
    };

    if (active) {
      void start().catch(() => setStatus("error"));
    } else {
      stop();
    }

    const appSub = AppState.addEventListener("change", (state) => {
      if (state !== "active") stop();
      if (state === "active" && active) void start().catch(() => setStatus("error"));
    });

    return () => {
      cancelled = true;
      stop();
      appSub.remove();
    };
  }, [active]);

  return { status, last };
}
