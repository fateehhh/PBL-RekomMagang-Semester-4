<?php

namespace App\Services;

use App\Models\KeahlianLowongan;
use App\Models\LowonganMagang;
use App\Models\ProfilMahasiswa;
use Illuminate\Support\Facades\Log;

// TOPSIS (Technique for Order Preference by Similarity to Ideal Solution) 
class SPKService
{
    public static function getRecommendations($userId)
    {
        $profilMahasiswa = ProfilMahasiswa::where('mahasiswa_id', $userId)
            ->with('user', 'programStudi', 'preferensiMahasiswa', 'pengalamanMahasiswa', 'keahlianMahasiswa')
            ->first();

        $lowonganMagang = LowonganMagang::with(['lokasi', 'persyaratanMagang', 'keahlianLowongan'])->get();

        $dataMahasiswa = (object) [
            'ipk' => $profilMahasiswa->ipk,
            'keahlian' => $profilMahasiswa->keahlianMahasiswa->map(function ($keahlian) {
                return (object) [
                    'keahlian_id' => $keahlian->keahlian_id,
                    'tingkat_kemampuan' => $keahlian->tingkatKemampuanIndex(),
                ];
            })->toArray(),
            'preferensi' => (object) [
                'posisi_preferensi' => explode(', ', $profilMahasiswa->preferensiMahasiswa->posisi_preferensi),
                'tipe_kerja_preferensi' => explode(', ', $profilMahasiswa->preferensiMahasiswa->tipe_kerja_preferensi),
                'lokasi' => $profilMahasiswa->preferensiMahasiswa->lokasi,
            ],
            'pengalaman' => $profilMahasiswa->pengalamanMahasiswa->map(function ($pengalaman) {
                return (object) [
                    'tipe_pengalaman' => $pengalaman->tipe_pengalaman,
                    'keahlian' => $pengalaman->pengalamanTag->map(function ($tag) {
                        return (object) [
                            'keahlian_id' => $tag->keahlian->keahlian_id,
                            'tingkat_kemampuan' => 10, // biar score bisa bernilai 1
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];

        $kriteriaMagang = [];
        foreach ($lowonganMagang as $lowongan) {
            $kriteriaMagang[] = (object) [
                'id' => $lowongan->id,
                'min_ipk' => $lowongan->persyaratanMagang->minimum_ipk,
                'keahlian' => $lowongan->keahlianLowongan->map(function ($keahlianLowongan) {
                    return (object) [
                        'keahlian_id' => $keahlianLowongan->keahlian_id,
                        'kemampuan_minimum' => $keahlianLowongan->kemampuanMinimumIndex(),
                    ];
                })->toArray(),
                'posisi' => $lowongan->judul_posisi,
                'remote' => $lowongan->opsi_remote,
                'lokasi' => $lowongan->lokasi,
                'pengalaman' => $lowongan->persyaratanMagang->pengalaman,
                'lowongan' => $lowongan // Keep original 
            ];
        }

        dump($dataMahasiswa, $kriteriaMagang);

        return self::calculateTopsisRanking($dataMahasiswa, $kriteriaMagang);
    }

    private static function calculateTopsisRanking($mahasiswa, $jobs)
    {
        $costAttributes = [3];

        $decisionMatrix = self::createDecisionMatrix($mahasiswa, $jobs);
        $normalizedMatrix = self::normalizeMatrix($decisionMatrix);
        $weightedMatrix = self::applyWeights($normalizedMatrix);
        $idealSolution = self::getIdealSolution($weightedMatrix, $costAttributes);
        $antiIdealSolution = self::getAntiIdealSolution($weightedMatrix, $costAttributes);

        $results = [];
        foreach ($normalizedMatrix as $index => $values) {
            $sPlus = self::calculateDistance($values, $idealSolution);
            $sMinus = self::calculateDistance($values, $antiIdealSolution);

            $results[] = [
                'lowongan' => $jobs[$index]->lowongan,
                'score' => $sMinus / ($sPlus + $sMinus)
            ];
        }

        // descending
        usort($results, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $results;
    }

    private static function createDecisionMatrix($mahasiswa, $jobs)
    {
        $matrix = [];

        foreach ($jobs as $job) {
            $matrix[] = [
                self::calculateIpkMatch($mahasiswa->ipk, $job->min_ipk),
                self::calculateSkillMatch($mahasiswa->keahlian, $job->keahlian),
                self::calculateExperienceMatch($mahasiswa->pengalaman, $job->pengalaman, $job->keahlian),
                self::calculateLocation($mahasiswa->preferensi->lokasi, $job->lokasi),
                self::calculatePositionMatch($mahasiswa->preferensi->posisi_preferensi, $job->posisi),               
            ];
        }

        $highestLocation = 0;
        foreach ($matrix as $values) {
            $highestLocation = max($highestLocation, $values[3]);
        }

        foreach ($matrix as $index => $values) {
            $matrix[$index][3] = $values[3] / $highestLocation;
        }

        return $matrix;
    }

    private static function applyWeights($matrix)
    {
        $weights = [
            0.20,  // IPK (benefit)
            0.25,  // Keahlian/Skills (benefit)
            0.20,  // Pengalaman/Experience (benefit)
            0.10,  // Jarak (cost)
            0.25,  // Posisi/Position (benefit)
        ];

        $weighted = [];
        foreach ($matrix as $row) {
            $weightedRow = [];
            foreach ($row as $i => $val) {
                $weightedRow[] = $val * $weights[$i];
            }
            $weighted[] = $weightedRow;
        }

        return $weighted;
    }

    private static function calculateIpkMatch($mahasiswaIpk, $jobMinIpk)
    {
        return (float) $mahasiswaIpk >= (float) $jobMinIpk ? 1 : 0;
    }

    private static function calculateSkillMatch($mahasiswaSkills, $requiredSkills)
    {
        $matched = 0;
        foreach ($requiredSkills as $required) {
            foreach ($mahasiswaSkills as $mahasiswaSkill) {
                if (
                    $mahasiswaSkill->keahlian_id == $required->keahlian_id &&
                    $mahasiswaSkill->tingkat_kemampuan >= $required->kemampuan_minimum
                ) {
                    $matched++;
                    break;
                }
            }
        }

        return count($requiredSkills) > 0 ? $matched / count($requiredSkills) : 0;
    }

    private static function calculateExperienceMatch($mahasiswaExperience, $jobRequiredExperience, $requiredSkills)
    {
        if (empty($mahasiswaExperience)) return 0;
        $score = $jobRequiredExperience ? 0.5 : 0;
        $tagMatch = 0;
        foreach ($mahasiswaExperience as $experience) {
            $tagMatch += self::calculateSkillMatch($experience->keahlian, $requiredSkills);
        }
        $tagMatch /= count($mahasiswaExperience);
        $score += $tagMatch * 0.5;
        return $score;
    }

    private static function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        if (!is_numeric($lat1) || !is_numeric($lon1) || !is_numeric($lat2) || !is_numeric($lon2)) {
            return 0;
        }

        $R = 6371; // Earth's radius in kilometers
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos($lat1) * cos($lat2) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $d = $R * $c;
        return $d;
    }

    private static function calculateLocation($mahasiswaLocationId, $jobLocationId)
    {
        return self::haversineDistance(
            $mahasiswaLocationId->latitude,
            $mahasiswaLocationId->longitude,
            $jobLocationId->latitude,
            $jobLocationId->longitude
        );
    }

    private static function calculatePositionMatch($mahasiswaPreferredPositions, $jobPosition)
    {
        $highestPercent = 0;
        foreach ($mahasiswaPreferredPositions as $position) {
            similar_text(strtolower($jobPosition), strtolower($position), $percent);
            if ($percent > $highestPercent) {
                $highestPercent = $percent;
            }
        }

        return $highestPercent / 100;
    }

    private static function normalizeMatrix($matrix)
    {
        if (empty($matrix)) return [];

        $columns = count($matrix[0]);
        $sumSquares = array_fill(0, $columns, 0);

        foreach ($matrix as $row) {
            foreach ($row as $i => $val) {
                $sumSquares[$i] += $val ** 2;
            }
        }

        return array_map(function ($row) use ($sumSquares) {
            return array_map(function ($val, $index) use ($sumSquares) {
                return $sumSquares[$index] ? $val / sqrt($sumSquares[$index]) : 0;
            }, $row, array_keys($row));
        }, $matrix);
    }

    private static function getIdealSolution($matrix, $costIndex)
    {
        $ideal = [];
        $columns = count($matrix[0]);
        for ($i = 0; $i < $columns; $i++) {
            $column = array_column($matrix, $i);
            $ideal[] = in_array($i, $costIndex) ? min($column) : max($column);
        }
        return $ideal;
    }

    private static function getAntiIdealSolution($matrix,  $costIndex)
    {
        $antiIdeal = [];
        $columns = count($matrix[0]);
        for ($i = 0; $i < $columns; $i++) {
            $column = array_column($matrix, $i);
            $antiIdeal[] = in_array($i, $costIndex) ? max($column) : min($column);
        }

        return $antiIdeal;
    }

    private static function calculateDistance($point, $solution)
    {
        $sum = 0;
        foreach ($point as $i => $val) {
            $sum += ($val - $solution[$i]) ** 2;
        }
        return sqrt($sum);
    }
}
