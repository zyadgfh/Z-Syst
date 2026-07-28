<?php

namespace Database\Seeders;

use App\Models\DrugInteraction;
use Illuminate\Database\Seeder;

class DrugInteractionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seed common drug interactions.
     * These are global interactions (business_id = null) that apply to all businesses.
     */
    public function run(): void
    {
        $interactions = [
            // === CONTRAINDICATED (Do not use together) ===
            [
                'drug_a_name' => 'Warfarin',
                'drug_b_name' => 'Aspirin',
                'severity' => 'contraindicated',
                'description' => 'Increased risk of bleeding. Aspirin inhibits platelet aggregation and warfarin inhibits clotting factors, leading to synergistic anticoagulant effect.',
                'mechanism' => 'Pharmacodynamic interaction - additive anticoagulation',
                'recommendation' => 'Avoid concomitant use. If necessary, monitor INR closely and adjust warfarin dose. Consider alternative analgesic/antiplatelet therapy.',
                'category' => 'pharmacodynamic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'Warfarin',
                'drug_b_name' => 'NSAIDs',
                'severity' => 'contraindicated',
                'description' => 'Significantly increased risk of gastrointestinal bleeding. NSAIDs inhibit COX-1, reducing gastric mucosal protection while warfarin impairs coagulation.',
                'mechanism' => 'Pharmacodynamic interaction - additive anticoagulation + mucosal damage',
                'recommendation' => 'Avoid concomitant use. If unavoidable, use lowest effective NSAID dose for shortest duration, add PPI for gastric protection, and monitor INR frequently.',
                'category' => 'pharmacodynamic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'MAO Inhibitors',
                'drug_b_name' => 'SSRIs',
                'severity' => 'contraindicated',
                'description' => 'Risk of serotonin syndrome (agitation, hyperthermia, autonomic instability, neuromuscular abnormalities). Both drugs increase serotonin levels through different mechanisms.',
                'mechanism' => 'Pharmacodynamic interaction - excessive serotonergic activity',
                'recommendation' => 'Allow at least 14 days between discontinuing MAOI and starting SSRI. If serotonin syndrome occurs, discontinue both and provide supportive care.',
                'category' => 'pharmacodynamic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'Cisapride',
                'drug_b_name' => 'Erythromycin',
                'severity' => 'contraindicated',
                'description' => 'Increased risk of life-threatening cardiac arrhythmias (QT prolongation, torsades de pointes). Both drugs can prolong QT interval.',
                'mechanism' => 'Pharmacodynamic interaction - additive QT prolongation + CYP3A4 inhibition',
                'recommendation' => 'Contraindicated. Use alternative prokinetic agent or alternative antibiotic.',
                'category' => 'pharmacodynamic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'Statins',
                'drug_b_name' => 'Gemfibrozil',
                'severity' => 'contraindicated',
                'description' => 'Increased risk of myopathy and rhabdomyolysis. Gemfibrozil inhibits glucuronidation of statins, increasing statin plasma concentrations.',
                'mechanism' => 'Pharmacokinetic interaction - inhibition of statin metabolism',
                'recommendation' => 'Avoid combination. If combination therapy needed, use fenofibrate instead of gemfibrozil and monitor for muscle symptoms.',
                'category' => 'pharmacokinetic',
                'source' => 'FDA Label',
            ],

            // === SEVERE (Use with caution, monitor closely) ===
            [
                'drug_a_name' => 'ACE Inhibitors',
                'drug_b_name' => 'Potassium-Sparing Diuretics',
                'severity' => 'severe',
                'description' => 'Increased risk of hyperkalemia. ACE inhibitors decrease aldosterone production while potassium-sparing diuretics reduce potassium excretion.',
                'mechanism' => 'Pharmacodynamic interaction - additive potassium retention',
                'recommendation' => 'Monitor serum potassium regularly, especially in elderly patients and those with renal impairment. Consider alternative antihypertensive.',
                'category' => 'pharmacodynamic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'Digoxin',
                'drug_b_name' => 'Amiodarone',
                'severity' => 'severe',
                'description' => 'Increased digoxin plasma concentration leading to digoxin toxicity (nausea, arrhythmias, visual disturbances). Amiodarone inhibits P-glycoprotein-mediated digoxin clearance.',
                'mechanism' => 'Pharmacokinetic interaction - P-glycoprotein inhibition',
                'recommendation' => 'Reduce digoxin dose by 50% when starting amiodarone. Monitor digoxin levels and renal function.',
                'category' => 'pharmacokinetic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'Clopidogrel',
                'drug_b_name' => 'Omeprazole',
                'severity' => 'severe',
                'description' => 'Reduced clopidogrel effectiveness due to CYP2C19 inhibition by omeprazole, decreasing conversion to active metabolite.',
                'mechanism' => 'Pharmacokinetic interaction - CYP2C19 inhibition',
                'recommendation' => 'Use pantoprazole or other PPI not metabolized by CYP2C19 instead of omeprazole or esomeprazole.',
                'category' => 'pharmacokinetic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'Methotrexate',
                'drug_b_name' => 'Trimethoprim-Sulfamethoxazole',
                'severity' => 'severe',
                'description' => 'Increased risk of methotrexate toxicity (bone marrow suppression, hepatotoxicity). Additive antifolate effect and reduced renal clearance.',
                'mechanism' => 'Pharmacodynamic + pharmacokinetic - additive antifolate effect',
                'recommendation' => 'Avoid concomitant use if possible. If necessary, monitor CBC, LFTs closely and consider folinic acid rescue.',
                'category' => 'pharmacodynamic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'Lithium',
                'drug_b_name' => 'NSAIDs',
                'severity' => 'severe',
                'description' => 'Increased lithium plasma concentration leading to lithium toxicity. NSAIDs reduce renal lithium clearance by inhibiting prostaglandin synthesis.',
                'mechanism' => 'Pharmacokinetic interaction - reduced renal clearance',
                'recommendation' => 'Monitor lithium levels closely when starting/stopping NSAIDs. May need to reduce lithium dose. Prefer acetaminophen for pain.',
                'category' => 'pharmacokinetic',
                'source' => 'FDA Label',
            ],

            // === MODERATE (Monitor therapy) ===
            [
                'drug_a_name' => 'Metformin',
                'drug_b_name' => 'Contrast Dye (Iodinated)',
                'severity' => 'moderate',
                'description' => 'Increased risk of lactic acidosis, particularly in patients with renal impairment. Contrast dye can cause acute kidney injury reducing metformin clearance.',
                'mechanism' => 'Pharmacokinetic interaction',
                'recommendation' => 'Discontinue metformin at time of or before contrast procedure, hold for 48 hours, and restart after renal function confirmed normal.',
                'category' => 'pharmacokinetic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'SSRIs',
                'drug_b_name' => 'NSAIDs',
                'severity' => 'moderate',
                'description' => 'Increased risk of upper gastrointestinal bleeding. SSRIs impair platelet function by depleting serotonin in platelets, while NSAIDs cause gastric mucosal damage.',
                'mechanism' => 'Pharmacodynamic interaction - additive effect on bleeding risk',
                'recommendation' => 'Consider adding PPI for gastric protection. Monitor for signs of GI bleeding. Consider alternative antidepressant or analgesic.',
                'category' => 'pharmacodynamic',
                'source' => 'PubMed / Clinical Studies',
            ],
            [
                'drug_a_name' => 'Theophylline',
                'drug_b_name' => 'Ciprofloxacin',
                'severity' => 'moderate',
                'description' => 'Increased theophylline concentration causing toxicity (nausea, tachycardia, seizures). Ciprofloxacin inhibits CYP1A2-mediated theophylline metabolism.',
                'mechanism' => 'Pharmacokinetic interaction - CYP1A2 inhibition',
                'recommendation' => 'Monitor theophylline levels and reduce dose as needed. Consider alternative antibiotic.',
                'category' => 'pharmacokinetic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'Phenytoin',
                'drug_b_name' => 'Fluconazole',
                'severity' => 'moderate',
                'description' => 'Increased phenytoin concentration causing toxicity. Fluconazole inhibits CYP2C9-mediated phenytoin metabolism.',
                'mechanism' => 'Pharmacokinetic interaction - CYP2C9 inhibition',
                'recommendation' => 'Monitor phenytoin levels and reduce dose if necessary. Monitor for phenytoin toxicity (nystagmus, ataxia, slurred speech).',
                'category' => 'pharmacokinetic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'Calcium Channel Blockers',
                'drug_b_name' => 'Beta Blockers',
                'severity' => 'moderate',
                'description' => 'Increased risk of bradycardia, heart block, and hypotension. Both drugs have negative chronotropic and inotropic effects on the heart.',
                'mechanism' => 'Pharmacodynamic interaction - additive cardiovascular depression',
                'recommendation' => 'Monitor heart rate and blood pressure. Particularly caution with verapamil/diltiazem combined with beta blockers.',
                'category' => 'pharmacodynamic',
                'source' => 'FDA Label',
            ],

            // === MINOR (Limited clinical significance) ===
            [
                'drug_a_name' => 'Antacids',
                'drug_b_name' => 'Tetracyclines',
                'severity' => 'minor',
                'description' => 'Reduced tetracycline absorption. Antacids containing aluminum, calcium, or magnesium chelate with tetracyclines reducing bioavailability.',
                'mechanism' => 'Pharmacokinetic interaction - chelation in GI tract',
                'recommendation' => 'Separate administration by at least 2 hours. Take tetracycline 1 hour before or 2 hours after antacids.',
                'category' => 'pharmacokinetic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'Vitamin K Antagonists',
                'drug_b_name' => 'Green Tea',
                'severity' => 'minor',
                'description' => 'Reduced anticoagulant effect. Green tea contains vitamin K which can antagonize warfarin effect.',
                'mechanism' => 'Pharmacodynamic interaction - vitamin K content',
                'recommendation' => 'Maintain consistent intake of green tea. Monitor INR if consumption changes significantly.',
                'category' => 'pharmacodynamic',
                'source' => 'PubMed / Clinical Studies',
            ],
            [
                'drug_a_name' => 'Iron Supplements',
                'drug_b_name' => 'Levothyroxine',
                'severity' => 'minor',
                'description' => 'Reduced levothyroxine absorption. Iron forms insoluble complexes with levothyroxine in the GI tract.',
                'mechanism' => 'Pharmacokinetic interaction - GI chelation',
                'recommendation' => 'Separate administration by at least 4 hours. Monitor thyroid function tests if starting/stopping iron.',
                'category' => 'pharmacokinetic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'Caffeine',
                'drug_b_name' => 'Quinolones',
                'severity' => 'minor',
                'description' => 'Increased caffeine effects (nervousness, insomnia, heart palpitations). Quinolones inhibit CYP1A2 reducing caffeine metabolism.',
                'mechanism' => 'Pharmacokinetic interaction - CYP1A2 inhibition',
                'recommendation' => 'Limit caffeine intake during quinolone therapy. Educate patient about potential increased caffeine sensitivity.',
                'category' => 'pharmacokinetic',
                'source' => 'FDA Label',
            ],
            [
                'drug_a_name' => 'Grapefruit Juice',
                'drug_b_name' => 'Statins',
                'severity' => 'minor',
                'description' => 'Increased statin plasma concentration. Grapefruit juice inhibits CYP3A4 in the gut wall, increasing bioavailability of statins like simvastatin and atorvastatin.',
                'mechanism' => 'Pharmacokinetic interaction - CYP3A4 inhibition',
                'recommendation' => 'Limit grapefruit juice intake (less than 1 quart/day). Avoid grapefruit juice with simvastatin and lovastatin. Rosuvastatin and pravastatin are minimally affected.',
                'category' => 'pharmacokinetic',
                'source' => 'FDA Label',
            ],
        ];

        foreach ($interactions as $interaction) {
            DrugInteraction::create($interaction);
        }

        $this->command->info('Seeded ' . count($interactions) . ' common drug interactions.');
    }
}

