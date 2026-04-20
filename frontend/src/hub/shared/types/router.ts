import 'vue-router';

export type LayoutName = 'auth' | 'dashboard';

declare module 'vue-router' {
  interface RouteMeta {
    layout?: LayoutName;
    public?: boolean;
  }
}
