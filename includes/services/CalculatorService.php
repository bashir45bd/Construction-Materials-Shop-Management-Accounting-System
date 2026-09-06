<?php
/**
 * Civil Engineering & Construction Material Calculator Service
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

namespace App\Services;

class CalculatorService
{
    /**
     * Standard Rod (TMT Rebar) Weight by Diameter and Length
     * Formula: Weight (KG) = (Diameter_in_mm^2 / 162.2) * Length_in_Meters
     * Or: (Diameter_in_mm^2 / 533) * Length_in_Feet
     */
    public static function calculateRodWeight(float $diameterMm, float $length, string $lengthUnit = 'meter', int $quantity = 1): array
    {
        $lengthInMeters = ($lengthUnit === 'feet') ? ($length * 0.3048) : $length;
        $unitWeightPerMeter = ($diameterMm * $diameterMm) / 162.2;
        $totalWeightKg = $unitWeightPerMeter * $lengthInMeters * $quantity;
        $totalWeightTon = $totalWeightKg / 1000.0;

        return [
            'diameter_mm'          => $diameterMm,
            'length'               => $length,
            'length_unit'          => $lengthUnit,
            'quantity'             => $quantity,
            'unit_weight_kg_meter' => round($unitWeightPerMeter, 4),
            'total_weight_kg'      => round($totalWeightKg, 2),
            'total_weight_ton'     => round($totalWeightTon, 4),
            'total_weight_maund'   => round($totalWeightKg / 40.0, 2)
        ];
    }

    /**
     * Brick Masonry Estimation
     * Standard Brick Size in Bangladesh: 9.5" x 4.5" x 2.75"
     */
    public static function calculateBricks(
        float $wallLengthFt,
        float $wallHeightFt,
        string $wallThickness = '5_inch', // '5_inch' or '10_inch'
        float $mortarRatio = 1.0 // Cement:Sand ratio (e.g. 1:6)
    ): array {
        $wallAreaSqft = $wallLengthFt * $wallHeightFt;

        // 5" wall takes ~5 bricks per sq.ft (500 per 100 sqft)
        // 10" wall takes ~10 bricks per sq.ft (1000 per 100 sqft)
        $bricksPerSqft = ($wallThickness === '10_inch') ? 10.0 : 5.0;
        $totalBricks = ceil($wallAreaSqft * $bricksPerSqft);
        $totalBricksWithWastage = ceil($totalBricks * 1.05); // 5% wastage

        // Mortar volume estimation: Dry volume factor ~ 1.33 for mortar
        $wetMortarCft = ($wallThickness === '10_inch') ? ($wallAreaSqft * 0.22) : ($wallAreaSqft * 0.10);
        $dryMortarCft = $wetMortarCft * 1.33;

        // Assuming 1:6 cement to sand mix for masonry
        $cementBags = round(($dryMortarCft / 7.0) / 1.25, 1); // 1 bag cement = 1.25 cft
        $sandCft = round(($dryMortarCft / 7.0) * 6.0, 1);

        return [
            'wall_area_sqft'   => round($wallAreaSqft, 2),
            'wall_thickness'   => $wallThickness,
            'total_bricks'     => $totalBricks,
            'with_wastage'     => $totalBricksWithWastage,
            'cement_bags'      => max(1, (int)ceil($cementBags)),
            'sand_cft'         => round($sandCft, 2)
        ];
    }

    /**
     * Concrete Mix Ratio & Material Estimator (RCC Casting)
     * Mix Ratios: (Cement : Sand : Stone/Khoa)
     * Common: 1:2:4 (M15), 1:1.5:3 (M20), 1:1:2 (M25)
     */
    public static function calculateConcrete(
        float $lengthFt,
        float $widthFt,
        float $depthInch,
        float $ratioCement = 1.0,
        float $ratioSand = 2.0,
        float $ratioStone = 4.0
    ): array {
        $depthFt = $depthInch / 12.0;
        $wetVolumeCft = $lengthFt * $widthFt * $depthFt;
        
        // Dry Volume Conversion factor = 1.54
        $dryVolumeCft = $wetVolumeCft * 1.54;
        $sumOfRatios = $ratioCement + $ratioSand + $ratioStone;

        // 1 Bag Cement = 1.25 CFT (50 KG)
        $cementCft = ($dryVolumeCft * $ratioCement) / $sumOfRatios;
        $cementBags = ceil($cementCft / 1.25);

        $sandCft = ($dryVolumeCft * $ratioSand) / $sumOfRatios;
        $stoneCft = ($dryVolumeCft * $ratioStone) / $sumOfRatios;

        return [
            'wet_volume_cft' => round($wetVolumeCft, 2),
            'dry_volume_cft' => round($dryVolumeCft, 2),
            'mix_ratio'      => "{$ratioCement}:{$ratioSand}:{$ratioStone}",
            'cement_bags'    => (int)$cementBags,
            'sand_cft'       => round($sandCft, 2),
            'stone_cft'      => round($stoneCft, 2)
        ];
    }
}
