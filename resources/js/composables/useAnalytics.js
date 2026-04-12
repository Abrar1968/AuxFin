import { useAnalyticsStore } from '../stores/analytics.store';

export function useAnalytics() {
    const store = useAnalyticsStore();
    return {
        ...store,
    };
}
