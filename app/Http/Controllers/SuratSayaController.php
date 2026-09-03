<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSuratStatusRequest;
use App\Models\Surat;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function updateStatus(UpdateSuratStatusRequest $request, Surat $surat): RedirectResponse
    {
        $surat->advanceStatus($request->validated('status'));
        $surat->save();

        return redirect()
            ->route('pegawai.surat-saya.show', $surat)
            ->with('success', 'Status surat berhasil diperbarui.');
    }
}
