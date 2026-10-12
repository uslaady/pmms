<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Contractor;
use App\Models\FundingSource;
use App\Models\Inspection;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectMilestone;
use App\Models\ProjectUpdate;
use App\Models\Role;
use App\Models\User;
use App\Models\Ward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Dummy data for training and testing. Every project belongs to a directorate,
 * and the data shows every stage of the workflow:
 *
 *   Pending approval (no contractor yet) -> Approved -> contractor assigned -> In progress /
 *   Delayed / Completed, plus locked projects (Suspended / Cancelled / Deactivated).
 *   Contractors: Active, Pending (awaiting the Perm Sec), Suspended and Blacklisted —
 *   the Suspended one still has a running project, which can still be updated.
 *   Payments: Paid, Approved (waiting to be marked paid) and Pending (waiting for the Perm Sec).
 *   Progress updates waiting for approval.
 *
 * Requires RoleAndPermissionSeeder and BornoLocationSeeder to run first.
 * Do NOT run on the live system — it is removed before go-live with migrate:fresh.
 */
class DemoDataSeeder extends Seeder
{
    /** directorate => [code prefix, category, project types, milestone names] */
    private const PLAN = [
        'civil' => ['CE', 'Roads & Bridges',
            ['road rehabilitation', 'bridge construction', 'drainage works', 'culvert installation'],
            ['Site clearance', 'Earthworks', 'Drainage & culverts', 'Base course', 'Asphalt surfacing', 'Road furniture']],
        'public_building' => ['PB', 'Public Buildings',
            ['primary school renovation', 'health clinic construction', 'staff quarters construction', 'market stalls construction'],
            ['Site clearance', 'Foundation', 'Block work', 'Roofing', 'Finishes', 'Handover']],
        'electrical' => ['EE', 'Electrical Works',
            ['street lighting installation', 'solar mini-grid', 'transformer upgrade', 'office electrification'],
            ['Survey & design', 'Poles / trenching', 'Cabling', 'Equipment installation', 'Testing', 'Commissioning']],
        'mechanical' => ['ME', 'Mechanical Works',
            ['borehole scheme', 'water pump station', 'generator installation', 'hospital HVAC installation'],
            ['Survey', 'Drilling / base works', 'Equipment supply', 'Installation', 'Testing', 'Commissioning']],
    ];

    /** Status of the n-th project of each directorate (8 per directorate = 32 projects). */
    private const STATUSES = ['pending', 'planned', 'in_progress', 'in_progress', 'delayed', 'completed', 'suspended', 'cancelled'];

