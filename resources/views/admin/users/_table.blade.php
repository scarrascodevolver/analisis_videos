<table class="table table-bordered table-hover">
    <thead class="rugby-green">
        <tr>
            <th width="25%">Nombre</th>
            <th width="25%">Email</th>
            <th width="15%">Rol</th>
            <th width="15%">Categoría</th>
            <th width="10%" class="text-center">Registro</th>
            <th width="10%" class="text-center">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $user)
            <tr>
                <td>
                    @if($user->profile && $user->profile->avatar)
                        <img src="{{ asset('storage/' . $user->profile->avatar) }}"
                             alt="Avatar"
                             class="img-circle mr-2"
                             style="width: 25px; height: 25px; object-fit: cover;">
                    @endif
                    <strong>{{ $user->name }}</strong>
                </td>
                <td>{{ $user->email }}</td>
                <td>
                    @php
                        $roleColors = [
                            'jugador' => 'primary',
                            'entrenador' => 'success',
                            'analista' => 'warning',
                            'staff' => 'info',
                            'director_club' => 'danger',
                            'director_tecnico' => 'secondary'
                        ];
                        $color = $roleColors[$user->role] ?? 'dark';
                    @endphp
                    <span class="badge badge-{{ $color }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </td>
                <td>
                    @if($user->profile && $user->profile->category)
                        <span class="badge badge-info">
                            {{ $user->profile->category->name }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-center">
                    <small class="text-muted">{{ $user->created_at->format('d/m/Y') }}</small>
                </td>
                <td class="text-center">
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.users.show', $user) }}"
                           class="btn btn-info btn-sm"
                           title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('admin.users.edit', $user) }}"
                           class="btn btn-warning btn-sm"
                           title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user) }}"
                                  method="POST"
                                  class="d-inline"
                                  onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="btn btn-danger btn-sm"
                                        title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="text-center py-4">
                    <i class="fas fa-search fa-2x text-muted mb-3"></i>
                    <p class="text-muted mb-0">No se encontraron usuarios con los filtros aplicados.</p>
                    <small class="text-muted">Intenta con otros términos de búsqueda.</small>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- Paginación -->
<div class="mt-3" id="pagination-container">
    {{ $users->links('custom.pagination') }}
</div>
