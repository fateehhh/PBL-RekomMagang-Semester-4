<div class="d-flex flex-column gap-2 pb-4">
    <div class="d-flex flex-row w-100">
        <div class="card-body d-flex flex-column gap-2 flex-fill">
            <div class="d-flex flex-row gap-2 align-items-end justify-content-between">
                <div class="d-flex flex-row gap-2 align-items-end">
                    <h3 class="fw-bold mb-0">{{ $lowongan->judul_lowongan }} </h3>
                </div>
            </div>
            <div class="d-flex flex-row gap-2">
                <span class="badge my-auto bg-{{ $lowongan->is_active ? 'success' : 'danger' }}">
                    {{ $lowongan->is_active ? 'Aktif' : 'Tidak Aktif' }}
                </span>
                <p class="mb-0 text-muted">
                    Batas Pendaftaran: {{ \Carbon\Carbon::parse($lowongan->batas_pendaftaran)->format('d/m/Y') }} atau
                    <strong>{{ $days }}</strong>
                    hari lagi
                </p>
            </div>
            <div class="d-flex flex-column gap-2 mt-1">
                <h5 class="fw-bold mb-0"><span class="text-muted">Posisi:</span> {{ $lowongan->judul_posisi }} </h5>
                <p>
                    {!! nl2br(e($lowongan->deskripsi)) !!}
                </p>
            </div>
            <div class="d-flex flex-row">
                <div>
                    <h5 class="fw-bold mb-0">Persyaratan Magang</h5>
                    <ul class="list-unstyled">
                        <li>&#8226; IPK Minimum: {{ $lowongan->persyaratanMagang->minimum_ipk }}</li>
                        @foreach (explode(';', $lowongan->persyaratanMagang->deskripsi_persyaratan) as $deskripsiPersyaratan)
                            <li>&#8226; {{ $deskripsiPersyaratan }}</li>
                        @endforeach
                    </ul>
                </div>
                @if ($lowongan->persyaratanMagang->dokumen_persyaratan)
                    <div class="mx-auto">
                        <h5 class="fw-bold mb-0">Persyaratan Dokumen</h5>
                        <ul class="list-unstyled">
                            @foreach (explode(';', $lowongan->persyaratanMagang->dokumen_persyaratan) as $dokumenPersyaratan)
                                <li>&#8226; {{ $dokumenPersyaratan }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <div>
                <h5 class="fw-bold mb-0">Skill Minimum</h5>
                <div class="d-flex flex-column gap-2">
                    @foreach ($tingkat_kemampuan as $keytingkatKemampuan => $tingkatKemampuan)
                        @php
                            $keahlianLowongan = $lowongan->keahlianLowongan->where(
                                'kemampuan_minimum',
                                $keytingkatKemampuan,
                            );
                        @endphp
                        @if (!$keahlianLowongan->isEmpty())
                            <div class="d-flex flex-column">
                                <p class="fw-bold mb-0"> &#8226; <span>{{ $tingkatKemampuan }}</span> </p>
                                <div class="d-flex flex-row gap-1 flex-wrap ps-2 _keahlian">
                                    @foreach ($keahlianLowongan as $keahlianMahasiswa)
                                        <span
                                            class="badge badge-sm 
                                            @if ($keytingkatKemampuan == 'ahli') bg-danger 
                                            @elseif ($keytingkatKemampuan == 'mahir') bg-warning 
                                            @elseif ($keytingkatKemampuan == 'menengah') bg-primary 
                                            @else bg-info @endif">{{ $keahlianMahasiswa->keahlian->nama_keahlian }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="d-flex flex-column gap-1 mt-1">
                <h5 class="fw-bold mb-0">Tentang Lowongan</h5>
                <div class="d-flex flex-row gap-3">
                    <div class="d-flex flex-column gap-0 align-items-center">
                        <div class="d-flex flex-row gap-1 align-content-center justify-content-center">
                            <svg class="icon my-auto ">
                                <use xlink:href="{{ url('build/@coreui/icons/sprites/free.svg#cil-location-pin') }}">
                                </use>
                            </svg>
                            <p class="mb-0"> Tipe Kerja </p>
                        </div>
                        <div>
                            <span class="badge bg-primary my-auto">
                                {{ ucfirst($lowongan->tipe_kerja_lowongan) }}
                            </span>
                        </div>
                    </div>
                    @if (isset($score))
                        <div class="d-flex flex-column gap-0 align-items-center">
                            <div class="d-flex flex-row gap-1 align-content-center justify-content-center">
                                <p class="mb-0">%</p>
                                <p class="mb-0">Skor Kecocokan</p>
                            </div>
                            <div>
                                <span
                                    class="badge fw-bold my-auto bg-{{ $score > 0.7 ? 'success' : ($score > 0.5 ? 'warning' : 'danger') }}">
                                    {{ number_format($score * 100, 2) }}%
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="d-flex flex-column gap-1 mt-1">
                <h5 class="fw-bold mb-0">Tanggal</h5>
                <div class="d-flex flex-row gap-1 align-content-center justify-content-start">
                    <svg class="icon my-auto ">
                        <use xlink:href="{{ url('build/@coreui/icons/sprites/free.svg#cil-clock') }}"></use>
                    </svg>
                    <p class="mb-0 text-muted"> Mulai: </p>
                    <div>
                        <p class="mb-0">
                            {{ \Carbon\Carbon::parse($lowongan->tanggal_mulai)->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
                <div class="d-flex flex-row gap-1 align-content-center justify-content-start">
                    <svg class="icon my-auto ">
                        <use xlink:href="{{ url('build/@coreui/icons/sprites/free.svg#cil-flag-alt') }}"></use>
                    </svg>
                    <p class="mb-0 text-muted"> Selesai: </p>
                    <div>
                        <p class="mb-0">
                            {{ \Carbon\Carbon::parse($lowongan->tanggal_selesai)->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card m-4" style="height: fit-content; max-width: 250px;">
            <div class="card-body d-flex flex-column flex-fill text-center">
                <h4 class="mb-0">
                    <span class="badge bg-info mb-0  {{ $lowongan->gaji > 0 ? 'bg-info' : 'bg-danger' }}">
                        {{ $lowongan->gaji > 0 ? 'Rp. ' . $lowongan->gaji : 'Tidak ada gaji' }}
                    </span>
                </h4>
                <hr class="my-2">
                <div class="d-flex flex-column gap-1 text-start">
                    <h6 class="fw-bold mb-0">Informasi Perusahaan</h6>
                    <p class="mb-0 small">
                        {{ $lowongan->perusahaanMitra->nama_perusahaan }}
                    </p>
                    <p class="mb-0 small"><span class="text-muted">Bidang Industri:</span>
                        {{ $lowongan->perusahaanMitra->bidang_industri }}
                    </p>

                    <a class="mb-0 small" target="_blank" href="{{ $lowongan->perusahaanMitra->website }}">
                        {{ $lowongan->perusahaanMitra->website }}
                    </a>
                    <a class="mb-0 small" href="mailto:{{ $lowongan->perusahaanMitra->kontak_email }}">
                        {{ $lowongan->perusahaanMitra->kontak_email }}
                    </a>
                    <p class="mb-0 small"><span class="text-muted">Telepon:</span>
                        {{ $lowongan->perusahaanMitra->kontak_telepon }}
                    </p>
                </div>
                <hr class="my-2">
                <div class="d-flex flex-column gap-1 text-start">
                    <h6 class="fw-bold mb-0">Lokasi</h6>
                    <a href="https://maps.google.com/?q={{ $lokasi->latitude }},{{ $lokasi->longitude }}"
                        target="_blank">
                        {{ $lokasi->alamat }}
                    </a>
                    <p class="mb-0 small"><span class="text-muted">Jarak dengan preferensi:<br /></span>
                        {{ number_format($jarak, 2) }} <span class="text-muted fw-bold">KM</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
