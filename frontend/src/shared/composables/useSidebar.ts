import { ref } from 'vue';

const collapsed = ref(false);
const mobileOpen = ref(false);

export function useSidebar() {
  function toggle() {
    collapsed.value = !collapsed.value;
  }

  function toggleMobile() {
    mobileOpen.value = !mobileOpen.value;
  }

  function closeMobile() {
    mobileOpen.value = false;
  }

  return {
    closeMobile,
    collapsed,
    mobileOpen,
    toggle,
    toggleMobile,
  };
}
