<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanMagang extends Model
{
    use HasFactory;

    const STATUS = [
        'menunggu',
        'disetujui',
        'ditolak',
        'selesai',
    ];

    protected $table = 'pengajuan_magang';
    protected $primaryKey = 'pengajuan_id';

    protected $fillable = [
        'mahasiswa_id',
        'lowongan_id',
        'dosen_id',
        'tanggal_pengajuan',
        'status',
        'catatan_admin',
        'catatan_mahasiswa',
        // 'tanggal_mulai',
        // 'tanggal_selesai',
        'file_sertifikat'
    ];

    protected function fileSertifikat(): Attribute
    {
        return Attribute::make(
            get: fn(?string $filename) => $filename ? url('storage/dokumen/mahasiswa/sertifikat/' . $filename) : null,
        );
    }

    // Relasi ke Mahasiswa
    public function profilMahasiswa()
    {
        return $this->belongsTo(ProfilMahasiswa::class, 'mahasiswa_id', 'mahasiswa_id');
    }

    // Relasi ke Lowongan
    public function lowonganMagang()
    {
        return $this->belongsTo(LowonganMagang::class, 'lowongan_id', 'lowongan_id');
    }

    // Relasi ke Dosen
    public function profilDosen()
    {
        return $this->belongsTo(ProfilDosen::class, 'dosen_id', 'dosen_id');
    }
    public function preferensiMahasiswa()
    {
        return $this->belongsTo(PreferensiMahasiswa::class, 'mahasiswa_id', 'mahasiswa_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id', 'lokasi_id');
    }

    public function logAktivitas()
    {
        return $this->hasMany(LogAktivitas::class, 'pengajuan_id', 'pengajuan_id');
    }

    public function dokumenPengajuan()
    {
        return $this->hasMany(DokumenPengajuan::class, 'pengajuan_id', 'pengajuan_id');
    }
}
