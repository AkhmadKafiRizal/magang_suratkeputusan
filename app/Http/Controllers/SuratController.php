<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuratRequest;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function store(SuratRequest $request): RedirectResponse
    {
        $surat = new Surat;
        $surat->status = Surat::STATUS_BELUM_DITANGANI;
        $surat->applyAdministrativeChanges($request->validated());
        $surat->save();

        return redirect()
            ->route('kepala-bidang.surat.show', $surat)
            ->with('success', 'Data surat berhasil ditambahkan.');
    }

    public function show(Surat $surat): View
    {
        $surat->load('pegawai:id,name,email');

        return view('surat.show', compact('surat'));
    }

    public function edit(Surat $surat): View
    {
        return view('surat.edit', [
            'surat' => $surat,
            'pegawai' => $this->daftarPegawai(),
        ]);
    }

    public function update(SuratRequest $request, Surat $surat): RedirectResponse
    {
        $surat->applyAdministrativeChanges($request->validated());
        $surat->save();

        return redirect()
            ->route('kepala-bidang.surat.show', $surat)
            ->with('success', 'Data surat berhasil diperbarui.');
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
}
