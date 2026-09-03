<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuratRequest;
use App\Models\Surat;
use App\Models\User;
use App\Services\AktivitasSuratService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuratController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $pegawaiFilter = (string) $request->query('pegawai', '');

        $surats = Surat::query()
            ->with('pegawai:id,name')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('perihal', 'like', "%{$search}%")
                        ->orWhere('pemohon_pengirim', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists($status, Surat::STATUS_LABELS), fn ($query) => $query->where('status', $status))
            ->when($pegawaiFilter === 'belum_ditugaskan', fn ($query) => $query->whereNull('pegawai_id'))
            ->when(ctype_digit($pegawaiFilter), fn ($query) => $query->where('pegawai_id', (int) $pegawaiFilter))
            ->latest('tanggal_masuk')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('surat.index', [
            'surats' => $surats,
            'pegawai' => $this->daftarPegawai(),
            'statusLabels' => Surat::STATUS_LABELS,
        ]);
    }

    public function create(): View
    {
        return view('surat.create', [
            'surat' => new Surat,
            'pegawai' => $this->daftarPegawai(),
        ]);
    }

    public function store(
        SuratRequest $request,
        AktivitasSuratService $aktivitasSurat
    ): RedirectResponse {
        $surat = DB::transaction(function () use ($request, $aktivitasSurat): Surat {
            $surat = new Surat;
            $surat->status = Surat::STATUS_BELUM_DITANGANI;
            $surat->applyAdministrativeChanges($request->validated());
            $surat->save();

            $actor = $request->user();
            $aktivitasSurat->suratDicatat($surat, $actor);

            if ($surat->pegawai_id !== null) {
                $pegawaiBaru = User::query()->findOrFail($surat->pegawai_id);
                $aktivitasSurat->perubahanPenugasan($surat, $actor, null, $pegawaiBaru);
            }

            return $surat;
        });

        return redirect()
            ->route('kepala-bidang.surat.show', $surat)
            ->with('success', 'Surat berhasil dicatat.');
    }

    public function show(Surat $surat): View
    {
        $surat->load('pegawai:id,name,email');
        $aktivitasTerbaru = $surat->aktivitas()
            ->with('actor:id,name')
            ->latest('created_at')
            ->latest('id')
            ->limit(5)
            ->get();

        return view('surat.show', compact('surat', 'aktivitasTerbaru'));
    }

    public function edit(Surat $surat): View
    {
        $surat->load('pegawai:id,name');

        return view('surat.edit', [
            'surat' => $surat,
            'pegawai' => $this->daftarPegawai(),
        ]);
    }

    public function update(
        SuratRequest $request,
        Surat $surat,
        AktivitasSuratService $aktivitasSurat
    ): RedirectResponse {
        $feedback = DB::transaction(function () use ($request, $surat, $aktivitasSurat): string {
            $surat->loadMissing('pegawai:id,name');
            $pegawaiLama = $surat->pegawai;
            $actor = $request->user();
            $nilaiLama = $this->nilaiAdministratif($surat);
            $pegawaiLamaId = $surat->pegawai_id;

            $surat->applyAdministrativeChanges($request->validated());
            $nilaiBaru = $this->nilaiAdministratif($surat);
            $pegawaiBerubah = (string) $pegawaiLamaId !== (string) $surat->pegawai_id;
            $fieldBerubah = [];

            foreach ($nilaiLama as $field => $nilai) {
                if ($nilai !== $nilaiBaru[$field]) {
                    $fieldBerubah[] = $field;
                }
            }

            if ($fieldBerubah !== [] || $pegawaiBerubah) {
                $surat->save();
            }

            if ($fieldBerubah !== []) {
                $aktivitasSurat->suratDiedit($surat, $actor, $fieldBerubah);
            }

            if ($pegawaiBerubah) {
                $pegawaiBaru = $surat->pegawai_id === null
                    ? null
                    : User::query()->findOrFail($surat->pegawai_id);
                $aktivitasSurat->perubahanPenugasan(
                    $surat,
                    $actor,
                    $pegawaiLama,
                    $pegawaiBaru
                );

                if ($pegawaiBaru === null) {
                    return 'Penugasan surat berhasil dihapus.';
                }

                if ($pegawaiLama === null) {
                    return "Surat berhasil ditugaskan kepada {$pegawaiBaru->name}.";
                }

                return "Penugasan berhasil dipindahkan kepada {$pegawaiBaru->name}.";
            }

            return 'Data surat berhasil diperbarui.';
        });

        return redirect()
            ->route('kepala-bidang.surat.show', $surat)
            ->with('success', $feedback);
    }

    /**
     * @return Collection<int, User>
     */
    private function daftarPegawai(): Collection
    {
        return User::query()
            ->where('role', User::ROLE_PEGAWAI)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    /** @return array<string, string|null> */
    private function nilaiAdministratif(Surat $surat): array
    {
        return [
            'nomor_surat' => $surat->nomor_surat,
            'tanggal_masuk' => $surat->tanggal_masuk?->format('Y-m-d'),
            'perihal' => $surat->perihal,
            'pemohon_pengirim' => $surat->pemohon_pengirim,
            'keterangan' => $surat->keterangan,
        ];
    }
}
