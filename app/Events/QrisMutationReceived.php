<?php

namespace App\Events;

// use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QrisMutationReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ownerDetailId;
    public $kasirDetailId;
    public $mutasi;

    /**
     * Create a new event instance.
     */
    public function __construct(int $ownerDetailId, int $kasirDetailId, array $mutasi)
    {
        $this->ownerDetailId = $ownerDetailId;
        $this->kasirDetailId = $kasirDetailId;
        $this->mutasi = $mutasi;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * KEAMANAN UTAMA: Channel name harus spesifik untuk kombinasi owner+kasir
     * Ini memastikan notifikasi hanya diterima oleh kasir yang tepat
     */
    public function broadcastOn(): array
    {
        // Format: qris-mutations.{owner_detail_id}.{kasir_detail_id}
        // return new PrivateChannel("qris-mutations.{$this->ownerDetailId}.{$this->kasirDetailId}");
        return [
            new PrivateChannel("qris-mutations.{$this->ownerDetailId}.{$this->kasirDetailId}")
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'qris.mutation.received';
    }

    /**
     * Get the data to broadcast.
     *
     * KEAMANAN: Sertakan owner_detail_id dan kasir_detail_id di payload
     * untuk validasi tambahan di client-side
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->mutasi['id'],
            'owner_detail_id' => $this->ownerDetailId,
            'kasir_detail_id' => $this->kasirDetailId,
            'nominal' => $this->mutasi['nominal'],
            'keterangan' => $this->mutasi['keterangan'],
            'status' => $this->mutasi['status'],
            'reported_at' => $this->mutasi['reported_at'],
            'created_at' => $this->mutasi['created_at'],
        ];
    }
}
