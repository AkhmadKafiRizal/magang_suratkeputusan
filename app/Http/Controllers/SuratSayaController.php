<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSuratStatusRequest;
use App\Models\Surat;
use App\Services\AktivitasSuratService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SuratSayaController extends Controller
{
    public function index(Request $request): View
    {
        $surats = Surat::query()
            ->where('pegawai_id', $request->user()->id)
            ->latest('tanggal_masuk')
            ->latest('id')
            ->paginate(10);

        return view('pegawai.surat.index', compact('surats'));
    }

    public function show(Surat $surat): View
    {
        Gate::authorize('viewMine', $surat);
        $surat->load('pegawai:id,name,email');

        return view('pegawai.surat.show', compact('surat'));
    }

    public function updateStatus(
        UpdateSuratStatusRequest $request,
        Surat $surat,
        AktivitasSuratService $aktivitasSurat
    ): RedirectResponse {
        $statusTujuan = $request->validated('status');

        DB::transaction(function () use ($request, $surat, $aktivitasSurat): void {
            $surat->advanceStatus($request->validated('status'));
            $surat->save();
            $aktivitasSurat->statusDiperbarui($surat, $request->user());
        });

        return redirect()
            ->route('pegawai.surat-saya.show', $surat)
            ->with(
                'success',
                $statusTujuan === Surat::STATUS_SELESAI
                    ? 'Surat berhasil ditandai selesai.'
                    : 'Surat mulai diproses.'
            );
    }
}
