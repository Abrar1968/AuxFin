import { ref } from 'vue';
import { defineStore } from 'pinia';
import { AnalyticsService } from '../services/analytics.service';

export const useAnalyticsStore = defineStore('analytics', () => {
    const overview = ref(null);
    const cmgr = ref(null);
    const forecast = ref(null);
    const anomalies = ref([]);
    const arHealth = ref(null);
    const burnRate = ref(null);
    const growth = ref(null);

    async function fetchAll(availableCash = 0) {
        const [o, c, f, a, h, b, g] = await Promise.all([
            AnalyticsService.overview(),
            AnalyticsService.cmgr(),
            AnalyticsService.forecast(),
            AnalyticsService.anomalies(),
            AnalyticsService.arHealth(),
            AnalyticsService.burnRate(availableCash),
            AnalyticsService.growth(),
        ]);

        overview.value = o.data;
        cmgr.value = c.data;
        forecast.value = f.data;
        anomalies.value = a.data;
        arHealth.value = h.data;
        burnRate.value = b.data;
        growth.value = g.data;
    }

    return { overview, cmgr, forecast, anomalies, arHealth, burnRate, growth, fetchAll };
});
