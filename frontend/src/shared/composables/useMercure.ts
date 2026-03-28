import { onUnmounted, ref, type Ref } from 'vue';

const MERCURE_HUB_URL = '/.well-known/mercure';

interface UseMercureOptions<T> {
  onMessage?: (data: T) => void;
  onError?: (event: Event) => void;
}

interface UseMercureReturn<T> {
  data: Ref<T | null>;
  connected: Ref<boolean>;
  close: () => void;
}

export function useMercure<T = unknown>(
  topic: string | string[],
  options: UseMercureOptions<T> = {},
): UseMercureReturn<T> {
  const data = ref<T | null>(null) as Ref<T | null>;
  const connected = ref(false);
  let eventSource: EventSource | null = null;
  let reconnectTimeout: ReturnType<typeof setTimeout> | null = null;

  function connect() {
    const url = new URL(MERCURE_HUB_URL, window.location.origin);
    const topics = Array.isArray(topic) ? topic : [topic];
    for (const t of topics) {
      url.searchParams.append('topic', t);
    }

    eventSource = new EventSource(url.toString());

    eventSource.onopen = () => {
      connected.value = true;
    };

    eventSource.onmessage = (event: MessageEvent) => {
      const parsed = JSON.parse(event.data) as T;
      data.value = parsed;
      options.onMessage?.(parsed);
    };

    eventSource.onerror = (event: Event) => {
      connected.value = false;
      options.onError?.(event);
      eventSource?.close();
      eventSource = null;
      reconnectTimeout = setTimeout(connect, 3000);
    };
  }

  function close() {
    if (reconnectTimeout) {
      clearTimeout(reconnectTimeout);
      reconnectTimeout = null;
    }
    if (eventSource) {
      eventSource.close();
      eventSource = null;
    }
    connected.value = false;
  }

  connect();

  onUnmounted(close);

  return { data, connected, close };
}
