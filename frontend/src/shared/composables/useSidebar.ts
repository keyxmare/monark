import { ref } from 'vue';

import { useLocalStorage } from '@/shared/composables/useLocalStorage';
import { STORAGE_KEYS } from '@/shared/constants';

const collapsed = useLocalStorage(STORAGE_KEYS.SIDEBAR_COLLAPSED, false);
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