    public function run(): void
    {
        $wards = Ward::with('lga')->get();
        if ($wards->isEmpty()) {
            $this->command?->warn('No wards found — run BornoLocationSeeder first.');
            return;
        }

        $user = fn (string $email) => User::where('email', $email)->firstOrFail();
        $admin = $user('admin@works.gov');
        $ps = $user('ps@works.gov');
        $planning = $user('dpr@works.gov');
        $finance = $user('dfa@works.gov');
        $directorOf = ['civil' => $user('dce@works.gov'), 'public_building' => $user('dpb@works.gov'),
            'electrical' => $user('dee@works.gov'), 'mechanical' => $user('dme@works.gov')];
        $residentOf = ['civil' => $user('prece@works.gov'), 'public_building' => $user('prepb@works.gov'),
            'electrical' => $user('preee@works.gov'), 'mechanical' => $user('preme@works.gov')];

        $funding = collect(['Borno State Govt.', 'Federal Government Grant', 'World Bank Assisted'])
            ->map(fn ($n) => FundingSource::firstOrCreate(['name' => $n]));

        // ---------------- Contractors ----------------
        $contractorSpecs = [
            ['Bedrock Construction Ltd', 'active'], ['Horizon Civil Works Plc', 'active'],
            ['Trident Engineering Ltd', 'active'], ['Savannah Builders Ltd', 'active'],
            ['Crestline Infrastructure Ltd', 'active'], ['Lakeside Power Systems Ltd', 'active'],
            ['Northgate Mechanical Services Ltd', 'pending'],   // awaiting Perm Sec approval
            ['Dunes Contracting Ltd', 'suspended'],             // locked, but its running project continues
            ['Mirage Works Ltd', 'blacklisted'],                // locked
            ['Kanem Allied Works Ltd', 'rejected'],             // rejected by Perm Sec, awaiting correction
        ];
        $contractors = collect();
        foreach ($contractorSpecs as $i => [$name, $status]) {
            $c = Contractor::create([
                'name' => $name,
                'rc_number' => 'RC'.(1200000 + $i * 7311),
                'tax_id' => 'TIN-'.(20000000 + $i * 131),
                'contact_person' => ['Musa Ali', 'Grace Okon', 'Ibrahim Bukar', 'Halima Musa', 'Peter Danjuma', 'Zainab Kolo', 'Usman Goni', 'Fatima Ali', 'Bello Sani', 'Aliyu Kyari'][$i],
                'email' => 'info@'.strtolower(preg_replace('/[^a-z]/i', '', explode(' ', $name)[0])).'.example.com',
                'phone' => '0803'.str_pad((string) (1000000 + $i * 4321), 7, '0', STR_PAD_LEFT),
                'address' => ($i + 3).' Baga Road, Maiduguri, Borno State',
                'director' => ['A. Bello', 'C. Okafor', 'M. Shettima', 'H. Kachalla', 'J. Audu', 'K. Modu', 'Y. Grema', 'S. Mohammed', 'D. Lawan', 'B. Tijani'][$i],
                'performance_score' => 55 + ($i * 5) % 40,
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? 'Tax clearance certificate has expired. Upload the 2026 certificate and resubmit.' : null,
            ]);
            $this->dummyDocuments($c, $planning);
            $contractors->push($c);
        }
        $active = $contractors->where('status', 'active')->values();
        $suspended = $contractors->firstWhere('status', 'suspended');
        $blacklisted = $contractors->firstWhere('status', 'blacklisted');

        // ---------------- Projects ----------------
        $seq = 0;
        foreach (self::PLAN as $directorate => [$prefix, $categoryName, $types, $milestoneNames]) {
            $category = ProjectCategory::firstOrCreate(['name' => $categoryName]);

            foreach (self::STATUSES as $n => $status) {
                $seq++;
                $ward = $wards[($seq * 37) % $wards->count()];
                $type = $types[$n % count($types)];

                [$planned, $actual] = match ($status) {
                    'pending', 'planned' => [0, 0],
                    'in_progress' => [60 + $n * 3, 52 + $n * 3],
                    'delayed' => [80, 55],
                    'completed' => [100, 100],
                    'suspended' => [65, 40],
                    'cancelled' => [30, 10],
                };

                // Contractor: none while pending; the suspended contractor keeps one running
                // project (still updatable) and the blacklisted one a cancelled project.
                $contractor = match (true) {
                    $status === 'pending' => null,
                    $status === 'planned' && $directorate === 'mechanical' => null, // approved, awaiting assignment
                    $directorate === 'civil' && $n === 3 => $suspended,
                    $directorate === 'public_building' && $status === 'cancelled' => $blacklisted,
                    default => $active[$seq % $active->count()],
                };

                $commenced = now()->subMonths(14 - $n)->startOfMonth();
                $project = Project::create([
                    'project_code' => sprintf('MWH/2026/%s/%03d', $prefix, $n + 1),
                    'name' => ucfirst($ward->name).' '.$type,
                    'directorate' => $directorate,
                    'project_category_id' => $category->id,
                    'description' => ucfirst($type).' at '.$ward->name.' ward, '.$ward->lga->name.' LGA.',
                    'district_id' => $ward->lga->district_id,
                    'lga_id' => $ward->lga_id,
                    'ward_id' => $ward->id,
                    'latitude' => $ward->latitude ? $ward->latitude + (($seq % 7) - 3) / 1000 : null,
                    'longitude' => $ward->longitude ? $ward->longitude + (($seq % 5) - 2) / 1000 : null,
                    'contractor_id' => $contractor?->id,
                    'supervising_engineer_id' => $residentOf[$directorate]->id,
                    'award_date' => $status === 'pending' ? null : $commenced->copy()->subMonth(),
                    'commencement_date' => in_array($status, ['pending', 'planned'], true) ? null : $commenced,
                    'expected_completion_date' => $commenced->copy()->addMonths($status === 'delayed' ? 10 : 18),
                    'status' => $status,
                    'priority' => ['medium', 'high', 'low', 'critical'][$n % 4],
                    'funded_by' => $funding[$n % 3]->name,
                    'planned_progress_percent' => $planned,
                    'actual_progress_percent' => $actual,
                    // Planning registered a few; the Director registered the rest
                    'created_by' => ($n === 7 ? $planning : $directorOf[$directorate])->id,
                ]);

                if (! $contractor) {
                    continue;
                }

                // ---- Contract & payments (registered by Planning, paid by Finance, approved by Perm Sec)
                $value = (120 + ($seq * 53) % 700) * 1_000_000;
                $contract = Contract::create([
                    'project_id' => $project->id,
                    'contract_number' => sprintf('CTR/2026/%s/%03d', $prefix, $n + 1),
                    'award_date' => $project->award_date ?? now()->subMonths(2),
                    'contract_value' => $value,
                    'funding_source_id' => $funding[$n % 3]->id,
                    'retention_percent' => 5,
                ]);

                if ($actual > 0) {
                    Payment::create([
                        'contract_id' => $contract->id, 'certificate_number' => "IPC-{$prefix}{$seq}-1",
                        'amount' => round($value * $actual / 100 * 0.6, 2), 'retention_held' => round($value * $actual / 100 * 0.03, 2),
                        'payment_date' => now()->subMonths(2), 'status' => 'paid',
                        'recorded_by' => $finance->id, 'approved_by' => $ps->id,
                    ]);
                }
                if (in_array($status, ['in_progress', 'delayed'], true)) {
                    Payment::create([
                        'contract_id' => $contract->id, 'certificate_number' => "IPC-{$prefix}{$seq}-2",
                        'amount' => round($value * 0.1, 2), 'payment_date' => now()->subDays(10),
                        'status' => $n === 2 ? 'approved' : 'pending', // one waits to be marked paid, others wait for the Perm Sec
                        'recorded_by' => $finance->id, 'approved_by' => $n === 2 ? $ps->id : null,
                    ]);
                }

                // ---- Milestones, inspections, progress updates
                if ($actual > 0) {
                    foreach ($milestoneNames as $m => $name) {
                        $threshold = ($m + 1) / count($milestoneNames) * 100;
                        ProjectMilestone::create([
                            'project_id' => $project->id, 'name' => $name, 'sequence' => $m,
                            'planned_date' => $commenced->copy()->addMonths($m * 3),
                            'actual_date' => $actual >= $threshold ? $commenced->copy()->addMonths($m * 3)->addDays(10) : null,
                            'status' => $actual >= $threshold ? 'completed' : ($actual >= $threshold - 20 ? 'in_progress' : 'pending'),
                        ]);
                    }

                    Inspection::create([
                        'project_id' => $project->id, 'officer_id' => $residentOf[$directorate]->id,
                        'inspection_date' => now()->subDays(20 + $n), 'weather' => 'Sunny',
                        'observations' => 'Works progressing; materials on site conform to specification.',
                        'recommendations' => 'Increase manpower to recover lost time.',
                        'risk_level' => $status === 'delayed' ? 'high' : 'low',
                        'score' => $status === 'delayed' ? 48 : 82,
                        'next_inspection_date' => now()->addDays(10 - $n),
                    ]);

                    ProjectUpdate::create([
                        'project_id' => $project->id, 'submitted_by' => $residentOf[$directorate]->id,
                        'physical_progress_percent' => $actual, 'financial_progress_percent' => max(0, $actual - 10),
                        'narrative' => 'Monthly site report.', 'approval_status' => 'approved',
                        'approved_by' => $ps->id, 'approved_at' => now()->subDays(15),
                    ]);

                    if (in_array($status, ['in_progress', 'delayed'], true)) {
                        // waiting for the Permanent Secretary's approval
                        ProjectUpdate::create([
                            'project_id' => $project->id,
                            'submitted_by' => ($n === 3 ? $directorOf[$directorate] : $residentOf[$directorate])->id,
                            'physical_progress_percent' => min(100, $actual + 5), 'financial_progress_percent' => $actual,
                            'narrative' => 'Additional works completed this week.', 'approval_status' => 'pending',
                        ]);
                    }
                }
            }
        }

        // One project of each directorate that is DEACTIVATED (locked) — keeps its contractor and data.
        foreach (self::PLAN as $directorate => [$prefix]) {
            Project::where('directorate', $directorate)->where('status', 'completed')->first()
                ?->replicate(['project_code'])->fill([
                    'project_code' => "MWH/2025/{$prefix}/099",
                    'name' => 'Old '.self::PLAN[$directorate][2][0].' (deactivated)',
                    'status' => 'deactivated',
                ])->save();

            // One REJECTED project per directorate (not locked: the Director corrects and resubmits it)
            Project::where('directorate', $directorate)->where('status', 'pending')->first()
                ?->replicate(['project_code'])->fill([
                    'project_code' => "MWH/2026/{$prefix}/098",
                    'name' => 'Proposed '.self::PLAN[$directorate][2][1],
                    'status' => 'rejected',
                    'rejection_reason' => 'Bill of quantities and site survey report are missing. Attach them under Documents and resubmit.',
                ])->save();
        }
    }

    /** Small placeholder PDF files so the contractor documents feature has something to show. */
    private function dummyDocuments(Contractor $contractor, User $by): void
    {
        foreach ([['cac_certificate', 'CAC certificate'], ['tax_clearance', 'Tax clearance 2026']] as [$type, $title]) {
            $path = "contractors/{$contractor->id}/".$type.'.pdf';
            Storage::disk('local')->put($path, $this->placeholderPdf("{$title} - {$contractor->name} (dummy document)"));
            $contractor->documents()->create([
                'type' => $type, 'title' => $title, 'path' => $path,
                'original_name' => $type.'.pdf', 'size' => Storage::disk('local')->size($path), 'uploaded_by' => $by->id,
            ]);
        }
    }

    private function placeholderPdf(string $text): string
    {
        $pdf = new \App\Services\SimplePdf();
        $pdf->text(\App\Services\SimplePdf::margin(), 12, $text, true);

        return $pdf->output();
    }
}
