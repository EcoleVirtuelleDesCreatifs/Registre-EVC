<?php

namespace Tests\Feature;

use App\Models\DocumentHolder;
use App\Models\DocumentType;
use App\Models\OfficialDocument;
use App\Models\User;
use App\Services\DocumentWorkflow;
use App\Services\ReferenceGenerator;
use App\Services\WorkflowException;
use App\Support\Reference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistreTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => $role.'@evc.test', 'password' => 'MotDePasse2026!', 'role' => $role, 'is_active' => true,
        ]);
    }

    private function draft(string $type = 'certificat', ?string $formation = 'DG'): OfficialDocument
    {
        return OfficialDocument::create([
            'document_type_id' => DocumentType::where('code', $type)->value('id'),
            'holder_id' => DocumentHolder::create(['full_name' => 'Awa Koné'])->id,
            'formation' => $formation, 'status' => 'brouillon', 'source' => 'manuel', 'pdf_public' => true,
        ]);
    }

    public function test_reference_normalisation(): void
    {
        $this->assertSame('EVC-DG-2026-0001', Reference::normalize(' evc–dg_2026 0001 '));
        $this->assertTrue(Reference::isValid('ATT-EVC-2026-0001'));
        $this->assertFalse(Reference::isValid('EVC-DG-26-1'));
    }

    public function test_new_format_continues_after_legacy_references(): void
    {
        $legacy = $this->draft('attestation_formation');
        $legacy->update(['reference' => 'ATT-EVC-2026-0001', 'status' => 'publie', 'public_token' => Str::random(40)]);

        $type = DocumentType::where('code', 'attestation_formation')->first();
        $ref = app(ReferenceGenerator::class)->next($type, 'DG', 2026);

        $this->assertSame('EVC-ATT-2026-0002', $ref);
        $this->assertSame('EVC-ATT-2026-0003', app(ReferenceGenerator::class)->next($type, 'DG', 2026));
    }

    public function test_full_workflow_and_separation_of_duties(): void
    {
        Storage::fake('local');
        $wf = app(DocumentWorkflow::class);
        $secretariat = $this->user('secretariat');
        $pedago = $this->user('direction_pedagogique');
        $dg = $this->user('direction_generale');
        $doc = $this->draft();

        $wf->submit($doc, $secretariat);
        $this->assertSame('en_verification', $doc->fresh()->status);

        // Contrôle incomplet refusé
        try {
            $wf->control($doc->fresh(), $pedago, ['evaluations'], true, null);
            $this->fail('Le contrôle incomplet aurait dû être refusé.');
        } catch (WorkflowException) {
        }

        $all = array_keys(config('evc.criteres_certificat'));
        $wf->control($doc->fresh(), $pedago, $all, true, 'Dossier complet');
        $this->assertNotNull($doc->fresh()->pedagogical_validated_at);
        $this->assertNull($doc->fresh()->reference, 'Aucune référence avant la délivrance.');

        $wf->approve($doc->fresh(), $dg, true, null);
        $ref = $wf->deliver($doc->fresh(), $dg, now()->toDateString());
        $this->assertMatchesRegularExpression('/^EVC-DG-\d{4}-0001$/', $ref);

        // Le PDF officiel est généré automatiquement à la délivrance, avec son empreinte
        $fresh = $doc->fresh();
        $this->assertNotNull($fresh->generated_pdf_path, 'Le PDF officiel doit être généré à la délivrance.');
        Storage::disk('local')->assertExists($fresh->pdf_path);
        $this->assertSame(hash('sha256', Storage::disk('local')->get($fresh->pdf_path)), $fresh->pdf_sha256);
        $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($fresh->pdf_path));

        $wf->publish($doc->fresh(), $dg);
        $this->assertSame('publie', $doc->fresh()->status);
        $this->assertGreaterThan(0, $doc->auditLogs()->count());

        // Vérification publique
        $this->get('/v/'.$ref)->assertOk()->assertSee('Awa Koné');
        $this->get('/q/'.$doc->fresh()->public_token)->assertOk()->assertSee($ref);

        // Révocation
        $wf->revoke($doc->fresh(), $dg, 'Erreur matérielle constatée sur le dossier.');
        $this->get('/v/'.$ref)->assertOk()->assertSee('Révoqué');
    }

    public function test_unpublished_or_unknown_reference_is_not_disclosed(): void
    {
        $doc = $this->draft();
        $doc->update(['reference' => 'EVC-DG-2026-0042', 'status' => 'approuve']);

        $this->get('/v/EVC-DG-2026-0042')->assertDontSee('Awa Koné');
        $this->get('/v/EVC-DG-2026-9999')->assertDontSee('Awa Koné');
    }

    public function test_secretariat_cannot_approve(): void
    {
        $secretariat = $this->user('secretariat');
        $doc = $this->draft();
        $doc->update(['status' => 'en_verification', 'pedagogical_validated_at' => now()]);

        $this->assertFalse($secretariat->can('approve', $doc));
        $this->assertFalse($secretariat->can('documents.revoke'));
        $this->assertTrue($this->user('direction_generale')->can('approve', $doc));
    }

    public function test_audit_log_is_immutable(): void
    {
        $doc = $this->draft();
        \App\Services\AuditLogger::log('test', 'Entrée de test', $doc);
        $log = $doc->auditLogs()->first();

        $this->expectException(\Throwable::class);
        $log->update(['description' => 'falsifié']);
    }

    public function test_admin_requires_authentication(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_french_wording(): void
    {
        $this->assertSame('le treizième jour du mois de novembre de l’an deux mille vingt-six', \App\Support\FrenchText::solemnDate('2026-11-13'));
        $this->assertSame('le premier jour du mois de mars de l’an deux mille vingt-sept', \App\Support\FrenchText::solemnDate('2027-03-01'));
        $this->assertSame('vingt et unième', \App\Support\FrenchText::ordinal(21));
        $this->assertSame('Quatre (4) mois', \App\Support\FrenchText::duration('2026-03-21', '2026-07-21'));
        $this->assertSame('1er février 2026', \App\Support\FrenchText::date('2026-02-01'));
    }

    public function test_draft_preview_is_a_specimen_with_holder_data(): void
    {
        $doc = $this->draft('attestation_formation', 'DG');
        $doc->update(['details' => ['civilite' => 'Mme', 'statut' => 'finalisation', 'inscription' => '2026-02-11']]);
        $html = app(\App\Services\DocumentRenderer::class)->html($doc->fresh());

        $this->assertStringContainsString('Madame Awa Koné', $html);
        $this->assertStringContainsString('inscrite', $html);
        $this->assertStringContainsString('SPÉCIMEN', $html);
        $this->assertStringContainsString('11 février 2026', $html);
    }

    public function test_students_by_document_lists_holders_per_type(): void
    {
        $this->draft('certificat', 'DG');
        $this->draft('recommandation', null)->holder->update(['full_name' => 'Yao Kouassi']);
        $user = $this->user('secretariat');

        $this->actingAs($user)->get('/admin/etudiants-par-document?type=certificat')
            ->assertOk()
            ->assertSee('Étudiants par document')
            ->assertSee('Awa Koné')
            ->assertDontSee('Yao Kouassi');

        $this->actingAs($user)->get('/admin/etudiants-par-document?type=recommandation')
            ->assertOk()->assertSee('Yao Kouassi');

        $this->actingAs($user)->get('/admin/etudiants-par-document/export?type=certificat')
            ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
