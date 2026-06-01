import { create } from "zustand";

interface CourierWorkdayState {
  selectedOrderId: number | null;
  setSelectedOrderId: (orderId: number | null) => void;
}

export const useCourierWorkdayStore = create<CourierWorkdayState>((set) => ({
  selectedOrderId: null,
  setSelectedOrderId: (selectedOrderId) => set({ selectedOrderId }),
}));
