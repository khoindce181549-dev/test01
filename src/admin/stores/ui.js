import { defineStore } from 'pinia';

/**
 * Global admin UI state: sidebar collapse, the currently-open modal key,
 * and a shared loading flag for page-level busy states.
 *
 * Keeping these in one store avoids prop-drilling layout state through
 * every view and gives later batches a single reactive source to hook into.
 */
export const useUiStore = defineStore('ui', {
  state: () => ({
    sidebarOpen: true,
    activeModal: null, // string key or null
    loading: false, // generic busy flag
  }),
  actions: {
    toggleSidebar() {
      this.sidebarOpen = !this.sidebarOpen;
    },
    openModal(key) {
      this.activeModal = key;
    },
    closeModal() {
      this.activeModal = null;
    },
  },
});
