@props(['active'])

<span {{ $attributes->class(['badge', 'badge-success' => $active, 'badge-secondary' => ! $active]) }}>
    {{ $active ? 'Ativo' : 'Inativo' }}
</span>
