<tr>
    <td>{{ $kode->kode }}</td>
    <td class="ps-{{ $level * 3 }}">{{ $kode->nama }}</td>
    <td>{{ $kode->level }}</td>
    <td>
        @if($kode->is_active)
            <span class="badge bg-success">Aktif</span>
        @else
            <span class="badge bg-secondary">Tidak Aktif</span>
        @endif
    </td>
    <td>
        <div class="btn-group" role="group">
            <a href="{{ route('kode-rekening.edit', $kode->id) }}" class="btn btn-primary btn-sm">
                <i class="bx bx-edit"></i> Edit
            </a>
            <button type="button" class="btn btn-danger btn-sm" onclick="confirm('Apakah Anda yakin ingin menghapus data ini?') || event.stopImmediatePropagation()" wire:click="delete({{ $kode->id }})" wire:loading.attr="disabled">
                <i class="bx bx-trash"></i> Hapus
            </button>
        </div>
    </td>
</tr>
@if(isset($kode->children) && count($kode->children) > 0)
    @foreach($kode->children as $child)
        @include('livewire.kode-rekening.partials.rekening-item', ['kode' => $child, 'level' => $level + 1])
    @endforeach
@endif
