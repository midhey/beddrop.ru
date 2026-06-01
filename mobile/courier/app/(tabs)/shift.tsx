import { useWindowDimensions, View } from "react-native";
import { ShiftLiveMap } from "@/components/shift-live-map";
import { ShiftStatusSheet } from "@/components/shift-status-sheet";
import { useCourierWorkday } from "@/hooks/use-courier-workday";
import { colors } from "@/theme/tokens";

export default function ShiftScreen() {
  const { height } = useWindowDimensions();
  const workday = useCourierWorkday();

  return (
    <View style={{ flex: 1, backgroundColor: colors.mapDark }}>
      <ShiftLiveMap
        active={workday.open}
        location={workday.location.last}
        status={workday.location.status}
        height={height}
        order={workday.currentOrder}
        mode={workday.mode}
        routeSegments={workday.routeSegments}
      />
      <ShiftStatusSheet
        open={workday.open}
        profile={workday.profile}
        profileError={workday.profileError}
        openedAt={workday.openedAt}
        mode={workday.mode}
        availableOrders={workday.availableOrders}
        activeOrder={workday.activeOrder}
        selectedAvailableOrder={workday.selectedAvailableOrder}
        currentOrder={workday.currentOrder}
        routeSegments={workday.routeSegments}
        earnings={workday.earnings}
        busy={workday.actionBusy || workday.loading}
        onSelectOrder={workday.selectOrder}
        onClearSelection={workday.clearSelectedOrder}
        onAssign={workday.assignOrder}
        onPickedUp={workday.markPickedUp}
        onDelivered={workday.markDelivered}
        onToggleShift={workday.toggleShift}
        onLogout={workday.logout}
      />
    </View>
  );
}
