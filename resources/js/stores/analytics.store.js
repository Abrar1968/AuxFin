import { ref } from 'vue';
import { defineStore } from 'pinia';
import { AnalyticsService } from '../services/analytics.service';

export const useAnalyticsStore = defineStore('analytics', () => {
    const overview = ref(null);
    const cmgr = ref(null);
    const forecast = ref(null);
    const anomalies = ref([]);
    const growth = ref(null);

    async function fetchAll() {
        const [o, c, f, a, g] = await Promise.all([
            AnalyticsService.overview(),
            AnalyticsService.cmgr(),
            AnalyticsService.forecast(),
            AnalyticsService.anomalies(),
            AnalyticsService.growth(),
        ]);

        overview.value = o.data;
        cmgr.value = c.data;
        forecast.value = f.data;
        anomalies.value = a.data;
        growth.value = g.data;
    }

    return { overview, cmgr, forecast, anomalies, growth, fetchAll };
});
