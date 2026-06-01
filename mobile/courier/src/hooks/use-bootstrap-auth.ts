import { useEffect } from "react";
import { useAuth } from "@/store/auth";

let bootstrapped = false;

export function useBootstrapAuth() {
  const bootstrap = useAuth((state) => state.bootstrap);

  useEffect(() => {
    if (bootstrapped) return;
    bootstrapped = true;
    void bootstrap();
  }, [bootstrap]);
}
