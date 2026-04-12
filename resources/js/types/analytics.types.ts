export interface KpiMetric {
    label: string;
    value: number;
    delta_percent: number;
}

export interface GrowthVelocity {
    month: string;
    growth_rate_percent: number;
}

export interface AnomalySignal {
    id: string;
    category: string;
    severity: 'low' | 'medium' | 'high' | 'critical';
    message: string;
    detected_at: string;
}

export interface AnalyticsOverviewPayload {
    kpis: KpiMetric[];
    growth_velocity: GrowthVelocity[];
    anomalies: AnomalySignal[];
}
