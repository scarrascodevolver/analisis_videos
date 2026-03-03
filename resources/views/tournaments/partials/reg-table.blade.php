<div class="table-responsive">
    <table class="table table-sm mb-0">
        <tbody>
            @foreach($registrations as $reg)
                <tr id="reg-row-{{ $reg->id }}">
                    <td style="width:36px;">
                        @if($reg->clubOrganization->logo_path)
                            <img src="{{ asset('storage/' . $reg->clubOrganization->logo_path) }}"
                                 style="width:28px;height:28px;object-fit:contain;border-radius:4px;">
                        @else
                            <i class="fas fa-shield-alt text-muted"></i>
                        @endif
                    </td>
                    <td>{{ $reg->clubOrganization->name }}</td>
                    <td>
                        @if($reg->status === 'pending')
                            <span class="badge badge-warning">Pendiente</span>
                        @else
                            <span class="badge badge-success">Activo</span>
                        @endif
                    </td>
                    <td class="text-muted small">
                        {{ $reg->registered_at->diffForHumans() }}
                    </td>
                    <td class="text-right">
                        @if($reg->status === 'pending')
                            <button class="btn btn-xs btn-success btn-approve-reg"
                                    data-reg-id="{{ $reg->id }}">
                                <i class="fas fa-check"></i> Aprobar
                            </button>
                            <button class="btn btn-xs btn-outline-danger btn-reject-reg"
                                    data-reg-id="{{ $reg->id }}">
                                <i class="fas fa-times"></i>
                            </button>
                        @else
                            <button class="btn btn-xs btn-outline-secondary btn-revoke-reg"
                                    data-reg-id="{{ $reg->id }}"
                                    title="Dar de baja">
                                <i class="fas fa-user-minus"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
