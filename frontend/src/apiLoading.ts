let activeRequests = 0;

function emitLoadingState(): void {
  window.dispatchEvent(new CustomEvent('api-loading-change', {
    detail: {
      isLoading: activeRequests > 0,
      activeRequests,
    },
  }));
}

export function installApiLoadingIndicator(): void {
  const nativeFetch = window.fetch.bind(window);

  window.fetch = async (...args): Promise<Response> => {
    activeRequests += 1;
    emitLoadingState();

    try {
      return await nativeFetch(...args);
    } finally {
      activeRequests = Math.max(0, activeRequests - 1);
      emitLoadingState();
    }
  };
}
