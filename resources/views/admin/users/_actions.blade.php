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
    @if($canDelete)
        <form action="{{ route('admin.users.destroy', $user) }}"
              method="POST"
              class="d-inline delete-form">
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
