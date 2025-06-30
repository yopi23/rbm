<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PengeluaranTokoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tanggal_pengeluaran' => $this->tanggal_pengeluaran,
            'nama_pengeluaran' => $this->nama_pengeluaran,
            'jumlah_pengeluaran' => $this->jumlah_pengeluaran,
            'jumlah_pengeluaran_int' => $this->jumlah_pengeluaran_int,
            'jumlah_pengeluaran_formatted' => $this->jumlah_pengeluaran_formatted,
            'catatan_pengeluaran' => $this->catatan_pengeluaran,
            'kode_owner' => $this->kode_owner,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

class PengeluaranOperasionalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'tgl_pengeluaran' => $this->tgl_pengeluaran,
            'nama_pengeluaran' => $this->nama_pengeluaran,
            'kategori' => $this->kategori,
            'kategori_display' => $this->kategori_display,
            'kode_pegawai' => $this->kode_pegawai,
            'pegawai' => $this->when(
                $this->kode_pegawai !== '-' && is_numeric($this->kode_pegawai) && $this->relationLoaded('pegawai') && $this->pegawai,
                function () {
                    return [
                        'id' => $this->pegawai->id,
                        'name' => $this->pegawai->name,
                    ];
                }
            ),
            'jml_pengeluaran' => $this->jml_pengeluaran,
            'jml_pengeluaran_int' => $this->jml_pengeluaran_int,
            'jml_pengeluaran_formatted' => $this->jml_pengeluaran_formatted,
            'desc_pengeluaran' => $this->desc_pengeluaran,
            'kode_owner' => $this->kode_owner,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

// Collection Resources
class PengeluaranTokoCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'data' => PengeluaranTokoResource::collection($this->collection),
            'meta' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
                'has_more_pages' => $this->hasMorePages(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }
}

class PengeluaranOperasionalCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'data' => PengeluaranOperasionalResource::collection($this->collection),
            'meta' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
                'has_more_pages' => $this->hasMorePages(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }
}
