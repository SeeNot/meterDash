export type PeriodPreset = '30d' | '3m' | '12m' | 'ytd' | 'all' | 'custom';

export type Period = {
    from: string;
    to: string;
    preset: PeriodPreset;
};

export type WattageEntry = {
    id: number;
    measured_at: string;
    reading_kwh: number;
    note: string | null;
};

export type ConsumptionInterval = {
    from: string;
    to: string;
    label: string;
    days: number;
    kwh: number;
    kwhPerDay: number;
};

export type ConsumptionSeries = {
    labels: string[];
    kwh: number[];
    kwhPerDay: number[];
};

export type ConsumptionStats = {
    totalKwh: number;
    averageKwhPerDay: number;
    medianKwhPerDay: number;
    minKwhPerDay: number;
    maxKwhPerDay: number;
    daysCovered: number;
    intervalCount: number;
};

export type PlanCostMonth = {
    period: string;
    label: string;
    kwh: number;
    rateEur: number;
    energyEur: number;
    standingEur: number;
    estimated: boolean;
};

export type PlanCost = {
    planId: number;
    planName: string;
    type: TariffPlanType;
    typeLabel: string;
    totalEur: number;
    energyEur: number;
    standingEur: number;
    effectiveRateEur: number;
    hasEstimatedPrices: boolean;
    monthly: PlanCostMonth[];
};

export type TariffPlanType = 'fixed' | 'dynamic';

export type TariffPlan = {
    id: number;
    name: string;
    type: TariffPlanType;
    typeLabel: string;
    energy_rate_eur: number;
    standing_charge_eur: number;
    is_active: boolean;
};

export type TariffPlanTypeOption = {
    value: TariffPlanType;
    label: string;
    rateDescription: string;
};
