import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { useMercure } from '@/shared/composables/useMercure'

class MockEventSource {
  static instances: MockEventSource[] = []
  url: string
  onopen: ((event: Event) => void) | null = null
  onmessage: ((event: MessageEvent) => void) | null = null
  onerror: ((event: Event) => void) | null = null
  closed = false

  constructor(url: string) {
    this.url = url
    MockEventSource.instances.push(this)
  }

  close() {
    this.closed = true
  }
}

describe('useMercure', () => {
  beforeEach(() => {
    MockEventSource.instances = []
    vi.stubGlobal('EventSource', MockEventSource)
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.unstubAllGlobals()
  })

  it('creates an EventSource with the topic', () => {
    useMercure('/test-topic')

    expect(MockEventSource.instances).toHaveLength(1)
    expect(MockEventSource.instances[0].url).toContain('topic=%2Ftest-topic')
  })

  it('supports multiple topics', () => {
    useMercure(['/topic-a', '/topic-b'])

    expect(MockEventSource.instances[0].url).toContain('topic=%2Ftopic-a')
    expect(MockEventSource.instances[0].url).toContain('topic=%2Ftopic-b')
  })

  it('sets connected to true on open', () => {
    const { connected } = useMercure('/test')

    expect(connected.value).toBe(false)
    MockEventSource.instances[0].onopen?.(new Event('open'))
    expect(connected.value).toBe(true)
  })

  it('parses message data and calls onMessage', () => {
    const onMessage = vi.fn()
    const { data } = useMercure<{ id: string }>('/test', { onMessage })

    const event = new MessageEvent('message', { data: JSON.stringify({ id: '123' }) })
    MockEventSource.instances[0].onmessage?.(event)

    expect(data.value).toEqual({ id: '123' })
    expect(onMessage).toHaveBeenCalledWith({ id: '123' })
  })

  it('disconnects on error and schedules reconnect', () => {
    const onError = vi.fn()
    const { connected } = useMercure('/test', { onError })

    MockEventSource.instances[0].onopen?.(new Event('open'))
    expect(connected.value).toBe(true)

    MockEventSource.instances[0].onerror?.(new Event('error'))
    expect(connected.value).toBe(false)
    expect(onError).toHaveBeenCalled()
    expect(MockEventSource.instances[0].closed).toBe(true)

    vi.advanceTimersByTime(3000)
    expect(MockEventSource.instances).toHaveLength(2)
  })

  it('closes EventSource and clears reconnect on close()', () => {
    const { close, connected } = useMercure('/test')

    MockEventSource.instances[0].onopen?.(new Event('open'))
    MockEventSource.instances[0].onerror?.(new Event('error'))

    close()
    expect(connected.value).toBe(false)

    vi.advanceTimersByTime(5000)
    expect(MockEventSource.instances).toHaveLength(1)
  })

  it('close is safe when no active connection', () => {
    const { close } = useMercure('/test')
    MockEventSource.instances[0].onopen?.(new Event('open'))

    close()
    close()
    expect(MockEventSource.instances[0].closed).toBe(true)
  })
})
